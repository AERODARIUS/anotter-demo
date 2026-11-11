# M04 execution and acceptance — 2026-10-28

**Latest follow-up: M06 execution, 2026-10-31.** Authenticated MCP returned zero visible applications; provider test-account inspection requires unresolved owner verification. Docker Desktop/VM startup and safe recovery were attempted, but live engine/API health failed. Twelve host tests and targeted secret scan passed; current runtime and provider acceptance remain blocked. See [testing-m06.md](testing-m06.md). M04 passes below are historical, not rerun or treated as genuine payment evidence.

**Historical checkpoint:** M05 has since added QA/demo preparation and a reviewed WP 7.1.4 security patch. See [testing-m05.md](testing-m05.md). Historical M04 results below are retained; genuine provider acceptance is still incomplete and explicitly reserved for M06.

**Implementation is available; M04 payment acceptance is incomplete.** No authorized seller/buyer account, login or credential was supplied. No genuine provider preference, approved/rejected/pending payment or valid asynchronous event has been observed. No order was marked paid to claim an integration result. M05 had not started at this historical checkpoint; its later preparation is tracked separately.

## Environment and baseline

Repository inspected before implementation: `AGENTS.md`, README, architecture, checkout/shipping/roadmap, Compose, setup helpers and existing suites. Runtime: WordPress **7.1.3**, WooCommerce **11.2.1**, PHP 8.3, native `anotter-demo` **0.3.0** block theme, Checkout/Cart Blocks, Store API, HPOS, UYU, Uruguay-only shipping, native DAC/UES DEMO estimates and labelled offline BACS. Foundation plugin is now **0.4.0**. Mercado Pago was absent initially. Official **8.9.7** was downloaded/inspected separately in ignored work files, then installed/activated and checksum-verified by setup in the existing volume. No core/vendor source was modified or committed.

The installed plugin declares Blocks and HPOS compatibility. Its header's WooCommerce tested-through version is **11.0.1**, below our **11.2.1**. Registration/uncredentialed native checkout are checked; account-dependent Checkout Pro and provider transactions still need acceptance. No evidence required switching to classic checkout, so Blocks were preserved. Existing offline-only checkout/FAQ wording was updated to distinguish the two demo methods without claiming real payment support.

Docker was initially stopped. Docker Desktop was started; this machine's working Linux-engine context is `desktop-linux`. In PowerShell set `$env:DOCKER_CONTEXT='desktop-linux'` if the default engine pipe is unavailable. This is an environment detail, not a committed Docker-context change.

Before installing Mercado Pago, M01 smoke and the original six host tests passed. The first M02 attempt reached four widths, then was interrupted by a configuration mount being changed before container recreation; M03 consequently hit HTTP 500. After applying the missing mount, **M02's 14 groups and M03's 16 groups passed completely**. This was an implementation sequencing error, not an accepted baseline failure. The initial M04 setup also exposed the old M01 assertion that explicitly prohibited Mercado Pago; that assertion now recognizes M04 and retains restricted demo-gateway checks. Final regression results are recorded below.

## Reproducible commands

```powershell
python -m unittest discover -s tests -p "test_*.py" -v
python scripts/check-secrets.py
python scripts/dev.py lint
python scripts/dev.py setup
python scripts/dev.py wp -- plugin verify-checksums woocommerce-mercadopago
python scripts/dev.py test-m04
npm run test:m04
python scripts/dev.py tunnel
# While the tunnel remains open, in another terminal:
python scripts/dev.py payments --mode status
python scripts/dev.py test-m04
npm run test:m04
# Ctrl+C in the tunnel terminal restores the previous origin.
python scripts/dev.py payments --mode offline
python scripts/dev.py wp -- eval-file /opt/anotter/tests/storefront.php
python scripts/dev.py wp -- eval-file /opt/anotter/tests/commerce.php
npm run test:e2e
npm run test:m03
# Only after browser fixtures finish:
python scripts/dev.py verify
```

`test-m04` uses real bootstrapped WordPress/WooCommerce to check plugin/version, gateway/receiver, block support, HPOS, UYU, shipping, defaults and demo eligibility. `test:m04` creates one ownership-marked hidden product and **unpaid pending order**, uses the real official HTTP receiver and browser, compares paid/status/stock before/after, and cancels/deletes only its fixtures through native CRUD. It never invokes a successful notification handler or `payment_complete`. Cart/return requests use native access keys internally; those URLs are not printed. Browser captures are ignored under `work/m04/screenshots/`.

Visual review caught an early test that populated only a Cart-Token session while navigating a cookie browser; it could accept an empty-cart redirect. The test now adds through the browser's native cookie session, asserts the actual `/checkout/` URL and waits for the owned product text, not a visible loading skeleton. Intermediate selector/timing failures were corrected; the final public run passed with a fully hydrated order summary (UYU 100 product + UYU 260 sample shipping = UYU 360), and its capture was inspected. No provider amount or payment is inferred from that native total.

The secret scanner examines versionable files (including untracked non-ignored source), detects current private environment values and likely Mercado Pago token shapes, and prints only filenames on failure. It is a targeted check, not proof that all possible secrets are absent. Host tunnel unit tests use isolated files and mocked Compose calls to avoid changing the real environment; application/notification tests use real services without payment mocks.

If a test process is forcibly interrupted after creating its owned fixtures, run `python scripts/dev.py wp -- eval-file /opt/anotter/tests/payment-fixture.php cleanup` before restarting it. Cleanup verifies ownership and uses native cancellation/CRUD; never delete merchant products/orders or alter SQL internals to clear a test. M03 has its separate documented fixture cleanup workflow.

## Automated and infrastructure matrix

| Check | Status | Actual result / limit |
| --- | --- | --- |
| Host configuration/security tests | Passed | 8 tests; exact HTTPS hostname restrictions and private `.env` value preservation included |
| PHP syntax | Passed | Project theme/plugin/CLI/tests/configuration checked in pinned PHP CLI image |
| Official plugin install/activation/checksums | Passed | 8.9.7 installed in persistent volume; official checksum verification succeeded |
| M04 real configuration | Passed | Official gateway/receiver and Blocks classes register; HPOS, UYU and DEMO shipping preserved |
| Missing-account activation | Passed | `payments --mode pro` refuses before enabling payment; safe status remains MP off / BACS on |
| Invalid/repeated notification requests (local) | Passed | Official receiver returns 400 for malformed Core input and 422 for merchant-order input without id; isolated order/stock unchanged |
| Forged approved browser return (local) | Passed | Native return stays unpaid/pending with stock unreduced and no transaction completion |
| Native session/checkout after return (local) | Passed | Real Store API/cart and Checkout Blocks render; diagnostic directory denied |
| Public Quick Tunnel lifecycle | Passed | cloudflared 2026.10.0; two different assigned hostnames configured safely; Ctrl+C stopped the task child and restored local origin. First stopped public endpoint returned HTTP 530; no provider retry inference |
| Public HTTPS callbacks/returns/session | Passed | Actual public native callback responds/rejects invalid data; native return/cancel origins use HTTPS; cookie cart and fully hydrated Checkout Blocks preserve public origin without local HTTP links/mixed-content errors |
| M01–M03 regressions after plugin activation | Passed | Final setup/smoke (8 HTTP routes), M02/M03 live state suites, all 14 M02 and 16 M03 browser groups; native stock lifecycle, last-unit concurrency and administration passed; desktop/mobile checkout captures inspected |
| Setup idempotency and restart persistence | Passed | Repeated setup preserved the full managed snapshot, including non-secret MP settings; database and uploaded-image hashes survived stop/start; final 8 HTTP routes returned 200 with no PHP fatal/parse errors in that window |
| Versionable secret scan | Passed | No current local secrets or detected Mercado Pago tokens in versionable files |
| Genuine test payment redirect/preference | Blocked | Requires privately linked MLU test seller and separate test buyer |
| Real paid-order/amount/reference correlation | Blocked | No provider transaction; synthetic fixtures do not count |

Resolved failures from interrupted runs are documented above; no unresolved failure is intentionally treated as Passed. Status vocabulary: **Passed**, **Failed**, **Blocked**, **Not executed**. Failed means an executed expectation remains unsatisfied; Blocked means an external prerequisite prevents execution.

## Manual provider scenario matrix

Use only the [official WooCommerce Checkout Pro test-account procedure](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test). Start from a fresh public buyer session and record the synthetic WooCommerce order privately, provider payment/reference, UYU total/shipping/coupon/quantities, before/after paid state, status, native stock/reduction flag, notes and callback delivery timestamps/HTTP response. Do not record tokens, passwords, session cookies, full account/card data or order keys. Administrator inspection remains native WooCommerce Orders.

| Scenario | Steps and acceptance | Status / actual observation |
| --- | --- | --- |
| A — approved | Add a physical demo product, Uruguay address, DAC/UES and coupon if applicable; choose MP; compare hosted amount to WooCommerce; use documented approved test method; observe provider record, actual callback and native paid status/reference/stock | **Blocked** — no test seller/buyer; no approved transaction. Source predicts processing for physical products, not an observed final result |
| B — rejected | New attempt with documented rejection outcome; confirm provider rejection, customer feedback, unpaid native status/notes, no incorrect stock finalization and supported retry | **Blocked** — no rejected transaction; exact status depends on actual IPN/Core path |
| C — pending | If an offered test method supports pending, verify unpaid state/reservations or on-hold reduction; obtain definitive provider update and observe transition | **Blocked** — availability not established; do not mark unsupported without trying the authorized selected method |
| D — interrupted | Abandon hosted checkout/close browser before paying; revisit through native supported retry; verify unpaid state and native pending expiry | **Blocked** for real hosted checkout — no preference created |
| E — valid duplicate | Resend the same **genuine** event through official tools if available; compare order count, payment count, paid timestamp, notes, stock and any fulfillment hooks | **Blocked** — no valid event; repeating invalid requests passed but is not valid-event idempotency evidence |
| F — return before callback | Delay/interrupt callback connectivity while completing a real test payment; visit native return, verify unpaid until authoritative callback then observe correct transition | **Blocked** for genuine payment ordering; forged browser-approval negative test passed separately |
| G — tunnel unavailable | Interrupt tunnel during a real test purchase, inspect provider delivery failure/native pending order, restore same endpoint if possible and use supported resend/sync; observe recovery | **Blocked** for transaction/retry/recovery; infrastructure stop/reachability is tested separately |

For scenario A include at least one non-default quantity and each shipping zone/carrier across attempts; compare a coupon-discounted order to the provider amount. Never compute a separate preference in tests. For E do not replay a fabricated approved body, modify plugin state, create a second charge or set native status manually. If official tools do not allow replay, record that limitation and keep the criterion incomplete.

For G no retry interval/delivery guarantee is asserted. A new Quick Tunnel hostname cannot receive a retry sent to the old hostname. Inspect supported provider resend and plugin sync facilities privately; if absent, record the exact limitation. Never force payment completion to “recover” an order. Infrastructure shutdown was separately observed (old public endpoint HTTP 530, local application restored). Windows signal handling was improved to poll the child process so Ctrl+C promptly reaches Python cleanup instead of waiting indefinitely in `child.wait()`; that updated behavior was exercised with the second hostname.

## Remaining acceptance and handoff

The [setup guide](mercadopago-setup.md) provides exact account/linking/mode/settings steps. Credentials must be entered through official private linking/settings, not chat or source. Run provider scenarios after linking; observe actual notification delivery. Confirm amounts/references belong to the expected order and valid repeated/out-of-order events cannot cause duplicate stock or fulfillment. Verify private administrator payment details and supported customer retry behavior.

The receiver uses authenticated provider lookups; the inspected IPN path does not validate a custom HMAC signature. Browser-supplied payment-reference metadata alone is not authoritative. Runtime eligibility uses official test-user/site flags; activation adds `/users/me` verification, not an independent credential store or processing implementation. Quick Tunnels are ephemeral development exposure. Plugin verbose logs are initialized off, Apache denies downloads of diagnostics, and shared excerpts must be manually redacted. Real merchant configuration remains out of scope.

The official plugin's inspected Core notification path can store limited card-method metadata such as last-four digits. This project collects no PAN/CVV and no card transaction was executed, but a literal zero-card-metadata requirement is not established by the official plugin and remains a constraint for review. Vendor code has not been patched to suppress it.

**M04 is not Definition-of-Done complete until genuine approved/rejected purchases, resulting statuses/amounts/inventory, and actual notification handling are observed (or a specific supported-test limitation is recorded against an incomplete criterion). M04 execution stopped at this checkpoint. Later M05 preparation preserves those incomplete provider gates for M06.**

Final local state: WordPress/MySQL running, public origin empty and both task tunnels stopped, official Mercado Pago 8.9.7 active, Checkout Pro disabled without credentials, labelled BACS restored. Final M04 local browser checks and full M01–M03 regressions passed after plugin activation. Owned synthetic fixtures were cleaned through the suites' native CRUD paths. No genuine provider payment or valid-notification result is claimed.
