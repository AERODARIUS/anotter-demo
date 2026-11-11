#!/usr/bin/env python3
"""Actual temporary HTTPS regression only; no authorized provider transactions."""
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import time
import urllib.request

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
import dev

env = dev.read_env(dev.ENV_FILE)
dev.validate_env(env)
dev.require_local_demo(env)
for key in env:
    os.environ.pop(key, None)
binary = shutil.which('cloudflared')
if not binary:
    raise RuntimeError('BLOCKED: cloudflared missing')
out = dev.ROOT / 'work' / 'm05' / 'public'
out.mkdir(parents=True, exist_ok=True)
child = None
configured = False
results = []
failure = None
try:
    with (out / 'cloudflared.log').open('w', encoding='utf-8') as log:
        child = subprocess.Popen([binary, 'tunnel', '--url', env['SITE_URL']], stdout=log, stderr=log,
                                 creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0)
        deadline = time.monotonic() + 90
        while time.monotonic() < deadline:
            if child.poll() is not None:
                raise RuntimeError('Quick Tunnel failed; inspect private ignored log')
            match = re.search(r'https://[a-z0-9]+(?:-[a-z0-9]+)*\.trycloudflare\.com', (out / 'cloudflared.log').read_text(errors='replace'))
            if match:
                break
            time.sleep(.5)
        else:
            raise RuntimeError('No public URL assigned')
        origin = match.group()
        configured = True
        dev.public_url(origin)
        with urllib.request.urlopen(origin, timeout=45) as response:
            assert response.status == 200 and 'DEMO INDEPENDIENTE' in response.read().decode('utf-8')
        results.append('PASS: actual public HTTPS storefront')
        subprocess.run([sys.executable, 'scripts/dev.py', 'doctor'], cwd=dev.ROOT, check=True)
        subprocess.run([sys.executable, 'scripts/dev.py', 'test-m04'], cwd=dev.ROOT, check=True)
        subprocess.run(['node', 'tests/payments.mjs'], cwd=dev.ROOT, check=True)
        results.append('PASS: real public host/receiver/forged-return/session/checkout negative suite')
        results.append('BLOCKED: genuine provider payments and valid notifications — M06')
except Exception as error:
    failure = type(error).__name__
    results.append('FAIL: public regression; inspect private local diagnostics')
    raise
finally:
    if child and child.poll() is None:
        child.terminate()
        try:
            child.wait(timeout=10)
        except subprocess.TimeoutExpired:
            child.kill()
            child.wait(timeout=10)
    if configured:
        dev.public_url(env.get('PUBLIC_SITE_URL', ''))
        results.append('PASS: task tunnel stopped and prior local origin restored')
    (out / 'results.json').write_text(json.dumps({'results': results, 'failure': failure}, indent=2), encoding='utf-8')
    for result in results:
        print(result)
