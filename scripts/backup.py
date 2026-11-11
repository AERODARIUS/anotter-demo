#!/usr/bin/env python3
"""Private native MySQL and WordPress backup. Backup contents stay off stdout."""
from datetime import datetime, timezone
import os
import subprocess
import dev

env = dev.read_env(dev.ENV_FILE)
dev.validate_env(env)
dev.require_local_demo(env)
for key in env:
    os.environ.pop(key, None)
dev.require_docker()
target = dev.PRIVATE_WORK / 'private-backups' / datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')
target.mkdir(parents=True, exist_ok=False)
# Use MySQL's matching client. Alpine WP-CLI's MariaDB client lacks caching_sha2_password.
dump = dev.compose('exec', '-T', 'db', 'sh', '-c',
                   'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump --single-transaction --no-tablespaces --hex-blob --default-character-set=utf8mb4 -u "$MYSQL_USER" "$MYSQL_DATABASE"', capture=True)
fd = os.open(target / 'database.sql', os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
with os.fdopen(fd, 'w', encoding='utf-8', newline='\n') as stream:
    stream.write(dump.stdout)
# Stream the binary archive without sharing the private host backup directory.
container = dev.compose('ps', '-q', 'wordpress', capture=True).stdout.strip()
if not container:
    raise RuntimeError('WordPress must be running to back up its files')
fd = os.open(target / 'wordpress.tar.gz', os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
with os.fdopen(fd, 'wb') as stream:
    subprocess.run(['docker', 'exec', '--user', '33:33', container, 'tar', '-C', '/var/www/html',
                    '--exclude=./wp-config.php', '--exclude=./wp-cli.yml',
                    '--exclude=./wp-content/themes/anotter-demo',
                    '--exclude=./wp-content/plugins/anotter-demo-foundation', '-czf', '-', '.'],
                   cwd=dev.ROOT, stdout=stream, check=True, timeout=600)
for name in ('database.sql', 'wordpress.tar.gz'):
    file = target / name
    if not file.exists() or file.stat().st_size < 1000:
        raise RuntimeError('Backup incomplete; do not upgrade')
print(f'PASS: private database and WordPress backups created under {target}.')
print('Preserve .env privately as well. Backups contain private data; never export or commit them.')
print('Host-mounted config/theme/plugin source is excluded; preserve the working tree separately.')
