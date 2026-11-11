"""Host-side tests for credential safety and configuration validation; no Docker needed."""
import importlib.util
from pathlib import Path
import tempfile
import unittest

SPEC = importlib.util.spec_from_file_location("dev", Path(__file__).resolve().parents[1] / "scripts/dev.py")
dev = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(dev)


class EnvironmentTests(unittest.TestCase):
    def generated(self, directory):
        path = Path(directory) / ".env"
        dev.init_env(path)
        return path, dev.read_env(path)

    def test_generated_secrets_are_valid_distinct_and_not_replaced(self):
        with tempfile.TemporaryDirectory() as directory:
            path, env = self.generated(directory)
            dev.validate_env(env)
            before = path.read_bytes()
            dev.init_env(path)
            self.assertEqual(before, path.read_bytes())
            self.assertEqual(len(dev.SECRET_NAMES), len({env[key] for key in dev.SECRET_NAMES}))

    def test_example_is_rejected_without_secrets(self):
        with self.assertRaisesRegex(ValueError, "DB_PASSWORD"):
            dev.validate_env(dev.read_env(dev.ROOT / ".env.example"))

    def test_reused_secrets_are_rejected(self):
        with tempfile.TemporaryDirectory() as directory:
            _, env = self.generated(directory)
            env["WP_ADMIN_PASSWORD"] = env["DB_PASSWORD"]
            with self.assertRaisesRegex(ValueError, "distinct"):
                dev.validate_env(env)

    def test_local_url_must_match_port(self):
        with tempfile.TemporaryDirectory() as directory:
            _, env = self.generated(directory)
            env["HTTP_PORT"] = "8090"
            with self.assertRaisesRegex(ValueError, "port must match"):
                dev.validate_env(env)
            env["SITE_URL"] = "http://localhost:8090"
            dev.validate_env(env)

    def test_invalid_shipping_cost_and_gateway_are_rejected(self):
        with tempfile.TemporaryDirectory() as directory:
            _, env = self.generated(directory)
            env["DEMO_FLAT_RATE_COST"] = "-20"
            with self.assertRaisesRegex(ValueError, "nonnegative"):
                dev.validate_env(env)
            env["DEMO_FLAT_RATE_COST"] = "220.50"
            env["DEMO_ENABLE_COD"] = "true"
            with self.assertRaisesRegex(ValueError, "yes or no"):
                dev.validate_env(env)

    def test_unsafe_dotenv_and_duplicate_keys_are_rejected(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / ".env"
            for content in ("VALUE=${TOKEN}\n", "VALUE=one\nVALUE=two\n"):
                path.write_text(content)
                with self.assertRaises(ValueError):
                    dev.read_env(path)


if __name__ == "__main__":
    unittest.main()
