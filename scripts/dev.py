#!/usr/bin/env python3
"""Cross-platform demo tooling. Python 3.10+, standard library only."""
from __future__ import annotations

import argparse
import http.cookiejar
from datetime import datetime, timezone
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
ENV_FILE = Path(os.environ.get("ANOTTER_ENV_FILE", str(ROOT / ".env"))).resolve()
PRIVATE_WORK = ROOT / "work" / "m05" if ENV_FILE.is_relative_to(ROOT) else ENV_FILE.parent
AUTH_NAMES = ("AUTH_KEY", "SECURE_AUTH_KEY", "LOGGED_IN_KEY", "NONCE_KEY",
              "AUTH_SALT", "SECURE_AUTH_SALT", "LOGGED_IN_SALT", "NONCE_SALT")
SECRET_NAMES = ("DB_PASSWORD", "DB_ROOT_PASSWORD", "WP_ADMIN_PASSWORD") + tuple("WORDPRESS_" + key for key in AUTH_NAMES)


def read_env(path: Path) -> dict[str, str]:
    """Intentionally small dotenv subset matching our Compose template."""
    result = {}
    for line in path.read_text(encoding="utf-8-sig").splitlines():
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        key, sep, value = line.partition("=")
        key, value = key.strip(), value.strip()
        if not sep or not re.fullmatch(r"[A-Z][A-Z0-9_]*", key) or key in result:
            raise ValueError("Invalid or duplicate .env key; use .env.example format")
        if len(value) >= 2 and value[0] == value[-1] and value[0] in "\"'":
            value = value[1:-1]
        if any(c in value for c in "$\r\n#\"'"):
            raise ValueError(f"{key}: dotenv interpolation and special quoting are unsupported")
        result[key] = value
    return result


def init_env(path: Path = ENV_FILE) -> None:
    if path.exists():
        print("Existing .env preserved.")
        return
    for variable in ("OneDrive", "OneDriveConsumer", "OneDriveCommercial"):
        sync_root = os.environ.get(variable)
        if sync_root and path.resolve().is_relative_to(Path(sync_root).resolve()):
            raise ValueError("Choose a private ANOTTER_ENV_FILE outside OneDrive before creating credentials.")
    content = (ROOT / ".env.example").read_text(encoding="utf-8")
    for key in SECRET_NAMES:
        content = content.replace(f"{key}=\n", f"{key}={secrets.token_urlsafe(36)}\n")
    # O_EXCL prevents accidental replacement; mode protects secrets on Unix.
    fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
    with os.fdopen(fd, "w", encoding="utf-8", newline="\n") as stream:
        stream.write(content)
    print("Created private .env with independent random credentials. Do not commit it.")


def validate_env(env: dict[str, str]) -> None:
    validate_public_url(env.get("PUBLIC_SITE_URL", ""))
    for key in SECRET_NAMES:
        value = env.get(key, "")
        if len(value) < 24 or re.search(r"change|example|replace", value, re.I):
            raise ValueError(f"{key} must contain a unique, strong secret of at least 24 characters")
    if len({env[k] for k in SECRET_NAMES}) != len(SECRET_NAMES):
        raise ValueError("Administrator, database and authentication keys must have distinct secrets")
    if not re.fullmatch(r"[a-z0-9][a-z0-9_-]*", env.get("COMPOSE_PROJECT_NAME", "")):
        raise ValueError("COMPOSE_PROJECT_NAME must be a lowercase Compose project name")
    if env.get("APP_ENV") not in ("local", "development", "staging", "production"):
        raise ValueError("APP_ENV must be a valid WordPress environment type")
    port = int(env.get("HTTP_PORT", "8080"))
    if not 1024 <= port <= 65535:
        raise ValueError("HTTP_PORT must be between 1024 and 65535")
    url = urllib.parse.urlsplit(env.get("SITE_URL", ""))
    if url.scheme not in ("http", "https") or not url.hostname or url.username or url.password or url.query or url.fragment or url.path not in ("", "/"):
        raise ValueError("SITE_URL must be a root http(s) URL without credentials, query or fragment")
    if url.hostname in ("localhost", "127.0.0.1") and (url.port or (443 if url.scheme == "https" else 80)) != port:
        raise ValueError("Local SITE_URL port must match HTTP_PORT")
    if not re.fullmatch(r"[A-Za-z0-9_.-]+", env.get("WP_ADMIN_USER", "")):
        raise ValueError("WP_ADMIN_USER must be a nonempty simple login name")
    if not re.fullmatch(r"[^\s@]+@[^\s@]+\.[^\s@]+", env.get("WP_ADMIN_EMAIL", "")):
        raise ValueError("WP_ADMIN_EMAIL must be an email address")
    if env.get("DEMO_ENABLE_COD") not in ("yes", "no"):
        raise ValueError("DEMO_ENABLE_COD must be yes or no")
    if not re.fullmatch(r"\d+(\.\d{1,2})?", env.get("DEMO_FLAT_RATE_COST", "")):
        raise ValueError("DEMO_FLAT_RATE_COST must be a nonnegative decimal with at most two decimals")


def execute(args: list[str], *, capture: bool = False, check: bool = True, timeout: int = 600) -> subprocess.CompletedProcess:
    # No shell: values are always literal arguments. Never echo credential-bearing config.
    result = subprocess.run(args, cwd=ROOT, text=True, encoding="utf-8", errors="replace",
                            stdout=subprocess.PIPE if capture else None,
                            stderr=subprocess.PIPE if capture else None, timeout=timeout)
    if check and result.returncode:
        if capture and result.stderr:
            print(result.stderr, file=sys.stderr)
        raise RuntimeError(f"Command failed ({result.returncode}): {args[0]}")
    return result


def compose(*args: str, capture: bool = False, timeout: int = 600) -> subprocess.CompletedProcess:
    return execute(["docker", "compose", "--project-directory", str(ROOT), "--env-file", str(ENV_FILE),
                    "-f", str(ROOT / "compose.yaml"), *args], capture=capture, timeout=timeout)


def require_docker() -> None:
    execute(["docker", "compose", "version"], capture=True, timeout=30)
    result = execute(["docker", "info"], capture=True, check=False, timeout=30)
    if result.returncode or "OSType: linux" not in result.stdout:
        raise RuntimeError("Docker's Linux engine is unavailable. Start Docker Desktop with WSL2/Linux containers, then retry.")


def wait_ready(timeout: int = 300) -> None:
    deadline = time.monotonic() + timeout
    for service in ("db", "wordpress"):
        while time.monotonic() < deadline:
            container = compose("ps", "-q", service, capture=True).stdout.strip()
            if container:
                state = execute(["docker", "inspect", "--format", "{{json .State}}", container], capture=True).stdout
                state = json.loads(state)
                health = state.get("Health", {}).get("Status")
                if state.get("Running") and health == "healthy":
                    print(f"{service}: healthy")
                    break
                if health == "unhealthy" or state.get("Status") in ("exited", "dead"):
                    raise RuntimeError(f"{service} is unhealthy. Inspect docker compose logs {service} locally.")
            time.sleep(2)
        else:
            raise RuntimeError(f"Timed out waiting for {service}. Inspect docker compose logs.")


def wp(*args: str, capture: bool = False) -> subprocess.CompletedProcess:
    return compose("run", "--rm", "-T", "--no-deps", "wpcli", *args, capture=capture)


def setup() -> None:
    compose("up", "-d", "db", "wordpress")
    wait_ready()
    compose("run", "--rm", "-T", "--no-deps", "--entrypoint", "sh", "wpcli", "/opt/anotter/scripts/setup.sh")


def snapshot() -> dict:
    output = wp("eval-file", "/opt/anotter/tests/snapshot.php", capture=True).stdout
    # A sentinel keeps non-JSON plugin notices from corrupting the snapshot.
    lines = [line.removeprefix("ANOTTER_SNAPSHOT=") for line in output.splitlines() if line.startswith("ANOTTER_SNAPSHOT=")]
    if len(lines) != 1:
        raise RuntimeError("Could not read the database snapshot")
    return json.loads(lines[0])


def http_checks(env: dict[str, str]) -> None:
    output = wp("eval-file", "/opt/anotter/tests/routes.php", capture=True).stdout
    routes = next((json.loads(line.split("=", 1)[1]) for line in output.splitlines() if line.startswith("ANOTTER_ROUTES=")), None)
    if not routes:
        raise RuntimeError("No smoke test routes were returned")
    # A private cookie jar exercises the actual checkout with a nonempty cart.
    session = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    for name, route in routes.items():
        url = env["SITE_URL"].rstrip("/") + route
        with session.open(url, timeout=45) as response:
            body = response.read().decode("utf-8", errors="replace")
            if response.status != 200 or not body.strip():
                raise RuntimeError(f"HTTP smoke failure: {name}")
            if re.search(r"Fatal error|Uncaught (?:Error|Exception)|critical error on this website", body, re.I):
                raise RuntimeError(f"PHP fatal error in {name}")
            if name in ("home", "shop", "product") and "DEMO INDEPENDIENTE" not in body:
                raise RuntimeError(f"Missing independent demonstration notice: {name}")
            if name == "store-api" and not isinstance(json.loads(body), list):
                raise RuntimeError("WooCommerce Store API did not return a product list")
            if name == "checkout" and urllib.parse.urlsplit(response.geturl()).path != urllib.parse.urlsplit(url).path:
                raise RuntimeError("Checkout redirected away instead of rendering with the seeded cart")
        print(f"HTTP 200: {name}")


def check_logs(since: str) -> None:
    logs = compose("logs", "--no-color", "--since", since, "wordpress", capture=True).stdout
    if re.search(r"PHP (?:Fatal|Parse) error|Uncaught (?:Error|Exception)", logs):
        raise RuntimeError("PHP fatal/parse errors found in application logs. Inspect docker compose logs wordpress.")
    print("No PHP fatal/parse errors in the tested request window.")


def smoke(env: dict[str, str]) -> None:
    since = datetime.now(timezone.utc).isoformat()
    wait_ready()
    wp("eval-file", "/opt/anotter/tests/smoke.php")
    http_checks(env)
    check_logs(since)


def verify(env: dict[str, str]) -> None:
    """Real DB comparisons: idempotency, preserved edits, and stop/start persistence."""
    baseline = snapshot()
    setup()
    if snapshot() != baseline:
        raise RuntimeError("Setup changed existing products, users, pages, shipping, options or media")
    print("Repeated setup preserved the complete managed data snapshot.")
    compose("stop", "wordpress", "db")
    compose("up", "-d", "db", "wordpress")
    wait_ready()
    if snapshot() != baseline:
        raise RuntimeError("Stop/start changed persisted data")
    print("Database and uploaded image hashes survived stop/start.")
    smoke(env)


def lint() -> None:
    compose("run", "--rm", "-T", "--no-deps", "--entrypoint", "sh", "wpcli", "/opt/anotter/tests/lint.sh")


def validate_public_url(url: str) -> str:
    url = url.rstrip("/")
    if url and not re.fullmatch(r"https://[a-z0-9]+(?:-[a-z0-9]+)*\.trycloudflare\.com", url):
        raise ValueError("PUBLIC_SITE_URL must be an exact HTTPS Quick Tunnel root hostname")
    return url


def public_url(url: str) -> None:
    """Change only one private environment key; preserve canonical DB URLs and secrets."""
    url = validate_public_url(url)
    content = ENV_FILE.read_text(encoding="utf-8")
    line = "PUBLIC_SITE_URL=" + url
    if re.search(r"^PUBLIC_SITE_URL=.*$", content, re.M):
        content = re.sub(r"^PUBLIC_SITE_URL=.*$", lambda _: line, content, flags=re.M)
    else:
        content += "\n" + line + "\n"
    # Write in place to preserve the existing file ACL/mode; never print its content.
    with ENV_FILE.open("w", encoding="utf-8", newline="\n") as stream:
        stream.write(content)
    os.environ.pop("PUBLIC_SITE_URL", None)
    compose("up", "-d", "--no-deps", "wordpress")
    wait_ready()
    print("Public origin: " + (url or "disabled; local development restored"))
    print("New tunnel: recreate pending payment attempts; old preferences retain old callback URLs.")


def tunnel(env: dict[str, str]) -> None:
    """Foreground Quick Tunnel; Ctrl+C stops child and restores the previous origin."""
    binary = shutil.which("cloudflared")
    if not binary:
        raise RuntimeError("Install cloudflared first; see docs/cloudflare-tunnel.md")
    previous = env.get("PUBLIC_SITE_URL", "")
    work = ROOT / "work" / "m04"
    work.mkdir(parents=True, exist_ok=True)
    logfile = work / "cloudflared.log"
    child = None
    configured = False
    try:
        with logfile.open("w", encoding="utf-8") as log:
            child = subprocess.Popen([binary, "tunnel", "--url", "http://localhost:" + env.get("HTTP_PORT", "8080")],
                                     cwd=ROOT, stdout=log, stderr=log,
                                     creationflags=subprocess.CREATE_NO_WINDOW if os.name == "nt" else 0)
            deadline = time.monotonic() + 90
            while time.monotonic() < deadline:
                if child.poll() is not None:
                    raise RuntimeError("cloudflared exited; inspect ignored work/m04/cloudflared.log")
                match = re.search(r"https://[a-z0-9]+(?:-[a-z0-9]+)*\.trycloudflare\.com", logfile.read_text(encoding="utf-8", errors="replace"))
                if match:
                    configured = True
                    public_url(match.group())
                    print("Keep this terminal open. Ctrl+C stops the tunnel and restores the previous origin.")
                    # Keep Python responsive to Ctrl+C on Windows; an infinite
                    # WaitForSingleObject inside child.wait() delays SIGINT handling.
                    while child.poll() is None:
                        time.sleep(0.5)
                    raise RuntimeError("Tunnel stopped unexpectedly; public origin restored")
                time.sleep(0.5)
            raise RuntimeError("No Quick Tunnel URL received; inspect work/m04/cloudflared.log")
    except KeyboardInterrupt:
        print("Stopping Quick Tunnel.")
    finally:
        if child is not None and child.poll() is None:
            child.terminate()
            try:
                child.wait(timeout=10)
            except subprocess.TimeoutExpired:
                child.kill()
                child.wait(timeout=10)
        if configured:
            public_url(previous)


def require_local_demo(env: dict[str, str]) -> None:
    """Host boundary; PHP independently validates the actual managed installation."""
    if (env.get("APP_ENV") not in ("local", "development")
            or urllib.parse.urlsplit(env["SITE_URL"]).hostname not in ("localhost", "127.0.0.1")
            or env.get("PUBLIC_SITE_URL")):
        raise ValueError("Demo mutation requires local/development, loopback SITE_URL and no public origin")


def doctor(env: dict[str, str]) -> int:
    """Read-only diagnostics. Missing payment prerequisites stay BLOCKED, never PASS."""
    failed = False
    try:
        require_docker()
        wait_ready(timeout=15)
        print("PASS: Docker services running and healthy")
    except (OSError, RuntimeError, subprocess.SubprocessError) as error:
        print("FAIL: Docker services unavailable or unhealthy")
        print("NOT RUN: WordPress/WooCommerce state; service prerequisite failed")
        return 1
    report = wp("eval-file", "/opt/anotter/scripts/php/doctor.php", capture=True).stdout
    print(report, end="")
    failed = "FAIL:" in report
    try:
        http_checks(env)
        print("PASS: Native local HTTP storefront/cart/checkout")
    except (OSError, RuntimeError, urllib.error.URLError):
        print("FAIL: Native local HTTP storefront/cart/checkout")
        failed = True
    origin = env.get("PUBLIC_SITE_URL", "")
    if origin:
        try:
            with urllib.request.urlopen(origin + "/", timeout=30) as response:
                valid = response.status == 200 and urllib.parse.urlsplit(response.url).netloc == urllib.parse.urlsplit(origin).netloc
                valid = valid and "DEMO INDEPENDIENTE" in response.read().decode("utf-8", errors="replace")
            print(("PASS" if valid else "FAIL") + ": Public HTTPS storefront and configured hostname")
            failed = failed or not valid
        except (OSError, urllib.error.URLError):
            print("FAIL: Configured public HTTPS storefront")
            failed = True
    else:
        print("NOT RUN: Public HTTPS endpoint; no tunnel configured")
    print("BLOCKED: Genuine approved/rejected/pending Mercado Pago acceptance belongs to M06")
    return 1 if failed else 0


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("command", choices=("init-env", "setup", "smoke", "verify", "lint", "stop", "start", "reset", "wp", "tunnel", "public-url", "payments", "test-m04", "doctor", "demo-reset", "demo-tag"))
    parser.add_argument("--public-url", default="", help="Exact HTTPS Quick Tunnel origin; empty restores local access")
    parser.add_argument("--mode", choices=("status", "offline", "pro"), default="status")
    parser.add_argument("--seller-id", help="Expected Mercado Pago test seller ID for payments --mode pro")
    parser.add_argument("--confirm-reset", metavar="PROJECT_NAME", help="Required exact project name for deleting this project's volumes")
    parser.add_argument("--session", default="rehearsal", help="Explicit rehearsal session (4–40 lowercase letters/digits/hyphens)")
    parser.add_argument("--apply", action="store_true", help="Apply demo changes; default is a read-only dry run")
    parser.add_argument("--dry-run", action="store_true", help="Explicit read-only preview")
    parser.add_argument("--confirm-project", help="Exact project name required for demo writes")
    parser.add_argument("--delete-orders", action="store_true", help="Delete only eligible offline orders enrolled in this session")
    parser.add_argument("--order", type=int, help="Native order ID for explicit demo-tag enrollment")
    args, wp_args = parser.parse_known_args()
    if args.seller_id is not None and (
        args.command != "payments" or args.mode != "pro" or not re.fullmatch(r"[1-9][0-9]*", args.seller_id)
    ):
        parser.error("--seller-id requires a positive numeric ID and payments --mode pro")
    if wp_args and args.command != "wp":
        parser.error("Unexpected arguments: " + " ".join(wp_args))
    if wp_args[:1] == ["--"]:
        wp_args = wp_args[1:]
    try:
        if args.command == "init-env":
            init_env()
            return 0
        if args.command == "setup" and not ENV_FILE.exists():
            init_env()
        if not ENV_FILE.exists():
            raise RuntimeError("Missing .env. Run python scripts/dev.py init-env first.")
        env = read_env(ENV_FILE)
        validate_env(env)
        if args.command in ("demo-reset", "demo-tag"):
            require_local_demo(env)
            if not re.fullmatch(r"[a-z0-9][a-z0-9-]{3,39}", args.session):
                raise ValueError("Invalid rehearsal session")
            if args.apply and args.dry_run:
                raise ValueError("Use either --apply or --dry-run")
            if args.apply and args.confirm_project != env["COMPOSE_PROJECT_NAME"]:
                raise ValueError("Applying requires --confirm-project with the exact COMPOSE_PROJECT_NAME")
            if args.command == "demo-tag" and (not args.order or args.order < 1):
                raise ValueError("demo-tag requires --order with a positive native ID")
        # Prevent ambient shell variables silently overriding the reviewed .env.
        for key in env:
            os.environ.pop(key, None)
        compose("config", "--quiet")
        if args.command == "doctor":
            return doctor(env)
        require_docker()
        if args.command == "setup":
            setup()
            smoke(env)
        elif args.command == "smoke":
            smoke(env)
        elif args.command == "verify":
            verify(env)
        elif args.command == "lint":
            lint()
        elif args.command == "stop":
            compose("stop")
        elif args.command == "start":
            compose("up", "-d", "db", "wordpress")
            wait_ready()
        elif args.command == "wp":
            wp(*wp_args)
        elif args.command == "public-url":
            public_url(args.public_url)
        elif args.command == "tunnel":
            tunnel(env)
        elif args.command == "payments":
            origin = env.get("PUBLIC_SITE_URL", "") or env["SITE_URL"]
            seller_args = (args.seller_id,) if args.seller_id is not None else ()
            wp("--url=" + origin, "eval-file", "/opt/anotter/scripts/php/payments.php", args.mode, *seller_args)
        elif args.command == "test-m04":
            origin = env.get("PUBLIC_SITE_URL", "") or env["SITE_URL"]
            wp("--url=" + origin, "eval-file", "/opt/anotter/tests/payments.php")
        elif args.command in ("demo-reset", "demo-tag"):
            wp("eval-file", "/opt/anotter/scripts/php/demo.php", "tag" if args.command == "demo-tag" else "reset",
               args.session, "apply" if args.apply else "dry-run", args.confirm_project or "none",
               "delete-orders" if args.delete_orders else "preserve-orders", str(args.order or 0))
        elif args.command == "reset":
            if args.confirm_reset != env["COMPOSE_PROJECT_NAME"]:
                raise ValueError("Destructive reset requires --confirm-reset followed by the exact COMPOSE_PROJECT_NAME")
            compose("down", "--volumes", "--remove-orphans")
            print("Deleted this project's database and WordPress volumes. The private .env was retained.")
        return 0
    except (OSError, ValueError, RuntimeError, subprocess.SubprocessError, urllib.error.URLError) as error:
        print(f"ERROR: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
