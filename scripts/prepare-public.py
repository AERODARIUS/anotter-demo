#!/usr/bin/env python3
"""Create a checked public snapshot with new anonymous history; never push.

The existing repository, runtime, volumes and private environments are preserved.
Only regular versionable working files are copied, never the source .git folder.
"""
from __future__ import annotations

import argparse
import importlib.util
import os
from pathlib import Path
import stat
import subprocess
import sys

SPEC = importlib.util.spec_from_file_location("publication_scanner", Path(__file__).with_name("check-secrets.py"))
scanner = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(scanner)
ROOT = Path(__file__).resolve().parents[1]
IDENTITY_NAME = "Demo Maintainer"
IDENTITY_EMAIL = "maintainer@example.invalid"


def public_git(root: Path, *args: str) -> bytes:
    env = os.environ.copy()
    # Keep inherited Git routing and identity settings out of this new repository.
    for name in tuple(env):
        if name.startswith("GIT_"):
            env.pop(name)
    env.update({
        "GIT_AUTHOR_NAME": IDENTITY_NAME, "GIT_AUTHOR_EMAIL": IDENTITY_EMAIL,
        "GIT_COMMITTER_NAME": IDENTITY_NAME, "GIT_COMMITTER_EMAIL": IDENTITY_EMAIL,
    })
    result = subprocess.run(
        ["git", "-c", "core.fsmonitor=false", "-c", "commit.gpgsign=false",
         "-c", "core.hooksPath=.git/no-hooks", *args],
        cwd=root, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
    )
    if result.returncode:
        raise RuntimeError("Public snapshot Git operation failed; inspect locally.")
    return result.stdout


def prepare(root: Path, output: Path, known: tuple[bytes, ...] = ()) -> int:
    root, output = root.resolve(), output.resolve()
    if not output.is_relative_to(root / "work") or output == root / "work":
        raise ValueError("The output must be a new directory inside work/.")
    if output.exists():
        raise ValueError("Output already exists; choose a new directory. Nothing was overwritten.")
    failures, _ = scanner.check_source(root, known)
    if failures:
        raise ValueError("Source publication scan failed. Run check-secrets.py for locations.")

    # Freeze and recheck the bytes to copy; no source changes after this point
    # can smuggle different content into the snapshot.
    files = []
    for name in scanner.versionable_paths(root):
        path = root / name
        if not path.exists() and not path.is_symlink():
            continue
        if scanner.private_path(name) or not stat.S_ISREG(path.lstat().st_mode) or path.is_symlink():
            raise ValueError("Unsupported or private source file; no snapshot created.")
        data = path.read_bytes()
        if scanner.findings(data, known):
            raise ValueError("Source changed during preparation; rerun the source scan.")
        files.append((name, data))
    output.mkdir(parents=True, exist_ok=False)
    for name, data in files:
        destination = output / name
        destination.parent.mkdir(parents=True, exist_ok=True)
        destination.write_bytes(data)
    public_git(output, "init", "--template=", "--initial-branch=main")
    public_git(output, "config", "--local", "user.name", IDENTITY_NAME)
    public_git(output, "config", "--local", "user.email", IDENTITY_EMAIL)
    public_git(output, "config", "--local", "core.hooksPath", ".githooks")
    public_git(output, "add", "--all")
    for hook in ("pre-commit", "pre-push"):
        if (output / ".githooks" / hook).is_file():
            public_git(output, "update-index", "--chmod=+x", ".githooks/" + hook)
    staged, _ = scanner.check_source(output, known, staged=True)
    if staged:
        raise ValueError("Public snapshot index scan failed; do not publish the output.")
    public_git(output, "commit", "--quiet", "-m", "Prepare independent DEMO source for public release")
    historical, _ = scanner.check_history(output, known)
    if historical or public_git(output, "rev-list", "--count", "--all").strip() != b"1":
        raise ValueError("Public snapshot history scan failed; do not publish the output.")
    if public_git(output, "remote").strip() or public_git(output, "status", "--porcelain").strip():
        raise ValueError("Public snapshot is not clean and isolated; do not publish the output.")
    return len(files)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=Path("work/public-repository"), help="New directory inside work/; existing directories are never overwritten.")
    parser.add_argument("--env-file", action="append", type=Path, default=[], help="Additional private environment for exact-value comparison; never copied.")
    args = parser.parse_args()
    try:
        selected = list(args.env_file)
        if os.environ.get("ANOTTER_ENV_FILE"):
            selected.append(Path(os.environ["ANOTTER_ENV_FILE"]))
        if any(not path.is_file() for path in selected):
            raise ValueError("An explicitly selected private environment is unavailable.")
        known = scanner.environment_values([ROOT / ".env", *selected])
        output = args.output if args.output.is_absolute() else ROOT / args.output
        count = prepare(ROOT, output, known)
        print(f"PASS: {count} files, new anonymous initial commit, clean index and history, no remote. Nothing uploaded.")
        return 0
    except (OSError, RuntimeError, UnicodeError, ValueError) as error:
        # Our errors contain no values/paths; OS errors can, so suppress those.
        message = str(error) if type(error) in (RuntimeError, ValueError) else "Filesystem operation failed; inspect locally."
        print("FAIL: " + message, file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
