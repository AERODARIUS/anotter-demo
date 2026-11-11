# Public source publication

Published source: [AERODARIUS/anotter-demo](https://github.com/AERODARIUS/anotter-demo), public, default branch `main`. This project root is linked as `origin`.

The project folder itself is the repository to publish. Source, theme, plugin, scripts, tests, documentation and original assets belong in Git. Private environments, credentials, backups, downloaded dependencies, Docker data, logs and browser evidence stay local through `.gitignore`. This is an independent fictional DEMO; M06 genuine provider acceptance and M07 recording remain pending.

## What changed in Git

The previous local history included personal commit emails and older documentation with account identifiers and a home path. `.gitignore` cannot remove content already in commits. The prior standalone Git metadata was copied to a verified private backup outside the project and OneDrive. Native Git operations gave this same project folder a new `main` with one anonymous initial commit. The `.git` directory stays in place because Windows blocks its rename. Working source, ignored files, private environments and Docker volumes were preserved. The initial cleanup performed no remote operation; subsequent publication is recorded in [verification.md](verification.md).

The initial author/committer is `Demo Maintainer <maintainer@example.invalid>`, a reserved synthetic identity. Local Git identity and `.githooks` are configured here. The old history is not kept in a public branch/tag or tracked backup. Local editor checkpoints, reflogs and unreachable recovery objects can still contain private prior versions; Git does not send them in a normal `git push origin main`. Do not upload `.git` as an archive, push recovery refs, or use `--mirror`. Local recovery details are stored in ignored `work/git-publication-state.json`; keep that file and the external backup private. Recovering the old history would reintroduce the information excluded from public history.

## Review and upload from the project root

Run these commands directly in the project folder:

```powershell
git status --short
git log --oneline main
python scripts/check-secrets.py --staged --history
```

Expected: clean status and a passing scan. Public history starts at an anonymous initial commit; later reviewed source/documentation commits follow it. Ordinary development changes can then be staged/committed normally. `.env.example` has blank secret fields; reserved synthetic emails and negative-validation fixtures are intentional. Original artwork and font license notices are retained. Real emails, payment account/application numbers, tokens, passwords, private keys, active tunnel hosts and order keys do not belong in source, filenames, messages or Git identities.

The public repository has been created and this workspace already has its `origin`. After reviewing and committing subsequent changes, run from the project root:

```powershell
python scripts/check-secrets.py --staged --history
git push origin main
```

A fresh clone sets `origin` automatically. When using this project as a starting point for a different repository, create an empty repository in the intended account and configure its remote separately.

Authenticate through the Git credential manager; never embed tokens in a remote URL. Push `main` normally. Do not restore the old Git metadata into this public history or push a backup with `--mirror`. This preparation does not grant a new project license; third-party font notices remain unchanged.

## Ongoing checks

Pre-commit checks the complete index, including forcibly added ignored paths. Pre-push additionally checks every local branch, tag and HEAD, including their reachable history and identities. Hooks need Python on PATH. In each fresh clone, enable them with:

```powershell
git config --local core.hooksPath .githooks
```

Local hook configuration and commit identity do not transfer with a clone. Use a reserved or privacy-preserving identity before future commits. Hooks can be bypassed; run the history check before every push as well.

Both scanning tools automatically compare against secrets from root `.env` and the selected `ANOTTER_ENV_FILE` when present. Additional private environments can be selected with repeated `--env-file` arguments. Explicitly selected files must exist. Values are never printed or copied. Provider credentials stored only in the WordPress database are outside the source scan; database, vendor source and uploads are excluded entirely.

`python scripts/check-secrets.py` checks tracked and non-ignored untracked working files. `--staged` reads exact index bytes even when working files differ. `--history` checks commits, tags, blobs and identities reachable from every local branch, tag and HEAD. `--history --all-refs` additionally audits private editor/recovery refs; it may flag expected private old data and is not the publication scope. Common token/key formats, credential assignments, non-placeholder emails, personal paths, numeric account identifiers, actual tunnel hosts, order keys and private file types are checked. Long numeric IDs in local editor checkpoint ref names are not account numbers; all their contents and other rules remain checked. Diagnostics show locations/rules, with sensitive filenames redacted, never matched values.

Checks are offline and heuristic, not proof that all possible secrets are absent. Unreachable objects, local Git configuration/reflogs and unselected external environments are outside the usual history scan. Local recovery metadata is not part of the public branch; an independent complete backup also exists outside the project and OneDrive. If a credential was exposed outside the private checkout, rotate/revoke it with its issuer; deleting a file alone does not undo exposure. No exposure or credential rotation is claimed here.

## Optional checked export

A second checkout is optional. To create one without changing the project repository:

```powershell
python scripts/prepare-public.py
```

The helper creates a new anonymous repository at ignored `work/public-repository/`, copies only checked regular versionable working files, rechecks staged content/history and never copies the source `.git`, private environments or ignored runtime. Existing output directories are refused; use `--output work/public-repository-v2` for another export. It never pushes. Earlier export/rehearsal folders under `work/` are local artifacts and can be ignored when publishing this root repository.
