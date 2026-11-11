# M04 — temporary HTTPS with Cloudflare Quick Tunnel

Install `cloudflared` from [Cloudflare's official installation instructions](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/). Docker Desktop must run Linux containers; `python scripts/dev.py setup` must succeed first. Confirm local `http://localhost:8080/` works. No Cloudflare account/domain/email login is needed for a [Quick Tunnel](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/).

## Start and stop

```powershell
python scripts/dev.py tunnel
```

The helper starts `cloudflared tunnel --url http://localhost:8080` (uses `HTTP_PORT` if changed), reads the assigned HTTPS hostname from ignored `work/m04/cloudflared.log`, updates only `PUBLIC_SITE_URL` in private `.env`, and recreates only the WordPress container to receive it. DB URLs/content are untouched. Keep the terminal open. Ctrl+C terminates the child, restores the previous public-origin value and recreates WordPress. A crash/forced process kill cannot run cleanup: stop the specific cloudflared process and run `python scripts/dev.py public-url` to restore local-only configuration. Never kill unrelated tunnels.

For a manually managed tunnel, in terminal A:

```powershell
cloudflared tunnel --url http://localhost:8080
```

In terminal B, paste its exact assigned hostname:

```powershell
python scripts/dev.py public-url --public-url https://YOUR-ASSIGNED-HOST.trycloudflare.com
python scripts/dev.py payments --mode status
python scripts/dev.py test-m04
npm run test:m04
# After testing, Ctrl+C in terminal A, then:
python scripts/dev.py public-url
```

The placeholder must be replaced with a lower-case actual hostname. The validator accepts only HTTPS root URLs on one explicit `trycloudflare.com` subdomain; ports, paths, wildcards, userinfo, queries and arbitrary hosts are rejected. Run URL changes/restarts on an idle demo, after test fixtures are cleaned up. Both automatic/manual workflows preserve `.env` secrets and existing ACLs. Do not print expanded Compose config.

## Proxy boundary and local access

Compose publishes Apache exclusively on `127.0.0.1`; MySQL has no host port. Cloudflared preserves the incoming public Host on its connection to Apache. Project-owned `docker/public-origin.php` accepts only the exact configured public Host, sets `HTTPS=on`, port 443 and per-request WordPress home/site URL overrides. **X-Forwarded-Proto and X-Forwarded-Host are ignored**, so an arbitrary header cannot select an origin. Unexpected web Hosts receive 421. Loopback local access continues to use the database's local URLs.

This is a host allowlist behind a loopback boundary, not a general multi-proxy trust system. Local software can send that Host itself and is already within the boundary. Do not expose Apache on all interfaces or copy this configuration to production without redesigning the proxy boundary. Health checks and ordinary WP-CLI remain local; `payments`/`test-m04` use the explicit public URL. No WordPress/WooCommerce core files are changed.

Native WordPress URL helpers supply HTTPS asset/REST/checkout URLs. A narrow render filter adapts exact configured local-origin URLs embedded in saved blocks without modifying the database. Never run serialized search/replace for a temporary tunnel. Browsers use separate local/public native session cookies: open a fresh cart in the buyer's private public-origin session. Recreating containers preserves named-volume cart/order data, but does not migrate cookies across hosts.

## Verify each new hostname

1. Run the safe status/configuration checks above and open the public homepage/cart/checkout. Verify all requests and redirects use the same HTTPS origin, assets load, Store API responds and cart contents persist in that browser.
2. Confirm the official custom IPN/return overrides are empty. The gateway generates native order-specific success/pending URLs, a cancellation failure URL, and its callback from the public origin.
3. Run `npm run test:m04`: malformed synthetic callback requests must be rejected, forged browser approval must leave the isolated order unpaid, cart/checkout must render, and diagnostics must be inaccessible. This proves accessibility/rejection only.
4. After linking the test seller, inspect a **new** actual preference and its success/failure/pending and notification destinations privately. Old payment attempts retain the old host. Update any manually configured seller/dashboard URL and create new attempts after each change.
5. Keep public exposure alive until actual asynchronous events have been observed. Closing the browser does not stop the tunnel; closing the tunnel does stop callbacks. Stop it when finished.

Apache additionally denies dotfiles, log/SQL/environment/backup artifacts and WooCommerce diagnostic directories. Project scripts/tests/configuration live outside the web root or read-only private mounts. No database administration, development server, mail preview service or secrets endpoint is exposed. Native WordPress admin is reachable through the public site for official linking; protect the private demo administrator password. `noindex` is a disclosure/indexing preference, not access control.

## Limits and recovery

Quick Tunnels are development infrastructure: ephemeral hostnames, no uptime guarantee and service limitations documented by Cloudflare. Do **not** use interactive email authentication on callbacks; providers need non-interactive HTTPS. Do not disable TLS certificate checking, add a fake payment callback or process real money to work around failures.

If cloudflared exits, inspect its private log/network access and start a new tunnel. If a config file under the user's cloudflared directory prevents a Quick Tunnel, follow the official troubleshooting instructions; do not delete unrelated configuration automatically. Incorrect hostname/redirect: inspect safe status, apply the exact new URL, reopen a fresh public session and rebuild the payment attempt. SSL/mixed-content: inspect actual network requests, configured origin and saved block customizations.

During outage, orders may remain unpaid even if a provider payment succeeded. Restore connectivity, inspect the authoritative test payment and native notes, and use only official notification resend/status-sync facilities that are actually offered for that transaction. Provider retry timing and guarantees have **not** been established for this plugin/test-account path; do not promise automatic recovery. A different Quick Tunnel hostname cannot receive retries addressed to the old hostname. Keep the affected criterion incomplete until the real notification/status is observed.
