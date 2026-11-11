# M01–M03 verification record

M01/M02 execution date: **2026-10-27**. M03 and regression date: **2026-10-28**. **M01, M02 and M03 are complete.** The checks below ran against real services; no commerce mocks were used.

## Runtime and resolved prerequisites

The local site runs at **http://localhost:8080/** with WordPress **7.1.3**, WooCommerce **11.2.1**, PHP **8.3.36** and MySQL **8.4.13**. Docker Desktop 4.12 / Engine 20.10.17 / Compose 2.10.2 use this machine's existing Hyper-V Linux backend. The original unavailable-engine and source-sharing blockers were resolved. This legacy Desktop's WSL2 backend encountered a cgroup incompatibility; its original Hyper-V configuration was restored. The exact project folder was allowed for source sharing. No existing database/application volumes were reset or deleted.

A separate `anotter-m02-installcheck` project on port 8081 verified installation from an empty database. It is stopped, with its volumes preserved. The main demo remains running. Private credentials, runtime data, diagnostic logs and downloaded tooling are excluded from Git and the source archive.

## Executed checks

| Check | Actual result |
| --- | --- |
| `python scripts/dev.py setup` | Passed real core/plugin/theme installation, language setup, seed, M02 migration and native smoke checks |
| Fresh isolated installer | Passed using native `wp_install()` through the CLI-only script; output was checked against all 11 generated private secret values and contained none |
| `python scripts/dev.py verify` | Passed repeated setup with an unchanged managed snapshot; database state and uploaded-image hashes survived stop/start; subsequent smoke passed |
| Expanded snapshot | Includes product prices/stock, galleries, categories/tags, attributes, user/page/media identities, shipping/settings, M02 page mappings and translation fallback |
| HTTP/native commerce smoke | HTTP 200 for home, shop, product, add-to-cart, cart, nonempty checkout, account and Store API; UYU/Uruguay/native settings assertions passed; no PHP fatal/parse errors in the tested request window |
| `python scripts/dev.py wp -- eval-file /opt/anotter/tests/storefront.php` | Passed live M02 page/block/form assertions and preserved M01 defaults |
| `python scripts/dev.py lint` | All 35 application/test PHP files passed syntax checks in the official CLI container |
| `python -m unittest discover -s tests -p "test_*.py" -v` | All 6 host configuration/credential regression tests passed |
| Bash / JavaScript syntax | Setup/lint shell scripts and browser/form-focus JavaScript passed syntax checks |
| `theme.json` | Passed validation against the official WordPress 7.1 JSON schema |
| Real WordPress editors | Site Editor loaded valid template blocks and homepage editor loaded a valid native pattern; no edits saved |
| Source/archive hygiene | LF text conventions, ignored private/runtime content, staged whitespace and secret checks, and archive integrity reviewed before packaging |

The first M01 report recorded an unavailable Docker engine. That historical blocker no longer describes the current result. The installer also replaced the unsafe non-TTY WP-CLI password prompt with a silent environment-based flow, and the local administrator password was rotated. Never print `.env` or expanded Compose configuration.

## Browser and visual acceptance

`npm run test:e2e` passed **14 acceptance groups**, using Chromium/Playwright 1.62.1 against the running WordPress/WooCommerce installation:

1. Home, shop, product, how-to-play, FAQ and contact at **320, 375, 768, 1024 and 1440px**: one main/H1, loaded images and no horizontal overflow (five groups).
2. Header/footer/legal links return HTTP 200.
3. Primary hero media loads eagerly with high priority.
4. Mobile navigation opens by keyboard, exposes expanded state and Spanish labels, receives focus, and restores focus on Escape.
5. Native catalog, descending-price sorting and category discovery work.
6. Product title/current price/UYU match native data; Product structured data and related products exist; quantity-two add-to-cart and quantity-three cart update produce the correct native subtotal.
7. FAQ disclosures open/close by keyboard.
8. Contact client/server validation, nonce rejection, honeypot and honest no-delivery feedback work; feedback receives focus.
9. A separate marked out-of-stock fixture cannot be purchased through either the UI or a forged native add-to-cart request. The fixture is cleaned up; merchant inventory is untouched.
10. No unexpected JavaScript/console errors.

Desktop (1440px) and mobile (375px) home/shop/product captures were inspected, including full pages and homepage first-screen crops. Layout fixes included native product-grid columns, uncropped primary catalog media, duplicate availability removal, Spanish navigation labels, heading hierarchy and the contact POST routing collision. The final captures and machine-readable passing report are distributed in the sibling `m02-screenshots/` folder and screenshot archive:

- `home-1440.png`, `home-1440-top.png`, `home-375.png`, `home-375-top.png`
- `shop-1440.png`, `shop-375.png`
- `product-1440.png`, `product-375.png`
- `results.json` (`passed: true`, 14 groups, no errors)

Calculated contrast ratios for intended text/control pairs passed the applicable AA thresholds: text/background 13.41:1, muted/background 5.78:1, white/primary 9.78:1, primary/accent 6.36:1, error/white 6.77:1, success/white 7.24:1 and focus/background 6.55:1. These calculations and keyboard checks are targeted evidence, not a full WCAG certification.

## Reproduce

From the repository root, with the private local environment present and Docker running:

```powershell
python scripts/dev.py start
python scripts/dev.py lint
python scripts/dev.py wp -- eval-file /opt/anotter/tests/storefront.php
python scripts/dev.py verify
python -m unittest discover -s tests -p "test_*.py" -v
npm ci
npx playwright install chromium
npm run test:e2e
```

Run `verify` and browser tests sequentially on an idle demo: verification restarts this project's services. For a new clone, run `python scripts/dev.py setup` first. Browser captures default to ignored `work/screenshots/`; set an absolute `SCREENSHOT_DIR` to export elsewhere. Node is needed only for testing.

## Known limitations and milestone boundary

- Identity, artwork, game specifications/rules, video and legal/commercial content are original provisional demo material. Official assets, contact details and social destinations were not supplied. Placeholders remain visibly honest.
- Contact validates only: it neither sends nor stores messages. A real delivery implementation and verified transport require a later explicit task. The foundation plugin still suppresses all WordPress mail; SMTP configuration alone does not enable this form.
- Core es_UY is incomplete and WooCommerce does not publish an es_UY pack. The site retains es_UY, uses the installed WooCommerce es_ES fallback, and supplies missing native menu labels through the theme.
- WooCommerce activation/theme-switch CLI operations can emit a nonfatal early-textdomain notice. The tested frontend requests had no PHP fatal/parse errors. Logging was retained.
- Tests used Chromium and targeted keyboard/semantic checks. Broad cross-browser, assistive-technology, load and production security audits belong to M05.
- M03 is complete with offline test orders and illustrative flat rates. M04 Mercado Pago/webhooks/tunnel and M05 broader QA/video preparation remain **pending**. No public deployment, real carrier integration or online payment processing was performed.

## M03 execution — 2026-10-28

- Native Cart/Checkout/confirmation, guest UYU/Uruguay fields, DAC/UES sample flat-rate zones, unpaid BACS orders, HPOS administration and protected local email previews implemented.
- **16 M03 acceptance groups passed**, including keyboard removal/quantity, coupons, validation, real guest order creation, persisted shipping, access denial, duplicate-submission protection, five-width layouts and owned-fixture cleanup.
- **10 repeated last-unit races passed** after a supported native reservation-read filter resolved the observed status-transition visibility gap. A deterministic read-only transition observer also passed. Native reduction/restoration callbacks remain authoritative.
- **All 14 M02 browser regression groups, 45 PHP syntax checks, 6 host tests and both live state suites passed.**
- Repeated setup preserved the expanded M03 snapshot. Database state and image hashes survived stop/start; all eight HTTP/native smoke routes passed with no PHP fatal/parse errors in that request window. Baseline product prices and quantities were preserved.
- Desktop/mobile cart, empty cart, checkout, shipping, errors, confirmation, native order administration and email-preview captures inspected. Narrow cart overflow, sticky-header focus obstruction and wrapped confirmation amounts were corrected.

The original guest coming-soon setting was also resolved for the loopback demonstration. Full results, commands, fixture safety and manual procedures: [testing-m03.md](testing-m03.md). Flow and native inventory details: [checkout-flow.md](checkout-flow.md). Zone/rate configuration and limits: [shipping.md](shipping.md). Offline on-hold orders require cancellation when unused; their stock is not released by the pending-order timer. Exact Mercado Pago release compatibility with WooCommerce 11.2.1 remains an M04 verification task.

**Definition of Done: satisfied for M01–M03. No blocking M03 issue remains. Stop after M03.**

## M04 execution — 2026-10-28

- Official Mercado Pago **8.9.7** installed, activated and verified against official WordPress checksums. Setup pins the version, preserves later settings and keeps third-party code in the named volume. WordPress 7.1.3, WooCommerce 11.2.1, native Checkout Blocks, HPOS, UYU and DEMO shipping were preserved.
- Checkout Pro redirect settings initialized disabled. Missing-account activation refuses without changing gateways. The configured workflow requires an official MLU test seller in the documented sales mode, confirms provider test identity before activation and disables BACS; current local state retains BACS and no MP credentials.
- Two actual Cloudflare Quick Tunnel hostnames exercised. Exact-host public HTTPS, native return/cancel/callback URLs, browser cookie cart and fully hydrated Checkout Blocks checked. Unknown Hosts and forged forwarded headers cannot select an origin. Both tunnels stopped; local configuration restored. The first stopped public endpoint returned HTTP 530; provider retry behavior is not inferred.
- Real official receiver rejected malformed/repeated invalid inputs with HTTP 400/422; owned pending order paid state/stock remained unchanged. Forged browser approval did not complete payment. These negative tests are not genuine payment/notification evidence.
- Final **8 host tests**, project PHP syntax checks, M04 local/public checks, **14 M02 browser groups**, **16 M03 browser groups**, and both live state suites passed. Native lifecycle, coupons/shipping, concurrent last-unit checkout, access controls and administration remain operational. Public desktop and local mobile checkout captures inspected.
- Repeated setup preserved the complete managed snapshot including non-secret MP settings. Database state and uploaded-image hashes survived stop/start. All eight final HTTP routes returned 200, with no PHP fatal/parse errors in that request window. Targeted versionable-file secret scan passed.

**M04 acceptance remains incomplete:** no authorized seller/buyer login or credentials were supplied, so genuine approved/rejected/pending transactions, hosted preference amounts/redirects, provider notifications, valid duplicate handling and provider-driven inventory/status results are blocked. Account-dependent compatibility also remains unverified: the plugin header declares WooCommerce tested through 11.0.1, below our 11.2.1. The inspected official notification code may retain limited card metadata such as last-four digits; a literal zero-card-metadata constraint is not established. No raw card collection/storage is added by this project.

Resolved intermediate mount/test-selector/session issues and the final Passed/Failed/Blocked/Not executed matrix are in [testing-m04.md](testing-m04.md). Manual account/linking steps: [mercadopago-setup.md](mercadopago-setup.md). Infrastructure: [cloudflare-tunnel.md](cloudflare-tunnel.md). Actual inspected IPN/Core flow and limitations: [payment-flow.md](payment-flow.md). M05 has not started; stop within M04.

## Windows workspace migration — 2026-11-05

Copied the working tree and Git metadata to the OneDrive development folder. The snapshot includes existing uncommitted changes. Private main and QA settings were placed under LocalAppData using the same Compose project identities. Historical backups and logs remain in the original local checkout. The runbook records the new startup commands. Docker's Linux API was unavailable during migration, so the new bind mounts and live WordPress startup were not exercised.

The development guide review corrected the main and QA paths, added the existing-volume check and recorded the external backup path. Both selected environment files passed Compose configuration validation from OneDrive. The Linux engine API remained unavailable, so live startup and bind mounts remain unverified.

## M06 initial inspection — 2026-10-31

**Subsequent resource checkpoint:** user restarted Docker and explicitly requested test-account creation. Both Docker contexts still fail. MCP seller creation partially failed on zero fictitious funding; dashboard showed the created MLU seller, so creation was not repeated. Separate buyer creation returned MLU/buyer success with fictitious UYU 1,000 requested. No secrets were printed or written to reports/source. No Developer application, store linkage or payment created. Account-list reload requires another provider verification. Latest results: [testing-m06.md](testing-m06.md); earlier no-creation states below are historical.

**Latest follow-up:** owner verification subsequently completed privately and access to the test-account management page was verified. Page requires test-credential activation and states it automatically creates accounts upon activation. No activation/creation performed; existing WooCommerce ownership must be inspected first. Docker API checks still fail for default and desktop-linux. Earlier owner-verification failures below are historical. Updated details: [testing-m06.md](testing-m06.md).

**Later execution supersedes connection/runtime status in this initial note.** Authenticated MCP listing succeeds with zero applications visible to its owner. Docker Desktop launch, existing Hyper-V VM startup, official local diagnostics and a controlled Desktop restart were executed; Docker API/LinuxKit/proxy remained unavailable. Current native WooCommerce configuration could not be read. Provider test-account access requires owner verification; user authorized the authentication domain but could not complete verification. Twelve host tests and targeted secret scan passed. No resource, account/linkage, payment, event or stock/status mutation; no tunnel/dependency update/reset. See [testing-m06.md](testing-m06.md) for commands and PASS/FAIL/BLOCKED/NOT RUN results. M06 remains incomplete and M07 NOT RUN.

Read project conventions and requested M04/M05/M06 setup/testing/runbook documents; inspected existing official Checkout Pro configuration helpers and Quick Tunnel workflow. Read installed Mercado Pago Codex plugin 1.0.1 integration/connection/test-account instructions and verified its bundled server manifest without copying it. The plugin is enabled in user configuration, but no Mercado Pago tools are callable in this chat; sanitized `codex mcp list --json` lists no standalone Mercado Pago server. OAuth state and application/test-account existence remain unverified. No application/account was created, credentials retrieved, gateway changed or payment attempted. Official WooCommerce test documentation was opened; preserve the separate MLU test seller/buyer sales-mode procedure.

`docker --context desktop-linux compose ps --format json` failed because the Docker Desktop Linux-engine pipe was absent; live versions, plugin integrity/compatibility and payment settings are BLOCKED. No tunnel or regression suite was started. This checkpoint changes documentation only; existing uncommitted application work is preserved. Detailed PASS/BLOCKED/NOT RUN results and the first connection step are recorded in [m06-acceptance-plan.md](m06-acceptance-plan.md). M06 acceptance remains incomplete; M07 is NOT RUN.

## M05 final execution — 2026-10-29

Reviewed WP 7.1.4 security patch applied after private backup/QA staging; WC 11.2.1 and MP 8.9.7 retained. Twelve host tests, PHP lint, official integrity, 33 custom live security assertions, five isolated reset groups, 14 M02 / 16 M03 / five local and public M04 negative groups passed. Per-engine M05 evidence totals 27 groups and 60 axe scans with no violations; focused WebKit reruns and source timestamps are explicit. Main setup/restart/data/image persistence and eight HTTP routes passed; owned fixture accounting is clean. Five-page lab metrics/payload, limitations, screenshot review, runbook/storyboard and M06 gates are in testing-m05.md and the associated reviews. All temporary task tunnels stopped, public origin restored, isolated QA stopped. M05 complete; M04 genuine payment acceptance still incomplete; M06/M07 not executed.

## Docker Desktop startup recovery — 2026-11-10

- **PASS** — Launched the installed Docker Desktop 4.12.0. It started its existing Hyper-V VM and `docker --context desktop-linux version` reported Linux Engine 20.10.17 after about one minute. `docker volume inspect` confirmed `anotter-demo_database` and `anotter-demo_wordpress`. Existing MySQL and WordPress containers resumed and reached healthy status. Their source mounts still point to the original checkout. The Hyper-V checkpoint and its disk chain were left intact.
- **PASS** — The original local environment matched the running Compose project and image pins. Restored a verified copy to the documented private `LocalAppData\AnOtterDemo\main.env` location. `docker compose --env-file $env:ANOTTER_ENV_FILE config --quiet` passed from OneDrive. Both the homepage and `/wp-json/` returned HTTP 200, and `python scripts/dev.py smoke` passed from the original checkout with live WooCommerce assertions, eight HTTP 200 checks, and no PHP fatal or parse errors in the request window.
- **BLOCKED** — `python scripts/dev.py lint` from OneDrive was rejected by Docker Desktop's folder-sharing allowlist. The File sharing panel listed only the original checkout. Granting Docker access to the OneDrive project folder requires the user's local action. Live startup from OneDrive, setup and restart persistence verification were not run in this recovery. No payment acceptance work was performed.

### OneDrive startup follow-up — 2026-11-10

- **PASS** — After the user allowed the project folder in Docker Desktop, Compose config validation and `python scripts/dev.py lint` passed from OneDrive. `python scripts/dev.py start` reused the existing MySQL container and recreated the WordPress container with all six source mounts pointing to OneDrive in read-only mode. Both services became healthy and retained the two named volumes. A private `tests/snapshot.php` comparison matched the recorded database state and uploaded image hashes before and after startup.
- **PASS** — `python scripts/dev.py smoke` and `python scripts/dev.py doctor` passed their local checks from OneDrive. WooCommerce assertions passed, all eight HTTP routes returned 200, and smoke found no PHP fatal or parse errors. Doctor reported Mercado Pago prerequisites and genuine acceptance as BLOCKED and public HTTPS as NOT RUN. The separate `setup` and full `verify` routines were not run.

## M06 read-only configuration inspection — 2026-11-10

- **PASS** — With the private main environment and the `desktop-linux` context, `docker compose config --quiet` and `docker compose start db wordpress` succeeded. Both existing containers were already healthy and stayed healthy. `docker volume inspect` confirmed both named volumes. The homepage, WordPress REST index and WooCommerce Store API products endpoint returned HTTP 200.
- **PASS** — WP-CLI `core version` reported WordPress 7.1.4. `plugin list --skip-update-check` showed WooCommerce 11.2.1 and official Mercado Pago 8.9.7 active. `plugin verify-checksums woocommerce-mercadopago --strict` passed.
- **PASS** — WP-CLI `eval-file /opt/anotter/scripts/php/payments.php status` and a presence-only `wp eval` confirmed saved Checkout Pro redirect and auto-return defaults, Checkout Pro disabled, offline BACS enabled and UYU. The plugin defaults to test mode with no explicit mode saved. No seller collector/site ID, test-seller flag, application/client ID, linking integration ID, public key or access token was present. No public HTTPS origin or custom callback/return URL was configured.
- **PASS** — Authenticated Mercado Pago MCP `application_list` returned zero visible Developer applications for its authorized account. The existing Uruguay test seller's separate application inventory remains unverified. The next action is to inspect that test seller's Developer Dashboard for a reusable Checkout Pro application.
- **NOT RUN** — Seller linking, application creation, gateway changes, public tunnel, test purchases and provider notifications were outside this inspection. M06 acceptance remains pending.

## M06 live execution — 2026-11-10

- **PASS** — `docker compose --env-file $env:ANOTTER_ENV_FILE config --quiet`, healthy Linux Engine services and named-volume checks. `python scripts/dev.py wp -- core version` reported 7.1.4. WooCommerce 11.2.1 and Mercado Pago 8.9.7 were active. `python scripts/dev.py wp -- plugin verify-checksums woocommerce-mercadopago --strict` passed.
- **PASS** — `python scripts/backup.py` completed after replacing its private-directory bind mount with Docker archive streaming. Verified the required archive entries, private config exclusion and MySQL dump completion marker. The original backup attempt's directory share was declined.
- **PASS** — `python -m unittest discover -s tests -p 'test_*.py' -v` (12 tests), targeted secret scan, PHP lint, doctor and local smoke. Smoke returned HTTP 200 on eight routes and reported no PHP fatal or parse errors. A malformed expected seller ID was rejected at CLI validation. Native activation with the expected seller ID was also rejected from the local-only origin.
- **PASS** — `python scripts/dev.py test-m04` on local and public origins. Exact temporary HTTPS, public storefront and Store API, admin login redirect and parallel loopback reachability verified. `payments --mode status` showed the official IPN URL on the public origin and confirmed the store was still unlinked.
- **FAIL (first attempt)** — `npm run test:m04` reached the live receiver and verified rejection without changes to the fixture. The browser then timed out waiting for network idle and its diagnostic included the temporary unpaid fixture's return query. The fixture option was absent after cleanup. The test now waits for the rendered confirmation and redacts queries and order keys from errors.
- **PASS (rerun)** — `npm run test:m04` passed with the linked gateway over public HTTPS. Invalid and repeated requests and the forged browser return left the temporary order unpaid and stock untouched. The cart session and Checkout Blocks rendered on the correct origin. `tests/clean-state.php` confirmed that the temporary fixtures were removed.
- **PASS** — `npm run test:e2e` completed all 14 M02 browser groups at the five required widths. Reviewed mobile home/product and desktop shop captures.
- **PASS** — `npm run test:m03` completed all 16 groups, including native HPOS guest checkout, both Uruguay shipping zones, coupon and inventory lifecycle, and the concurrent last-unit race. Reviewed the mobile checkout capture.
- **PASS** — `python scripts/dev.py setup`, `npm run test:reset` (five groups) and `python scripts/dev.py wp -- eval-file /opt/anotter/tests/security.php` (33 live assertions) in the separate `anotter-m05-qa` project. Its existing environment and volumes were reused, and its services were stopped after testing.
- **PASS** — `python tests/access-logs.py` observed the live Apache probe request path and confirmed its synthetic query string was omitted.
- **PASS** — The operator linked the test seller and saved sales mode. The Mercado Pago identity response matched the expected Uruguay test user, and the stored client ID matched the application shown in the seller dashboard. `python scripts/dev.py payments --mode pro --seller-id $env:ANOTTER_TEST_SELLER_ID` enabled only Checkout Pro and disabled BACS. Live `test-m04` passed. An isolated Chromium cart reached public checkout with HTTP 200 and displayed the demo gateway in UYU.
- **FAIL** — `npm run test:m05` completed 27 browser groups and 60 axe scans. One `aria-allowed-attr` finding was classified serious or critical on WebKit checkout at 375 px. The issue remains open.
- **BLOCKED** — Hosted test buyer purchases, genuine notifications and provider-driven native order and stock changes are pending. See the [M06 acceptance report](payment-acceptance-report.md).

## M06 demo startup — 2026-11-11

- **PASS** — Started the installed Docker Desktop and verified the existing Linux engine and both `anotter-demo` named volumes. `python scripts/dev.py start` and `smoke` passed, including eight HTTP 200 routes and no PHP fatal or parse errors in the smoke window.
- **PASS** — Retired the expired public-origin setting and started a fresh Quick Tunnel. Public home and Store API returned 200. `payments --mode pro --seller-id $env:ANOTTER_TEST_SELLER_ID` confirmed the expected Uruguay test seller and retained Checkout Pro with BACS disabled. `test-m04`, the public M04 browser suite and post-suite fixture accounting passed on the new hostname.
- **PASS** — Recorded the native product baseline before the buyer test. DEMO-CARDS-001 has stock 24 and price UYU 890. DEMO-EXP-001 has stock 12 and price UYU 390. DEMO-ACC-001 has stock 30 and price UYU 250.
- **BLOCKED** — Hosted buyer purchase and provider-driven payment, notification and stock acceptance await the test buyer. The task tunnel remains running for that interaction.


## Public source preparation — 2026-11-11

Scope: source publication hygiene only. Existing uncommitted milestone work is preserved. No GitHub repository/push, provider action, environment write, Docker restart, reset or volume change was performed.

- **PASS** — Documentation replaced actual provider seller/buyer/application identifiers with placeholders and removed the personal checkout path. `.env.example` retains blank secrets and reserved synthetic email. Original artwork and font license notices are preserved.
- **PASS** — Extended `.gitignore` and offline `scripts/check-secrets.py` cover private paths, common token/key formats, emails, home paths, account numbers, order keys, active tunnel hosts, credential assignments and exact selected-environment values. Filename diagnostics with sensitive matches are redacted. Source inspection and an additional redacted literal survey found no further unexpected credential candidates.
- **PASS** — `python scripts/check-secrets.py --env-file <private-main-environment>` checked versionable source against the existing private main environment without printing its values. Credentials stored only in the WordPress database were not read or exported.
- **EXPECTED FAIL / DO NOT PUBLISH LEGACY HISTORY** — `python scripts/check-secrets.py --history --env-file <private-main-environment>` detected personal emails in the three legacy commits, prior account IDs/home paths in reachable blobs and a long-number ref requiring review. No values were printed. Original refs/history remain private and unchanged by these helpers; `.gitignore` cannot remove historical disclosures.
- **PASS** — `python -m unittest discover -s tests -p "test_*.py" -v`: 19 tests, including index-versus-working-file differences, forced private adds, deleted historical material, commit/tag identities, redacted CLI output, ignored-runtime exclusion, anonymous fresh-history creation and refusal to overwrite existing output. The initial new-tool regression exposed Git timestamps being mistaken for account numbers; metadata timestamps were normalized and the final suite passed. Existing guard tests print expected missing-environment diagnostics while passing.
- **PASS** — `python scripts/prepare-public.py --output work/publication-rehearsal --env-file <private-main-environment>` created 141 source files with one anonymous initial commit, a clean index/history and no remote. It included current uncommitted source and excluded the original Git database, private environments and ignored runtime/dependencies. Pre-commit/index and pre-push/history hooks are included. The delivery snapshot uses the helper's default `work/public-repository/` path; see [publishing.md](publishing.md).
- **PASS** — `git diff --check`; additional source search found no remaining previously identified personal username or actual provider account/application numbers.
- **NOT RUN** — WordPress/WooCommerce integration, Docker lint/setup/smoke/restart verification and browser/visual suites: this change modifies only host publication tooling, exclusions and documentation. No application behavior or milestone acceptance was changed. M06 genuine provider acceptance and M07 recording remain pending.

Publication checks are heuristic and require review. Only the fresh anonymous snapshot is intended for upload. No claim of exhaustive secret detection, credential rotation, real payment verification or public publication is made.

Final delivery check: `work/public-repository/` was generated successfully with 141 source files. `python scripts/check-secrets.py --staged --history` passed against the private main environment (139 reachable blobs). Both `git hook run pre-commit` and `git hook run pre-push` passed. Git Bash initially could not create its Windows MSYS named object inside the restricted sandbox (exit 66); the same local, read-only hook checks passed with authorized execution outside that sandbox. No push or network request was performed. This verification entry is included in the anonymous initial commit.


## Root repository publication follow-up — 2026-11-11

The project root is now the repository to publish, as requested; a separate export is optional. `.gitignore` keeps private environments, keys, backups, dependencies, runtime and browser evidence local. Prior complete Git metadata was copied outside the project and OneDrive and verified by hashes; private recovery details are in ignored `work/git-publication-state.json`.

- **PASS** — `python -m unittest discover -s tests -p "test_*.py" -v`: 20 host tests, including numeric editor-ref handling and continued audit of their contents.
- **PASS** — Source/index checks against the private main environment; native `commit-tree`/guarded `update-ref` gave `main` one anonymous root commit containing the 141 current source files. No old-history public branch/tag or remote was added. Working files and ignored data were preserved. Hooks and anonymous identity are configured locally. A background-generated Salesforce metadata cache caused the first native attempt to detect a dirty working tree and roll back safely; `.sf/` and `.sfdx/` are now explicitly ignored and rejected by the scanner if forced into Git.
- **RESOLVED ENVIRONMENT BLOCKER** — Windows denied moving the `.git` directory (access denied). The failed attempts left original metadata intact. The completed native Git approach keeps that directory in place, with a separate verified private backup. No permission bypass or directory deletion was needed.
- **SCOPE** — `--history` checks every local branch, tag and HEAD, which are eligible for normal publication. `--history --all-refs` also audits private editor/recovery checkpoints; it can still report old private local data. Those refs, reflogs and unreachable objects are not sent by `git push origin main`. Never mirror them or upload `.git` as an archive.
- **NOT RUN** — GitHub creation/push and application/Docker/browser regressions. Only publication tooling/documentation and local Git metadata changed. M06 provider acceptance and M07 remain pending.

Earlier instructions requiring publication from `work/public-repository/` are superseded. Use this project root and the [current publication guide](publishing.md).
