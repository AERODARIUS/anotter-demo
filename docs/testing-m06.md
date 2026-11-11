# M06 execution

## Live execution checkpoint — 2026-11-10

**Overall status: BLOCKED on payment acceptance.** The live demo, public HTTPS, seller authorization and Checkout Pro availability were verified. The next evidence requires the separate test buyer to complete a hosted checkout. The October checkpoints below are historical.

| Check | Status | Actual observation |
| --- | --- | --- |
| Compose and existing volumes | PASS | Private `main.env` passed `docker compose config --quiet`. Linux Engine 20.10.17 reported healthy WordPress and MySQL. Existing `anotter-demo_wordpress` and `anotter-demo_database` are in use. Apache binds to loopback and MySQL has no host port. |
| Installed versions and official checksum | PASS | WordPress 7.1.4, WooCommerce 11.2.1 and active Mercado Pago 8.9.7. `wp plugin verify-checksums woocommerce-mercadopago --strict` passed. The plugin declares WooCommerce tested through 11.0.1. |
| Payment settings before onboarding | PASS | `payments --mode status` showed UYU, BACS enabled, Checkout Pro disabled with redirect and automatic return, blank return/IPN overrides, and no saved seller, client ID, integration ID or credential pairs. |
| Recoverable backup | PASS | The previous bind-mount method was rejected by Docker Desktop. `python scripts/backup.py` streamed the WordPress archive to private LocalAppData and wrote a native MySQL dump. Verified archive entries and exclusions and the MySQL completion marker. |
| Local readiness | PASS | `doctor`, `lint` and `smoke` passed their local checks. Eight HTTP routes returned 200 and smoke reported no PHP fatal or parse errors. Doctor separately flagged missing provider setup. |
| Local and public M04 preflight | PASS | `python scripts/dev.py test-m04` passed against both origins. Checkout Pro and its receiver are registered. Blocks, HPOS, UYU and the two native demo rates for Montevideo and interior are intact. |
| Temporary public HTTPS | PASS | Official Quick Tunnel workflow gave the exact public origin. Public home, Store API products and WordPress login returned 200. The unauthenticated admin URL redirected to login. Loopback home remained 200. |
| Host checks | PASS | Twelve host tests and the targeted secret scan passed. Invalid `--seller-id` was rejected before the activation path. |
| Storefront and commerce regressions | PASS | `npm run test:e2e` passed 14 groups across five widths. `npm run test:m03` passed 16 groups, including concurrent last-unit checkout and native stock lifecycle. |
| Mercado Pago official research | PASS | MCP `search_documentation` (MLU, Spanish), the Uruguay site and the pinned installed source were reviewed. See the application decision in the [acceptance report](payment-acceptance-report.md). |
| Seller authorization and application association | PASS | The operator linked the existing seller through WooCommerce. The provider confirmed the expected MLU test user and the plugin client ID matched the automatically created application shown in the seller's dashboard. |
| Checkout Pro in the public checkout | PASS | After the operator saved sales mode, `payments --mode pro --seller-id $env:ANOTTER_TEST_SELLER_ID` verified the seller again and disabled BACS. `test-m04` passed. Chromium loaded a fresh cart and public checkout in UYU with Checkout Pro visible and BACS absent. |
| Approved and rejected purchases | BLOCKED | Requires an authorized test buyer in the hosted checkout. No provider payment or notification was observed. |

## Historical execution — 2026-10-31

**Overall status: BLOCKED; not accepted.** This is an actual execution checkpoint, not payment evidence. Two Uruguay test-account creation calls were made on the user's subsequent explicit request; their outcomes are recorded below. No Developer application, WooCommerce credential configuration, preference, payment or valid event created. No WooCommerce order/status/stock changed. Existing application, dependencies, vendor plugin and persistent volumes preserved. M07 not started.

## Executed work and evidence

### Latest follow-up: test accounts created; Docker restart rechecked

The user explicitly requested test-account creation while their Docker restart completed. Official MCP `create_test_user` was used; no ordinary Mercado Libre account was opened.

| Operation | Status | Verified outcome |
| --- | --- | --- |
| MLU test seller creation | PASS | Requested profile `seller`, description `AnOtter Game M06 WooCommerce Checkout Pro vendedor de prueba`. Initial call used amount 0 and returned an error at the funding stage: amount must be greater than 0. A dashboard reload nevertheless displayed the exact new account card, type Vendedor, country Uruguay. Creation was NOT repeated. |
| Seller initial funding | FAIL | Tool rejected zero fictitious funding. Do not treat the error as proof no account exists; do not retry account creation. No positive seller balance was credited by this call. |
| MLU test buyer creation | PASS | Requested profile `buyer`, distinct description `AnOtter Game M06 WooCommerce Checkout Pro comprador de prueba`, fictitious initial amount UYU 1,000. MCP returned `Test User Created Successfully`, Site ID MLU, Profile buyer. Login/credential-bearing response was not printed or written to source/reports. |
| Buyer balance reconciliation | NOT RUN | Initial amount was requested through test-user tooling; no independent provider-balance lookup or purchase executed. |
| Browser account-list follow-up | BLOCKED | Following buyer creation/reload, provider required owner verification again. Buyer creation is evidenced by MCP success; a refreshed two-account UI view is not claimed. |
| Docker after user's restart | FAIL | Repeated default and desktop-linux `docker version` checks still returned missing engine named pipes. Current WooCommerce linkage/runtime remains BLOCKED. |

Only fictitious test resources were used; no real funding/payment action occurred. No Developer application, seller registration, WooCommerce linkage, test-credential activation or gateway setting changed. Preserve the created accounts and reuse them when account management/runtime access returns; do not create replacements because of the seller call's partial error. The earlier zero-accounts/activation-gate observations and no-creation statements below describe previous checkpoints, not this latest state. Authoritative seller/buyer identity validation is still required before any test transaction.

### Follow-up: owner verification completed

On a later attempt on 2026-10-31 the user received and privately entered the verification code. Browser inspection confirmed successful access to the test-account management page. **PASS — account-owner verification.** No code or authentication URL was retained. This supersedes the earlier verification blocker in the historical execution rows below.

The page now says test credentials must be activated before test accounts can be viewed, and activation automatically creates the required accounts. **BLOCKED — account inventory/activation:** no account rows are available yet; this does not establish that no accounts exist. Do not manually create duplicate accounts ahead of reviewing that activation workflow. WooCommerce's current seller/application association is still inaccessible because Docker API health failed again for both default and desktop-linux contexts. No registration, credential activation, account creation or store configuration change was performed.

Read AGENTS, README, architecture, Mercado Pago setup/payment flow, M04/M05 results, M06 plan and demo runbook. Inspected existing setup, gateway eligibility, native notification paths and pinned official plugin reference source. The project remains PHP/WordPress/WooCommerce; Node is test tooling only.

| Check | Status | Actual observation |
| --- | --- | --- |
| Host environment/configuration tests | PASS | `python -m unittest discover -s tests -p 'test_*.py' -v`: 12 tests passed. Environment creation messages belong to temporary unit-test fixtures; no project credentials were replaced. |
| Targeted secret scan | PASS | `python scripts/check-secrets.py` passed. This checks detected token patterns/current environment secrets in versionable files, not arbitrary-secret absence. |
| Authenticated MCP application inventory | PASS | `application_list` succeeded and returned zero visible applications for its authorized account. This does not identify the WooCommerce seller or exclude applications owned by another account. |
| Official documentation | PASS | MCP `search_documentation`, country MLU / Spanish: WooCommerce integration tests, plugin linking and WooCommerce test-account guide reviewed. WooCommerce-specific procedure takes precedence over generic product guidance. |
| Browser application inventory | PASS | Authorized Developer Dashboard displayed the first-application state. Browser and MCP identity equivalence was not independently established. No account identifiers retained. |
| Existing test-account inventory | BLOCKED | Dashboard's test-account link required account-ownership verification. Initial cross-origin observation was rejected by automatic review for the Mercado Libre destination; user subsequently authorized that domain for this integration. The provider then required user verification; the user reported they could not complete it. No accounts were enumerated or created. |
| Docker Desktop launch | PASS | Started existing installed Docker Desktop. Observed 4.12.0 UI / Docker CLI 20.10.17. No software update, backend switch, disk reset or reinstall. |
| Existing VM startup | PASS | Existing Hyper-V `DockerDesktopVM` was Off; `Start-VM -Name DockerDesktopVM` returned Running / normal status. VM startup is not a functioning Docker engine. |
| Docker engine/API health | FAIL | `docker version`/`ps` failed for default, desktop-linux and the diagnostic tool's Linux named pipe. UI displayed failed startup. Local official `com.docker.diagnose.exe check` reported failed DD0031, DD0004, DD0011, DD0003, DD0014, DD0009, DD0010 and DD0032. Backend/proxy/LinuxKit services were unavailable. Diagnostic process exit 0 did not mean health checks passed. |
| Non-destructive recovery | FAIL | Controlled `DockerCli.exe -Shutdown` and Desktop relaunch completed; subsequent engine checks still failed. Troubleshoot Restart control was disabled. No cleanup/reset/volume operation or security change attempted. |
| Current WooCommerce linkage / versions / settings | BLOCKED | Database lives in unavailable Docker engine. Saved client/application ID, seller identity, credential presence and OAuth association cannot be established from source or old reports. |
| Live gateway / Blocks / HPOS / UYU / shipping | BLOCKED | Runtime unavailable. Historical baseline WP 7.1.4 / WC 11.2.1 / MP 8.9.7 is not a new installed-version check. |
| Tunnel / public notification reachability | BLOCKED | No healthy local origin. Existing tunnel workflow inspected but not started; no public exposure or origin change. |
| PHP lint / live M01–M05 regressions | BLOCKED | Docker PHP/WordPress/WooCommerce prerequisites unavailable. Historical passes not relabelled as current execution. |

Docker startup was checked outside the restricted shell as well as inside it. Local diagnostics confirmed host/VM disk space, virtualization and Hyper-V capability; these do not resolve the missing LinuxKit/engine/proxy services. Diagnostics were not uploaded. WSL was not substituted for the existing Hyper-V backend, because that could select a different data store. Current API failure is an infrastructure failure, not a reproduced WC/MP compatibility defect.

## Account ownership and reuse decision

The official [WooCommerce Checkout Pro test guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test) describes selecting a developer integration, obtaining two separate same-country test accounts, signing in as the test seller, registering its application, and linking that seller through WooCommerce's official account-linking flow. Checkout Pro then uses sales mode **only with that verified test seller**; the separate buyer uses provider test methods. The [test-account guide](https://www.mercadopago.com/developers/es/docs/woocommerce/additional-content/your-integrations/test/accounts) describes account management and verification codes. Its generic notes do not replace the WooCommerce-specific sequence.

There are two different ownership contexts: the developer's authorized MCP account and the seller linked to WooCommerce. MCP `create_application` creates under its OAuth owner; it cannot be assumed to create under a separate test seller. There is no exposed MCP tool for listing existing test users or reading this WooCommerce database. The zero-application response's generic suggestion to create is not sufficient evidence to create resources here.

No new application is presently justified until live WooCommerce linkage and existing test accounts can be inspected. If missing registrations are established later, a developer-owned integration may be needed for managing test resources, and the WooCommerce-specific flow requires a seller-owned test application. Reuse suitable existing resources first. Seller registration/linking connects the **existing** WooCommerce plugin through official authorization; no replacement ecommerce app, gateway, SDK server, or notification receiver is required. Never install the real developer account's merchant credentials merely because MCP is authenticated.

## Provider scenario matrix

| Scenario | Status | Missing evidence |
| --- | --- | --- |
| Approved payment | BLOCKED | Verified separate MLU test accounts, linked seller, real test preference/payment, actual receiver processing and native stock/status correlation |
| Rejected payment and retry | BLOCKED | Provider decline and observed native unpaid/status/stock/retry behavior |
| Pending / in process | BLOCKED | Available configured test method and genuine pending/final status; support not yet established |
| Abandoned hosted checkout | BLOCKED | Genuine preference and native unpaid/reservation behavior |
| Return before notification | BLOCKED | Real test payment and safely controlled callback ordering |
| Valid duplicate notification | BLOCKED | Actual event and supported official resend; no fabricated replay |
| Invalid notification | BLOCKED | Current live official receiver unavailable; M04 historical negative checks remain historical |
| Tunnel outage / recovery | BLOCKED | Healthy public origin, real payment/delivery failure and supported reconciliation |
| Amount / reference / inventory correlation | BLOCKED | Genuine provider record and corresponding native order/stock observations |
| Exact WC / MP account-dependent compatibility | BLOCKED | Live version checks and full credential-dependent matrix; no upgrade/downgrade justified |
| Real merchant payments | NOT APPLICABLE | Outside this test-only milestone |
| M07 recording | NOT RUN | Not authorized until M06 acceptance |

## Resume point

Account-owner verification must be completed through the official provider UI or its account-recovery/support process. Do not ask for codes, callback URLs or passwords in chat. The user has already reported they cannot complete the current verification; repeating that same request is not a resolution.

Independently, recover Docker's existing Hyper-V engine without resetting data. Once API health returns, use the selected private project environment and native WP-CLI to inspect installed versions, credential-presence booleans, stored application/client-ID presence, seller/site/test flags, gateway settings and native receiver. Never print full options or token-bearing payloads. Confirm ownership and reuse before creation/linking, then run existing preflight, tunnel and provider acceptance sequentially. See [payment-acceptance-report.md](payment-acceptance-report.md).
