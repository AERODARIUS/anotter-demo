#!/usr/bin/env python3
"""Create private, separate M05 QA credentials/volumes without touching the demo .env."""
import os
from pathlib import Path
import re
import dev

target = dev.PRIVATE_WORK / "qa.env"
target.parent.mkdir(parents=True, exist_ok=True)
if target.exists():
    print("Existing isolated QA environment preserved.")
else:
    dev.init_env(target)
    content = target.read_text(encoding="utf-8")
    for key, value in {"COMPOSE_PROJECT_NAME":"anotter-m05-qa", "HTTP_PORT":"8095", "SITE_URL":"http://localhost:8095", "APP_ENV":"local", "PUBLIC_SITE_URL":""}.items():
        content = re.sub(r"^" + key + r"=.*$", lambda _: key + "=" + value, content, flags=re.M)
    with target.open("w", encoding="utf-8", newline="\n") as stream:
        stream.write(content)
    dev.validate_env(dev.read_env(target))
print(f"QA file: {target}. Project anotter-m05-qa, loopback port 8095.")
print("Set ANOTTER_ENV_FILE to that absolute path for setup/tests. Never publish it.")
