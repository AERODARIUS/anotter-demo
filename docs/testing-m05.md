# M05 implementation and verification — 2026-10-29

**Status: COMPLETE for M05.** M04 is implemented but genuine payment acceptance remains incomplete; M06/M07 are not executed. No real payment was simulated or reported as verified. This report distinguishes implementation, measured results and unavailable prerequisites.

## Baseline and environment

The repository was audited before edits: AGENTS/README, architecture/design/roadmap, checkout/shipping, Mercado Pago/tunnel/payment-flow/M04 testing, actual source/Compose/storage/tests. Git contained M04 tracked modifications and untracked files before M05; they were preserved without reset, recreation of the project or an automatic commit. Host Git fsmonitor's IPC error was bypassed with `git -c core.fsmonitor=false` for read-only status/scan/diff operations.

Baseline live environment: WordPress 7.1.3, WooCommerce 11.2.1, PHP 8.3.36, MySQL 8.4.13, foundation 0.4.0/theme 0.3.0, official MP 8.9.7 (WC tested-through 11.0.1). Native Blocks/Store API/HPOS, UYU, Uruguay-only selling/shipping, DAC/UES sample instances and labelled offline BACS. Initial host tests 8/8, PHP lint, smoke (eight HTTP routes), M02 14 browser groups, M03 16 groups and M04 five negative/security groups passed against real services before M05 application fixes.

Windows/PowerShell, Node 24.19.0, Python 3.13; this host needs `DOCKER_CONTEXT=desktop-linux` and authorized Docker engine access outside the restricted shell sandbox. DB/core remain in named volumes, source mounted read-only. Main project uses loopback 8080; separately generated `anotter-m05-qa` uses 8095, new private credentials and independent volumes. Browser contexts are fresh per engine; owned native fixture products/orders/coupons are cleaned after tests. No merchant stock was used for order-producing tests.

During dependency review, the official newly published [WordPress 7.1.4 security release](https://wordpress.org/news/2026/10/wordpress-7-1-4/) justified a deliberate reversible patch. Private native-MySQL dump/core archive were created, archive readability checked (11,900 entries), then QA core/image patched and smoke/checksums exercised before main update. The es_UY package URL was unavailable; official en_US core package was used, preserving the database locale/Spanish packs. Runtime is now **WP 7.1.4**, WC/MP pins unchanged, theme/foundation 0.5.0. Image digest is recorded in the security review. No major dependency upgrade or vendor modification occurred.

## Executable inventory and commands

```powershell
$env:DOCKER_CONTEXT='desktop-linux'
python -m unittest discover -s tests -p 'test_*.py' -v
python scripts/check-secrets.py
python scripts/dev.py lint
python scripts/dev.py smoke
python scripts/dev.py wp -- core verify-checksums
python scripts/dev.py wp -- plugin verify-checksums woocommerce woocommerce-mercadopago akismet
python scripts/dev.py wp -- eval-file /opt/anotter/tests/storefront.php
python scripts/dev.py wp -- eval-file /opt/anotter/tests/commerce.php
python scripts/dev.py wp -- eval-file /opt/anotter/tests/security.php
python scripts/dev.py test-m04
npm run test:e2e
npm run test:m03
npm run test:m04
python tests/access-logs.py
python tests/public-smoke.py # Brief real tunnel; negative checks only, restores local origin.
# Only after browser/public fixtures finish:
python scripts/dev.py verify
python scripts/dev.py doctor
python scripts/dev.py demo-reset --dry-run
```

For isolated M05/reset checks, follow `demo-runbook.md` to create/select QA with absolute `ANOTTER_ENV_FILE`, run setup, `npm ci` and install Chromium/Firefox/WebKit. Run `npm run test:reset`, `npm run test:m05`, security assertions and `python tests/access-logs.py`. Reset test refuses any project except the exact separate QA project. Stop QA after cleanup, clear the environment selector and run final `npm run audit:performance` on idle main services. `M05_BROWSERS=chromium` (or comma-separated engine names) can narrow diagnosis; final combined evidence must include all available engines and mark unavailable ones BLOCKED.

## Results recorded so far

| Check | Status | Evidence / limit |
| --- | --- | --- |
| Host tooling/configuration guards | PASS | 12 tests, including production/staging/public/nonloopback refusal, wrong-project confirmation, mutually exclusive apply/dry-run and invalid session; host subprocess mocks apply only to helper safety, not commerce integration |
| PHP syntax | PASS | All custom theme/plugin/CLI/tests plus project wp-config/public-origin in pinned PHP image; Node syntax and Python compile checks also pass |
| M01 real application / HTTP | PASS | Healthy services, live state assertions, eight HTTP routes and no fatal/parse errors in the exercised request window |
| WP/plugin integrity | PASS | Core checksums before/after 7.1.4; official WC/MP/Akismet checksums; expected extra official-image config and mounted CLI file warnings are documented |
| M02 live + browser regression after patch | PASS | 14 groups: six routes at five widths, landmarks/images, navigation/footer, sorting/category/native product data, quantity/subtotal, keyboard/menu/FAQ, contact nonce/honeypot/HTML/server constraints and out-of-stock rejection |
| M03 live + browser regression after patch | PASS | 16 groups: cart/coupon/input boundaries, both Uruguay zones/carriers/totals, native guest order/admin/permissions, duplicate normal submission protection, last-unit concurrency, stock statuses/refund/expiry and five-width confirmation/cart/checkout |
| M04 local after patch | PASS | Five groups: Host/forwarded boundaries, official malformed/repeated invalid receiver, forged approval remains unpaid, native cookie cart/checkout and diagnostic directory denial |
| Custom security/validation | PASS | 33 live assertions: empty/scalar/array/Unicode boundaries, invalid email/nonce, output escaping/bounded reflection, rate limit, Customer/guest/Shop Manager/administrator capabilities, PHP MIME rejection, protected preview and bounded/no-real-recipient mail capture |
| Selective reset real integration | PASS | Five groups in separate QA: wrong project refused, unknown active order blocks before writes, dry-run unchanged, native selective cancellation/deletion + unrelated data/config preserved, repeat idempotent |
| Direct PHP production refusal | PASS | Separate QA invocation with APP_ENV=production refused before mutation, independently of Python guard |
| Apache log privacy | PASS | Unique container-side probe path observed in real logs; synthetic query absent. Earlier host/window probes did not observe the request and were replaced with an actual observed unique request |
| Main doctor/dry-run | PASS with explicit BLOCKED/NOT RUN rows | UYU, product/image/stock, theme/Woo/plugin, sample carriers/zones, hostname and native HTTP pass; credentials/provider acceptance blocked; no tunnel is NOT RUN when local-only |
| Cross-engine M05 final | PASS | Latest complete evidence per engine: 27 groups / 60 axe scans / zero violations across Chromium, Firefox and WebKit. Combined source timestamps are retained in `work/m05/browser/final-combined.json`; WebKit was rerun separately after synchronization corrections |
| Public HTTPS final regression | PASS | Actual public storefront/doctor, official invalid receiver/forged return/native public cookie checkout passed; tunnel stopped and prior local origin restored |
| Setup/restart persistence after final fixtures | PASS | Repeated setup preserved complete snapshot; DB/upload hashes survived stop/start; eight HTTP routes and log window passed |
| Final lab performance | PASS for measurement execution; limits documented | Five real pages, explicit desktop/cold-cache settings, native hydrated cart/checkout, observed/model metrics and resource payload below; INP/field performance NOT RUN |
| Genuine approved/rejected/pending payments, valid notification replay | BLOCKED — M06 | No authorized test seller/buyer/credentials; installation/invalid events/offline orders do not count |
| Final video | NOT RUN — M07 | Runbook and 4:30 storyboard prepared; no final commercial recording |

PASS means an executed expectation passed. FAIL is an executed unresolved expectation. BLOCKED means a missing prerequisite prevents the relevant test. NOT RUN means not executed; it is never counted as a pass.

## Coverage and test isolation

Real WP/WC integration, HTTP/Store API and native browser sessions supply pricing, inventory, orders, roles and statuses; no commerce/payment mocks establish success. Existing PHP live suites plus 33 new custom assertions exercise practical permission, malformed/boundary/error and state branches. M03 covers native reservations and the status-save/reduction interval. M04 successful seller/preference/notification branches remain untested until M06. CPT stock-query alternative, a full assistive-technology matrix, image-wide CVEs and sustained load are not established here.

PHP Xdebug/PCOV and an instrumented PHP unit runner are not installed. **Measured custom line/branch coverage is unavailable**; no percentage or comprehensive coverage claim is made. PHP lint is syntax validation, not full static taint analysis. Source review and actual negative/branch integration checks complement it. Test-only Node dependencies are locked; no frontend build chain is introduced.

Fixture cleanup now requires actual managed local/development/loopback environment and run identity/explicit marker plus owned product items. Products/coupons are preflighted before cleanup. Public M04 negative tests use raw native autoloaded DB home for this check because an intentional public WP_HOME override must not be mistaken for a remote canonical DB installation. Reset remains forbidden during any configured public origin. Browser results never export order URLs/keys or credentials. A frozen/interrupted test may require ownership-checked cleanup:

```powershell
python scripts/dev.py wp -- eval-file /opt/anotter/tests/commerce-fixtures.php cleanup
python scripts/dev.py wp -- eval-file /opt/anotter/tests/payment-fixture.php cleanup
python scripts/dev.py wp -- eval-file /opt/anotter/tests/stock-fixture.php cleanup
# Only isolated QA and only its owned reset-test ledger:
python scripts/dev.py wp -- eval-file /opt/anotter/tests/reset-fixture.php cleanup
```

Never run a volume reset to clear a test or delete arbitrary order IDs. Never run these suites against production/customer data. Native sessions/transients may persist until WC expiry; completed fixture orders/products/coupons do not accumulate. Close old browser contexts before rehearsal reset; reset deliberately does not mass-delete sessions.

## Defects and resolved execution failures

Application fixes: reviewed WP security patch (P1); closed mini-cart focus, brand label/name mismatch, checkout error association and loading status semantics (P1); safe fixture-cleanup ownership and administrator-only global mail previews (P1 data-safety/privilege risks); useful 404, bounded invalid-form reflection and query-free logs/failure metadata (P2). A real temporary Shop Manager is denied preview access while the configured administrator is allowed; that owned account is deleted after verification. Full reproduction/remediation details are in the security/accessibility reviews.

Initial security test correctly failed on oversized reflected form input before the fix, then passed. Initial axe scans exposed serious/critical findings; none is disabled to make the suite pass. An attempted aria-label wording fix still mismatched text across nested brand spans; the final link derives its name from actual visible content plus hidden Inicio context.

Early M05 harness failures assumed unsubmitted fields always persist after refresh, selected shipping before native department recalculation finished, attempted to fill a native collapsed saved-address form and interrupted WebKit's form navigation by opening Cart too soon. The harness now checks preserved cart, handles visible/native saved address state, waits for actual persisted shipping totals and explicitly awaits native add-to-cart navigation. These are corrected test assumptions, not evidence of paid integration. Final results must be read from the completed run below, not intermediate output.

Backup attempts exposed the Alpine MariaDB/MySQL authentication mismatch and unstable Windows metadata on mounted config files; the final backup uses the matching native client and excludes host mounts. A core-update attempt using unavailable es_UY package failed before retrying with the official en_US binary package. No failed attempt is counted as a pass. The initial public fixture guard rejected the intended public WP_HOME override; the canonical DB check was corrected without relaxing managed/local/production restrictions and the public suite was rerun.

## Demo and M06 handoff

Use [demo-runbook.md](demo-runbook.md) for startup/diagnostics, deterministic data, selective session reset, browser/admin/tunnel/teardown and backup/patch recovery. [video-storyboard.md](video-storyboard.md) is the 4:30 scene/narration plan. [m06-acceptance-plan.md](m06-acceptance-plan.md) requires genuine test-account transactions/notifications/amounts/stock/idempotency and exact WC/MP compatibility. No M06/M07 execution or production-ready claim is made.

Remaining issues by severity are summarized in the final acceptance section after measurements. Security review explicitly records public admin exposure, shared proxy rate limits and unavailable full vulnerability-feed/image scanning. Accessibility review records screen-reader/zoom/forced-colors limitations. Performance review records lab/field and measurement limitations.

## Final measured lab evidence

| Page | Observed trace LCP s | Modeled LCP s | CLS | Lab TBT ms | Document response ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| home | 1.386 | 1.455 | 0.000000 | 5.0 | 664 |
| shop | 0.926 | 1.604 | 0.000000 | 0.0 | 482 |
| product | 1.704 | 1.694 | 0.000000 | 0.0 | 776 |
| cart | 1.795 | 0.430 | 0.003119 | 110.0 | 1462 |
| checkout | 1.284 | 3.005 | 0.002648 | 75.5 | 578 |

| Page | Requests | Transfer KiB | JS decoded KiB | CSS decoded KiB | Images decoded KiB |
| --- | ---: | ---: | ---: | ---: | ---: |
| home | 89 | 919.7 | 212.8 | 330.5 | 60.4 |
| shop | 92 | 926.4 | 213.7 | 329.0 | 67.9 |
| product | 101 | 1063.8 | 291.1 | 341.2 | 169.2 |
| cart | 129 | 860.7 | 2240.0 | 300.5 | 3.2 |
| checkout | 134 | 920.0 | 2455.9 | 348.5 | 3.2 |

Measured on 2026-10-29 with QA stopped, Chromium 151.0.7922.34, Lighthouse 13.6.0, 1440×1000, desktop simulation (40 ms RTT / 10,240 Kbps / CPU multiplier 1), HTTP cache reset, native cookies/localStorage retained. Resource Timing payload is collected separately after actual product hydration. No order is submitted by this audit. One sample per page is not a distribution or capacity benchmark.

Observed trace LCP is actual local browser timing; modeled LCP is Lighthouse's simulated estimate. Both are retained exactly as returned. A trace-engine ancillary insight emitted a `TypeError` during checkout while headline/observed metrics and native hydration assertions completed. Model estimates diverge from observed timings (including cart below measured document response and checkout above the 2.5 s goal); treat simulation as diagnostic, not proof of target acceptance. Observed local LCP is 0.926–1.795 s and CLS ≤0.003119. **INP is NOT RUN**, not inferred from TBT. No field-performance or controlled before/after speedup claim is made.

The first warm-cache/mixed-throttle and early viewport/hydration experiments are retained privately and are not comparable benchmarks. Final instrumentation explicitly resets HTTP cache, fixes the initial viewport and verifies the actual cart/checkout product and final URL. Existing responsive WebP/high-priority hero/font strategy was preserved; the unnecessary emoji path was removed. Native commerce JS payload remains about 2.2–2.4 MiB decoded and merits later profiling. Do not remove stock/session/payment modules or cache personalized pages to improve a score.

## Final acceptance and remaining issues

- **PASS — M05:** implemented fixes/tools/reviews/runbook/storyboard and M06 plan; available M01–M04 regressions, host tests, security assertions, reset, public HTTPS negative checks and restart/idempotency/persistence verified. No unresolved known P0/P1 within the reviewed project scope remains.
- **Browsers:** Chromium 151.0.7922.34, Firefox 153.0 and WebKit 26.5 each passed nine M05 groups and 20 axe scans at the required five widths. The final combined evidence uses completed Chromium/Firefox results from the combined run and the focused WebKit rerun after synchronization fixes; it does not relabel an earlier failed full invocation. Source timestamps are preserved in `work/m05/browser/final-combined.json`. Zero axe violations in these 60 scans is not WCAG certification.
- **Resolved test sequencing:** an isolated QA restart was attempted before its previous teardown completed; it failed readiness without modifying fixture data. It was restarted sequentially, healthy checks passed and WebKit completed. A refresh originally interrupted a native shipping batch; the harness now waits for the keyboard-selected server total and network completion, preserving the runtime-error assertion. Direct diagnostics confirmed native focus eventually reaches email and real UES selection persists on refresh.
- **P0: none known unresolved. P1: none known unresolved** after reviewed core patch, accessibility fixes, fixture ownership and administrator-only mail preview.
- **P2 remaining:** modeled checkout LCP 3.005 s exceeds the optimization goal and Lighthouse's ancillary insight/model has limitations; heavy native commerce JS and the small Docker budget need representative profiling. Short public admin exposure/shared proxy contact-rate limit and unavailable image-wide/authenticated vulnerability scanning remain documented development risks. Full backup restore drill, spoken screen-reader/zoom/forced-colors evaluation and field INP are not established.
- **P3:** no additional blocking cosmetic defect identified in the reviewed captures; WebKit-on-Windows is not a Safari/VoiceOver acceptance claim.
- **BLOCKED — M06:** authorized separate MLU test seller/buyer, privately linked credentials, real approved/rejected/pending transactions, actual notifications/replay/status/stock/amount correlation and exact WC 11.2.1 / MP 8.9.7 account-dependent compatibility. Plugin tested-through WC 11.0.1 remains a risk, not established incompatibility. M04 is not fully accepted.
- **NOT RUN — M07:** no final commercial video. The four-and-a-half-minute storyboard is ready; provider scenes require M06 evidence.

Final state: main WordPress/MySQL remain available on loopback; temporary task tunnels stopped/public origin restored empty; BACS labelled offline, MP plugin active but Checkout Pro disabled without credentials. Owned fixtures were cleaned; QA is stopped with separate volumes preserved. Backups/private environments/runtime logs are excluded from source delivery. Existing uncommitted M04 work is preserved; no automatic Git commit was created. The project is not claimed production-ready or fully payment-accepted. Stop after M05.
