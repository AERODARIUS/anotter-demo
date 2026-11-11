"""Security boundaries for the host tunnel workflow; provider behavior uses live tests."""
import importlib.util
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

SPEC = importlib.util.spec_from_file_location("dev", Path(__file__).resolve().parents[1] / "scripts/dev.py")
dev = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(dev)


class PublicOriginTests(unittest.TestCase):
    def test_only_exact_https_quick_tunnel_origins(self):
        self.assertEqual(dev.validate_public_url("https://demo-test.trycloudflare.com/"), "https://demo-test.trycloudflare.com")
        for url in ("http://demo.trycloudflare.com", "https://localhost", "https://demo.trycloudflare.com.evil.org", "https://demo.trycloudflare.com:443", "https://user@demo.trycloudflare.com", "https://demo.trycloudflare.com/path", "https://demo.trycloudflare.com?x=y", "https://*.trycloudflare.com"):
            with self.subTest(url=url), self.assertRaises(ValueError):
                dev.validate_public_url(url)

    def test_public_origin_edit_preserves_private_values_and_restores(self):
        with tempfile.TemporaryDirectory() as folder:
            env = Path(folder) / ".env"
            original = "SITE_URL=http://localhost:8080\nDB_PASSWORD=example-private-value\n"
            env.write_text(original, encoding="utf-8")
            with patch.object(dev, "ENV_FILE", env), patch.object(dev, "compose"), patch.object(dev, "wait_ready"):
                dev.public_url("https://demo.trycloudflare.com")
                self.assertTrue(env.read_text().startswith(original))
                dev.public_url("")
                self.assertEqual(env.read_text().count("PUBLIC_SITE_URL="), 1)
                self.assertIn("PUBLIC_SITE_URL=\n", env.read_text())
                self.assertIn("DB_PASSWORD=example-private-value", env.read_text())


if __name__ == "__main__":
    unittest.main()
