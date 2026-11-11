"""Publication boundaries use disposable Git repositories and synthetic values."""
import importlib.util
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch


def load(name, filename):
    spec = importlib.util.spec_from_file_location(name, Path(__file__).parents[1] / "scripts" / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


scan = load("publication_check", "check-secrets.py")
export = load("publication_export", "prepare-public.py")


class PublicationTests(unittest.TestCase):
    def test_tokens_emails_keys_and_private_values(self):
        cases = [
            b"APP_" + b"USR-" + b"1" * 12 + b"-" + b"a" * 30,
            b"ghp_" + b"a" * 36,
            b"-----BEGIN " + b"PRIVATE KEY-----",
            b"person" + b"@" + b"mail.example",
            b"WP_ADMIN_PASSWORD=" + b"opaque-value-for-fixture",
            b"wc_" + b"order_" + b"a" * 16,
            b"https://" + b"assigned-fixture-host" + b".trycloudflare.com",
            b"C:" + b"/Users/" + b"fixture-owner/project",
            b"https://" + b"fixture-user:fixture-pass@host.invalid",
        ]
        for index, data in enumerate(cases):
            with self.subTest(index=index):
                self.assertTrue(scan.findings(data))
        known = b"opaque-" + b"private-value"
        self.assertTrue(scan.findings(b"binary\0" + known, (known,)))

    def test_blank_env_does_not_consume_next_line_and_api_is_not_a_path(self):
        self.assertEqual(scan.findings(b"DB_PASSWORD=\nWP_ADMIN_PASSWORD=\nWORDPRESS_AUTH_KEY=\n"), [])
        self.assertEqual(scan.findings(b"demo@example.invalid /users/me https://demo.trycloudflare.com"), [])

    def test_private_paths_include_forced_adds_but_allow_blank_template(self):
        for name in (".env", "main.env", "nested/.env.qa", "secrets/config.json", "backup.sql", "evidence.har", ".npmrc", "credentials.json", "private.key", "work/capture.png", "nested/.git/config", ".sf/orgs/cache.json", ".sfdx/session.json"):
            with self.subTest(name=name):
                self.assertTrue(scan.private_path(name))
        self.assertFalse(scan.private_path(".env.example"))
        self.assertTrue(scan.private_path("nested/.env.example"))

    def test_index_scans_staged_bytes_even_if_working_copy_is_clean(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            export.public_git(root, "init", "--template=", "--initial-branch=main")
            target = root / "sample.txt"
            target.write_bytes(b"person" + b"@" + b"mail.example")
            export.public_git(root, "add", "sample.txt")
            target.write_text("clean working file", encoding="utf-8")
            self.assertTrue(scan.check_source(root, (), staged=True)[0])
            self.assertEqual(scan.check_source(root, ())[0], [])
            (root / ".gitignore").write_text(".env\n", encoding="utf-8")
            (root / ".env").write_text("fixture", encoding="utf-8")
            export.public_git(root, "add", "--force", ".env")
            rules = [rule for _, _, rule in scan.check_source(root, (), staged=True)[0]]
            self.assertIn("private-path", rules)

    def test_cli_withholds_values_and_private_filenames(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            export.public_git(root, "init", "--template=", "--initial-branch=main")
            (root / "scripts").mkdir()
            script = root / "scripts/check-secrets.py"
            script.write_bytes(Path(scan.__file__).read_bytes())
            address = "person" + "@" + "mail.example"
            (root / (address + ".txt")).write_text(address, encoding="utf-8")
            result = subprocess.run([sys.executable, str(script)], cwd=root, capture_output=True, text=True)
            self.assertEqual(result.returncode, 1)
            self.assertNotIn(address, result.stdout + result.stderr)
            self.assertIn("private-filename", result.stdout)

    def test_history_finds_deleted_material_and_tag_and_commit_identities(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            export.public_git(root, "init", "--template=", "--initial-branch=main")
            (root / "sample.txt").write_bytes(b"person" + b"@" + b"mail.example")
            export.public_git(root, "add", "sample.txt")
            export.public_git(root, "commit", "--quiet", "-m", "fixture", "--author", "Fixture <" + "person" + "@" + "mail.example>")
            export.public_git(root, "tag", "--annotate", "fixture", "-m", "person" + "@" + "mail.example")
            (root / "sample.txt").write_text("clean", encoding="utf-8")
            export.public_git(root, "add", "sample.txt")
            export.public_git(root, "commit", "--quiet", "-m", "remove fixture material")
            self.assertEqual(scan.check_source(root, ())[0], [])
            failures, _ = scan.check_history(root, ())
            self.assertTrue(any(name.startswith("commit:") for name, _, _ in failures))
            self.assertGreaterEqual(sum(name.startswith("object:") for name, _, _ in failures), 2)

    def test_editor_ref_capture_number_is_not_an_account_but_blob_is_scanned(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            export.public_git(root, "init", "--template=", "--initial-branch=main")
            (root / "sample.txt").write_text("clean", encoding="utf-8")
            export.public_git(root, "add", "sample.txt")
            export.public_git(root, "commit", "--quiet", "-m", "fixture")
            head = scan.git(root, "rev-parse", "HEAD").strip().decode()
            capture = "refs/codex/turn-diffs/captures/" + "1" * 12 + "/fixture/base"
            export.public_git(root, "update-ref", capture, head)
            self.assertEqual(scan.check_history(root, ())[0], [])
            (root / "sample.txt").write_bytes(b"person" + b"@" + b"mail.example")
            export.public_git(root, "add", "sample.txt")
            tree = scan.git(root, "write-tree").strip().decode()
            export.public_git(root, "update-ref", capture, tree, head)
            self.assertEqual(scan.check_history(root, ())[0], [])
            self.assertTrue(scan.check_history(root, (), all_refs=True)[0])

    def test_snapshot_preserves_source_and_excludes_ignored_runtime(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            export.public_git(root, "init", "--template=", "--initial-branch=main")
            (root / ".gitignore").write_text("work/\n.env\n", encoding="utf-8")
            (root / "sample.txt").write_text("old", encoding="utf-8")
            export.public_git(root, "add", "--all")
            export.public_git(root, "commit", "--quiet", "-m", "private local history")
            before = scan.git(root, "rev-parse", "HEAD")
            (root / "sample.txt").write_text("new working content", encoding="utf-8")
            (root / "new.txt").write_text("untracked source", encoding="utf-8")
            (root / ".env").write_text("private fixture", encoding="utf-8")
            output = root / "work/public"
            with patch.dict(os.environ, {"GIT_AUTHOR_EMAIL": "person" + "@" + "mail.example"}):
                self.assertEqual(export.prepare(root, output), 3)
            self.assertEqual((output / "sample.txt").read_text(), "new working content")
            self.assertTrue((output / "new.txt").exists())
            self.assertFalse((output / ".env").exists())
            self.assertEqual(scan.git(root, "rev-parse", "HEAD"), before)
            self.assertEqual(scan.check_history(output, ())[0], [])
            self.assertEqual(scan.git(output, "rev-list", "--count", "--all").strip(), b"1")
            with self.assertRaises(ValueError):
                export.prepare(root, output)
            self.assertEqual((output / "sample.txt").read_text(), "new working content")


if __name__ == "__main__":
    unittest.main()
