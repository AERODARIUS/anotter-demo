"""Host-side guard tests: real integration/reset behavior is tested separately in QA."""
import importlib.util
from pathlib import Path
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location("dev_m05", Path(__file__).parents[1] / "scripts/dev.py")
dev = importlib.util.module_from_spec(spec)
spec.loader.exec_module(dev)


class LocalGuardTests(unittest.TestCase):
    def test_local_and_development_only(self):
        for kind in ("local", "development"):
            dev.require_local_demo({"APP_ENV": kind, "SITE_URL": "http://localhost:8095", "PUBLIC_SITE_URL": ""})
        for kind in ("staging", "production", "", "LOCAL"):
            with self.subTest(kind=kind), self.assertRaises(ValueError):
                dev.require_local_demo({"APP_ENV": kind, "SITE_URL": "http://localhost:8095"})

    def test_no_public_or_remote_canonical_reset(self):
        for url, public in (("https://store.example", ""), ("http://localhost.evil.invalid", ""), ("http://localhost:8095", "https://demo.trycloudflare.com")):
            with self.subTest(url=url), self.assertRaises(ValueError):
                dev.require_local_demo({"APP_ENV": "local", "SITE_URL": url, "PUBLIC_SITE_URL": public})

    def test_apply_refused_before_docker_without_confirmation(self):
        env = {"COMPOSE_PROJECT_NAME":"qa", "APP_ENV":"local", "SITE_URL":"http://localhost:8095"}
        with patch.object(dev, "read_env", return_value=env), patch.object(dev, "validate_env"), patch.object(dev, "compose") as compose, patch("sys.argv", ["dev.py", "demo-reset", "--apply"]):
            self.assertEqual(dev.main(), 1)
            compose.assert_not_called()

    def test_conflicting_dry_run_apply_and_invalid_session_refused(self):
        env = {"COMPOSE_PROJECT_NAME":"qa", "APP_ENV":"local", "SITE_URL":"http://localhost:8095"}
        for flags in (("--apply", "--dry-run"), ("--session", "../../production")):
            with patch.object(dev, "read_env", return_value=env), patch.object(dev, "validate_env"), patch.object(dev, "compose") as compose, patch("sys.argv", ["dev.py", "demo-reset", *flags]):
                self.assertEqual(dev.main(), 1)
                compose.assert_not_called()


if __name__ == "__main__":
    unittest.main()
