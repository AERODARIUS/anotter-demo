#!/usr/bin/env python3
"""Offline publication checks; diagnostics never include matched values.

Default: tracked and non-ignored untracked files. --staged reads the index.
--history also checks commits/blobs reachable from local branches, tags and HEAD,
including identities. --all-refs additionally audits private editor/recovery refs.
This is a heuristic check, not a guarantee that arbitrary secrets are absent.
"""
from __future__ import annotations

import argparse
import json
import os
from pathlib import Path, PurePosixPath
import re
import stat
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
EMAIL = re.compile(rb"[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}")
RESERVED_DOMAINS = (b"example.com", b"example.org", b"example.net")
PATTERNS = {
    "provider-credential": rb"\b(?:APP_USR|TEST)-[A-Za-z0-9][A-Za-z0-9-]{19,}\b",
    "service-token": (
        rb"\b(?:gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{20,}"
        rb"|glpat-[A-Za-z0-9_-]{20,}|AKIA[A-Z0-9]{16}|ASIA[A-Z0-9]{16}"
        rb"|AIza[A-Za-z0-9_-]{30,}|xox[baprs]-[A-Za-z0-9-]{15,}"
        rb"|sk-(?:proj-)?[A-Za-z0-9_-]{20,}|[rs]k_live_[A-Za-z0-9]{16,})\b"
    ),
    "private-key": rb"-----BEGIN (?:[A-Z0-9 ]+ )?PRIVATE KEY-----",
    "jwt": rb"\beyJ[A-Za-z0-9_-]{12,}\.[A-Za-z0-9_-]{12,}\.[A-Za-z0-9_-]{12,}\b",
    "credential-url": rb"https?://[^\s/<>\"']+:[^\s/<>\"']+@",
    "personal-path": rb"(?:(?i:[A-Z]:[\\/]Users[\\/][^\s\\/\"']+)|/(?:home|Users)/[A-Za-z0-9_.-]+)",
    "account-number": rb"\b[0-9]{9,}\b",
    "order-key": rb"\bwc_order_[A-Za-z0-9]{8,}\b",
}
COMPILED = {name: re.compile(pattern) for name, pattern in PATTERNS.items()}
ASSIGNMENT = re.compile(
    rb"(?im)^[ \t]*[\"']?([A-Z0-9_]*(?:PASSWORD|PASSWD|SECRET|TOKEN|API_KEY|PRIVATE_KEY|AUTH_KEY|AUTH_SALT|NONCE_KEY|NONCE_SALT)[A-Z0-9_]*)"
    rb"[\"']?[ \t]*[:=][ \t]*[\"']?([^\s\"'#,;}]+)"
)
TUNNEL = re.compile(rb"(?i)https://([a-z0-9-]+)\.trycloudflare\.com")
PRIVATE_DIRS = {
    ".git", ".ssh", ".aws", ".sf", ".sfdx", ".codex", ".agents", "work", "runtime", "vendor",
    "node_modules", "wordpress", "backups", "private-backups", "private", "secrets",
    "playwright-report", "test-results", "coverage", "__pycache__",
}
PRIVATE_NAMES = {".npmrc", ".pypirc", ".netrc", ".git-credentials", "auth.json", "credentials.json"}
PRIVATE_SUFFIXES = {
    ".log", ".sql", ".dump", ".zip", ".gz", ".tar", ".tgz", ".7z", ".bak",
    ".db", ".sqlite", ".sqlite3", ".pem", ".key", ".p12", ".pfx", ".keystore",
    ".har", ".mp4", ".webm", ".pyc",
}


def git(root: Path, *args: str, input_data: bytes | None = None) -> bytes:
    result = subprocess.run(
        ["git", "-c", "core.fsmonitor=false", *args], cwd=root,
        input=input_data, stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=False,
    )
    if result.returncode:
        # Git errors can contain credential-bearing remote URLs.
        raise RuntimeError("Git operation failed; inspect the repository locally.")
    return result.stdout


def versionable_paths(root: Path) -> list[str]:
    return sorted(set(filter(None, git(root, "ls-files", "--cached", "--others", "--exclude-standard", "-z").decode("utf-8").split("\0"))))


def read_objects(root: Path, objects: list[bytes]):
    """One Git process, with size-delimited binary output (including images)."""
    batch = git(root, "cat-file", "--batch", input_data=b"\n".join(objects) + b"\n") if objects else b""
    offset = 0
    for expected in objects:
        end = batch.index(b"\n", offset)
        oid, kind, size_bytes = batch[offset:end].split()
        size = int(size_bytes)
        offset = end + 1
        data = batch[offset:offset + size]
        if oid != expected or len(data) != size or batch[offset + size:offset + size + 1] != b"\n":
            raise RuntimeError("Incomplete Git object scan.")
        offset += size + 1
        yield oid, kind, data


def private_path(name: str) -> bool:
    path = PurePosixPath(name)
    if path.is_absolute() or ".." in path.parts or "\\" in name:
        return True
    if name in (".env.example", "tests/fixtures/.env.example"):
        return False
    leaf = path.name.lower()
    return (
        bool(set(part.lower() for part in path.parts[:-1]) & PRIVATE_DIRS)
        or leaf in PRIVATE_NAMES or leaf.startswith("service-account") and leaf.endswith(".json")
        or leaf == ".env" or leaf.startswith(".env.") or leaf.endswith(".env") or ".env." in leaf
        or path.suffix.lower() in PRIVATE_SUFFIXES
        or "wp-content/uploads" in name or "wp-content/plugins" in name or "wp-content/cache" in name
    )


def reserved_email(address: bytes) -> bool:
    domain = address.lower().rsplit(b"@", 1)[-1]
    # Exact userinfo string from a negative URL-validation fixture, not a mailbox.
    return domain in RESERVED_DOMAINS or domain.endswith((b".invalid", b".test")) or address == b"user@demo.trycloudflare.com"


def placeholder(value: bytes) -> bool:
    lower = value.lower()
    return (
        not value or lower in (b"none", b"null", b"false", b"true")
        or value.startswith((b"$", b"<", b"{", b"(", b"[", b"rb", b"re."))
        or lower.startswith((b"example-", b"example_", b"test-", b"test_", b"dummy-", b"dummy_", b"replace-", b"your-"))
    )


def findings(data: bytes, known: tuple[bytes, ...] = ()) -> list[tuple[int, str]]:
    hits = set()
    for rule, pattern in COMPILED.items():
        for match in pattern.finditer(data):
            hits.add((data.count(b"\n", 0, match.start()) + 1, rule))
    for match in EMAIL.finditer(data):
        if not reserved_email(match.group()):
            hits.add((data.count(b"\n", 0, match.start()) + 1, "non-placeholder-email"))
    for match in TUNNEL.finditer(data):
        if match.group(1).lower() not in (b"demo", b"demo-test", b"your-assigned-host"):
            hits.add((data.count(b"\n", 0, match.start()) + 1, "public-tunnel"))
    for match in ASSIGNMENT.finditer(data):
        if not placeholder(match.group(2)):
            hits.add((data.count(b"\n", 0, match.start()) + 1, "credential-assignment"))
    for secret in known:
        offset = data.find(secret)
        if offset >= 0:
            hits.add((data.count(b"\n", 0, offset) + 1, "private-environment-value"))
    return sorted(hits)


def environment_values(paths: list[Path]) -> tuple[bytes, ...]:
    values = set()
    for path in paths:
        if not path.is_file():
            continue
        for line in path.read_bytes().splitlines():
            key, sep, value = line.strip().partition(b"=")
            value = value.strip().strip(b"\"'")
            if not sep or key.startswith(b"#") or not value:
                continue
            if re.search(rb"PASSWORD|SECRET|TOKEN|KEY|SALT", key) and len(value) >= 8:
                values.add(value)
            elif key == b"WP_ADMIN_EMAIL" and not reserved_email(value):
                values.add(value)
    return tuple(sorted(values))


def check_source(root: Path, known: tuple[bytes, ...], staged: bool = False) -> tuple[list[tuple[str, int, str]], int]:
    failures = []
    count = 0
    if staged:
        entries = []
        for row in git(root, "ls-files", "--stage", "-z").split(b"\0"):
            if row:
                header, name = row.split(b"\t", 1)
                mode, oid, stage = header.split()
                if stage != b"0":
                    raise RuntimeError("Resolve index conflicts before scanning.")
                entries.append((name.decode("utf-8"), mode, oid))
    else:
        entries = [(name, None, None) for name in versionable_paths(root)]
    blobs = {}
    if staged:
        blobs = {oid: data for oid, kind, data in read_objects(root, [entry[2] for entry in entries if entry[1] in (b"100644", b"100755")]) if kind == b"blob"}
    for name, mode, oid in entries:
        path_hits = findings(name.encode("utf-8"), known)
        label = "[private-filename]" if path_hits else name
        failures.extend((label, 0, "private-filename:" + rule) for _, rule in path_hits)
        if private_path(name):
            failures.append((label, 0, "private-path"))
            continue
        if staged:
            if mode not in (b"100644", b"100755"):
                failures.append((label, 0, "unsupported-file-type"))
                continue
            if oid not in blobs:
                raise RuntimeError("Missing staged blob.")
            data = blobs[oid]
        else:
            path = root / name
            if not path.exists() and not path.is_symlink():
                continue
            if not stat.S_ISREG(path.lstat().st_mode) or path.is_symlink():
                failures.append((label, 0, "unsupported-file-type"))
                continue
            data = path.read_bytes()
        count += 1
        failures.extend((label, line, rule) for line, rule in findings(data, known))
    return failures, count


def check_history(root: Path, known: tuple[bytes, ...], all_refs: bool = False) -> tuple[list[tuple[str, int, str]], int]:
    failures = []
    ref_args = () if all_refs else ("refs/heads", "refs/tags")
    refs = git(root, "for-each-ref", "--format=%(refname)", *ref_args).splitlines()
    revisions = ["--all"] if all_refs else ["HEAD", *(ref.decode("utf-8") for ref in refs)]
    for ref in refs:
        for _, rule in findings(ref, known):
            # Local editor checkpoints use long numeric capture IDs. Their
            # contents and all other ref-name rules still receive a full scan.
            if rule == "account-number" and ref.startswith(b"refs/codex/turn-diffs/"):
                continue
            failures.append(("[private-refname]", 0, rule))
    for commit in git(root, "rev-list", *revisions, "--").splitlines():
        ref = commit.decode("ascii")
        failures.extend(("commit:" + ref[:12], line, rule) for line, rule in findings(history_metadata(git(root, "cat-file", "commit", ref)), known))
        for row in git(root, "ls-tree", "-rz", ref).split(b"\0"):
            if row:
                header, name = row.split(b"\t", 1)
                if findings(name, known):
                    failures.append(("tree:" + ref[:12], 0, "private-filename"))
                if private_path(name.decode("utf-8")) or header.split()[0] not in (b"100644", b"100755"):
                    failures.append(("tree:" + ref[:12], 0, "private-path-or-file-type"))
    count = 0
    objects = [row.split(b" ", 1)[0] for row in git(root, "rev-list", "--objects", *revisions, "--").splitlines()]
    for oid_bytes, kind, data in read_objects(root, objects):
        if kind not in (b"blob", b"tag"):
            continue
        count += 1
        oid = oid_bytes.decode("ascii")
        if kind == b"tag":
            data = history_metadata(data)
        failures.extend(("object:" + oid[:12], line, rule) for line, rule in findings(data, known))
    return failures, count


def history_metadata(data: bytes) -> bytes:
    # Git's required timestamps are not provider account numbers. Keep identity
    # and message bytes, and normalize only timestamps in the metadata headers.
    header, separator, body = data.partition(b"\n\n")
    header = re.sub(rb"(?m)^((?:author|committer|tagger) .+> )[0-9]+ [+-][0-9]{4}$", rb"\1[TIMESTAMP]", header)
    return header + separator + body


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--staged", action="store_true", help="Check the complete index instead of working files.")
    parser.add_argument("--history", action="store_true", help="Also check local branches, tags, HEAD and their reachable history/identities.")
    parser.add_argument("--all-refs", action="store_true", help="With --history, additionally audit private editor/recovery refs; these are not published by a normal main push.")
    parser.add_argument("--env-file", action="append", type=Path, default=[], help="Compare values from an additional private environment; never printed.")
    args = parser.parse_args()
    if args.all_refs and not args.history:
        parser.error("--all-refs requires --history")
    try:
        env_paths = [ROOT / ".env", *args.env_file]
        if os.environ.get("ANOTTER_ENV_FILE"):
            env_paths.append(Path(os.environ["ANOTTER_ENV_FILE"]))
        if any(not path.is_file() for path in env_paths[1:]):
            raise RuntimeError("An explicitly selected private environment is unavailable.")
        known = environment_values(env_paths)
        failures, count = check_source(ROOT, known, args.staged)
        if args.history:
            historical, objects = check_history(ROOT, known, args.all_refs)
            failures.extend(historical)
            print(f"History: {objects} reachable blobs/tags checked.")
        for name, line, rule in sorted(set(failures)):
            print(f"FAIL {json.dumps(name, ensure_ascii=True)}:{line} [{rule}]")
        if failures:
            print("FAIL: publication blocked; matched values were withheld.")
            return 1
        print(f"PASS: {count} source files checked. No detected private material; manual review is still required.")
        return 0
    except (OSError, RuntimeError, UnicodeError, ValueError):
        print("FAIL: scan incomplete. Inspect files and Git availability locally; no private error details emitted.", file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
