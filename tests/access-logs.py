"""Real container-side query-redaction probe; print only a result, never log content."""
from pathlib import Path
import secrets
import sys
import time
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
import dev
probe = 'm05-log-' + secrets.token_hex(6)
code = '$c=stream_context_create(array("http"=>array("ignore_errors"=>true)));@file_get_contents("http://127.0.0.1/' + probe + '/?m05_query_probe=synthetic",false,$c);'
dev.compose('exec', '-T', 'wordpress', 'php', '-r', code, capture=True)
time.sleep(.5)
output = dev.compose('logs', '--tail', '200', 'wordpress', capture=True)
logs = output.stdout + output.stderr
if probe not in logs:
    raise RuntimeError('NOT RUN: probe request not observed in current access-log window')
assert 'm05_query_probe' not in logs, 'FAIL: query string persisted in access logs'
print('PASS: actual unique request path logged; synthetic query omitted')
