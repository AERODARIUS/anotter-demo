# M06 acceptance plan

## Current checkpoint — 2026-11-10

The existing Docker volumes and exact pinned stack are healthy. A private backup, temporary public HTTPS and local/public M04 preflight passed. Seller authorization and genuine provider acceptance are still pending. See the [current results](testing-m06.md) and [acceptance report](payment-acceptance-report.md). October checkpoints below describe their original dates.

## Latest execution checkpoint — 2026-10-31

**Subsequent test-resource update:** user explicitly requested account creation while restarting Docker. MLU seller card verified after a partial MCP zero-funding error; seller creation not repeated. Separate MLU buyer creation succeeded through MCP with fictitious UYU 1,000 requested. Reuse these accounts; do not create replacements. No Developer application or WooCommerce linkage changed. Account-list reload requires provider verification again, and Docker APIs still fail. [Testing evidence](testing-m06.md) supersedes earlier no-account-created statements below. Provider acceptance remains BLOCKED.

**Later verification follow-up:** user successfully completed provider identity verification privately. Test-account management is accessible and requires test-credential activation before showing accounts; its UI says activation automatically creates accounts. No activation/creation performed. Docker API remains unavailable, so existing WooCommerce seller/application linkage must still be inspected before resource decisions. The earlier verification failures below are historical; see [updated execution evidence](testing-m06.md).

M06 execution has begun. [Testing results](testing-m06.md) supersede the initial checkpoint below: MCP is available and application listing returns zero visible registrations. Docker Desktop was launched, its existing Hyper-V VM was started and a controlled restart attempted; LinuxKit/engine/proxy health still fails. Current store linkage remains unverified. Test-account inspection requires provider owner verification; the user authorized the Mercado Libre authentication domain but cannot complete verification. No new application/test account or payment created. Host tests (12) and targeted secret scan PASS; live/provider acceptance BLOCKED. [Acceptance](payment-acceptance-report.md) is incomplete and [M07 readiness](m07-readiness.md) BLOCKED. Initial connection findings below are retained as history, not current MCP status.

**External acceptance remains BLOCKED.** Authorized separate MLU seller/buyer test accounts and privately linked credentials are absent. M04 implementation/local rejection checks are not full payment acceptance. Do not activate a real merchant, process real money, invent notifications or mark orders paid manually.

## Initial plugin/environment inspection — 2026-10-31

M06 inspection only; external acceptance is incomplete. Historical M04/M05 results do not establish current runtime or account state.

| Check | Status | Evidence / limit |
| --- | --- | --- |
| Repository / recommended solution | PASS | Read AGENTS, README, Mercado Pago setup, M04/M05 reports, this plan and runbook. Retain PHP/WordPress/WooCommerce and official Checkout Pro for Uruguay/UYU; Node is test tooling only. |
| Codex plugin instructions | PASS | Read active 1.0.1 integration/connection/test-account skills and applicable Checkout Pro references. Bundled manifest names `mercadopago` at `https://mcp.mercadopago.com/mcp`; no manifest copied into the project. |
| MCP / OAuth | BLOCKED | No Mercado Pago tools callable in this chat, including `application_list` and `authenticate`. Sanitized `codex mcp list --json` lists no standalone Mercado Pago server. User configuration enables the installed plugin, with no explicit bundled-server enabled override. These observations do not establish OAuth state or the cause of missing bundled tools. Plugin-directory search returned no match. |
| Application / UY test accounts | BLOCKED | Application listing/creation and separate seller/buyer creation could not run. Existence and account identity remain unverified. No credentials or balances retrieved. Test-account skill documents creation/funding, but no such tool was available to execute. |
| WooCommerce-specific official guidance | PASS | Opened the [official testing guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test): separate test seller/buyer and Checkout Pro sales mode for the test seller. This does not authorize linking a real merchant. `developers/llms.txt` was inaccessible through the documentation browser. |
| Runtime / compatibility | BLOCKED | `docker --context desktop-linux compose ps --format json` could not find the Docker Desktop Linux-engine pipe. Live versions/checksums/Blocks/HPOS/settings unverified. Recorded M05 baseline: WP 7.1.4, WC 11.2.1, MP 8.9.7; tested-through WC 11.0.1 remains a risk, not demonstrated incompatibility. |
| HTTPS / gateway configuration | NOT RUN | Existing Quick Tunnel workflow and authoritative test-seller activation guard inspected in source. No tunnel started, origin changed, account linked or gateway enabled. |
| All provider scenarios below / notifications / stock | NOT RUN | No preference/payment/event created, replayed or simulated; no order or stock mutation. |
| Regression suites | NOT RUN | Documentation-only checkpoint; no application/configuration changes. Historical passes are not current test execution. |

**First interactive step:** make the installed plugin's `mercadopago` MCP server available in Codex. Then verify application access or initiate supported OAuth using the connection skill. Keep authentication private; never request access tokens or callback URLs in chat. Application creation and separate authorized MLU test accounts follow only after access is verified, one step at a time. No dependencies changed and M07 was not started.

## Entry gates

- [ ] Review M05 test/security/accessibility/performance reports and current P0/P1 findings; rerun final regression on the intended version set.
- [ ] Obtain an authorized Uruguay test seller and a different Uruguay test buyer. Account owner handles private verification challenges; no passwords/codes/tokens in chat/source/artifacts.
- [ ] Follow the [official WooCommerce test-account procedure](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test): official seller linking, Checkout Pro test-seller sales-mode procedure, separate buyer profile and supported test methods.
- [ ] Confirm official 8.9.7 checksums, Blocks/HPOS registration, UYU, native coupon/shipping totals and exact active versions. M05 patched core to 7.1.4; WooCommerce remains 11.2.1. Plugin tested-through WC 11.0.1 remains a compatibility risk, not established incompatibility.
- [ ] Prefer preserving the verified pinned pair while executing the complete account-dependent matrix in isolated staging. If a reproducible vendor compatibility failure occurs, record it, check current official support/releases, back up, test a supported plugin update or supported WooCommerce pairing in a new isolated environment and rerun all suites before changing the demo. No blind downgrade/upgrade or vendor patch.
- [ ] Start the exact public HTTPS tunnel; fresh buyer cart and new preference for that hostname. Confirm official callback/return origins, no custom stale overrides/mixed content and noninteractive callback accessibility.
- [ ] Run `payments --mode pro --seller-id $env:ANOTTER_TEST_SELLER_ID` privately. The `/users/me` response must match the expected Uruguay test seller before disabling BACS and enabling Checkout Pro.
- [ ] Review provider metadata/privacy: official code may retain limited card-method/last-four information. No custom PAN/CVV collection exists. Establish retention/access requirements; do not assert zero card metadata or compliance without evidence.

## Provider scenario matrix

| Scenario | Required actual evidence | Acceptance |
| --- | --- | --- |
| Approved | Provider test payment, actual official preference/reference, UYU amount with shipping/discount/quantity, genuine delivered notification and native order notes | Correct unpaid→paid transition, expected physical-product processing status, one order/payment, one stock reduction |
| Rejected | Actual documented declined test method and provider rejection | Understandable feedback, correct unpaid/status/reservation/restoration, supported retry; no false success |
| Pending / in process | Supported test method, actual provider status and later definitive update | Preserve unpaid state; distinguish pending reservations from on-hold reduction. If unsupported, document attempted method and leave the specific criterion incomplete |
| Valid duplicate notification | Official resend/replay of the **same genuine event**, if offered | No duplicate order, charge, stock reduction or fulfillment; paid date/state remain coherent. Repeated invalid bodies are insufficient |
| Return before callback | Actual successful test purchase with callback connectivity delayed safely | Browser return does not establish payment; actual official event later synchronizes the correct order |
| Interrupted checkout | Real hosted preference abandoned/reloaded | No false paid state; native reservation expiry/retry behaves correctly |
| Tunnel loss / recovery | Actual provider delivery failure and supported resend/sync, observed privately | Restore original endpoint if possible; new hostname cannot receive old callbacks. Record exact supported recovery; no fabricated completion/retry guarantee |
| Amount/reference and authorization | Match provider-returned reference, amount, currency and account to actual order | Unrelated customer cannot read/modify order; browser query fields cannot change paid state |

Across attempts include Montevideo/interior, both DAC/UES estimates, quantity >1, a native coupon and expected inventory before/after. Do not recompute a separate payment preference. Keep a sanitized ledger: opaque case label, version set, relative timing, expected/actual status, native amount/carrier, stock/reduction flag and evidence location. Store provider IDs and detailed order notes privately. Never retain tokens, OAuth codes, cookies, full account/card details or order keys in the shared ledger.

## Exit gates

- [ ] Genuine approved and rejected transactions observed; pending behavior verified where supported and limitations explicit.
- [ ] Actual provider notifications correlate to correct WooCommerce orders; duplicate/out-of-order handling and stock are observed.
- [ ] Exact plugin/WooCommerce compatibility established for credential-dependent UI, preference, notification, return and supported recovery paths.
- [ ] Regression: host tests, secret scan, lint, smoke/setup idempotency/persistence, storefront and commerce live assertions, M02/M03/M04/M05 browser suites and isolated reset checks all pass.
- [ ] Final sanitized acceptance report separates PASS / FAIL / BLOCKED / NOT RUN. No P0/P1 unresolved; preserve genuine provider records, do not delete them through M05 offline reset.
- [ ] Only after M06 approval proceed to M07 recording using the storyboard. M05 creates this plan and stops before external execution.
