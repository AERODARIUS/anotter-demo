# Project conventions

## Scope and architecture

- This is an independent AnOtter Game **demonstration**, never an official store. Keep fictional products and all orders visibly marked as DEMO. Do not download proprietary logos, packaging or art without authorization.
- Implement only the milestone explicitly requested. M01–M03 supply storefront/native commerce; M04 adds official MP/temporary HTTPS. Its provider acceptance remains incomplete without genuine test purchases/notifications. M05 prepares QA/demo tooling; M06 acceptance and M07 recording remain pending. Never activate real merchant payments or carrier integrations.
- Use official WordPress/Apache, MySQL and WP-CLI Docker images. Keep named database/core-content volumes. Do not expose the DB port or add a public DB admin service.
- Use native WordPress and WooCommerce APIs, hooks, CRUD and block features. No headless frontend, React storefront rewrite, custom commerce tables or core/vendor modifications.
- Theme presentation lives in `theme/anotter-demo`; business/demo policy lives in `plugins/anotter-demo-foundation`. Setup is CLI-only under `scripts/php`. Prefer no new dependency unless it materially solves the current milestone.

## Files, credentials and data safety

- Never commit `.env`, secrets, production credentials, core, downloaded vendor plugins, uploads, database files, generated output or runtime content. Check `git status` and `.gitignore` before staging.
- `python scripts/dev.py setup` is the canonical cross-platform entry point. Use Python standard library on the host and official WP-CLI in containers, UID/GID `33:33`. Keep host source mounts read-only and shell files LF.
- Do not print expanded Compose config or secrets. Use `docker compose config --quiet`. Do not pass passwords through command-line arguments.
- Initialization must remain idempotent. Preserve prices, stock, user/page identities and merchant changes on rerun. SKUs and initialization markers are stable. Never auto-adopt an unrelated installation or silently change canonical URLs.
- Destructive reset requires an explicit user request or the documented exact-project confirmation flag. Never delete volumes to fix a routine failure or run reset as a test.
- Read README upgrade/URL migration caveats before changing pins. Never silently upgrade persistent WordPress core or WooCommerce. Review security patches deliberately; image tags are version pins, not immutable digest locks.

## Coding practices

- PHP 8.3+, WordPress coding conventions (tabs, spaced parentheses, prefixed functions, translation-ready strings). Escape at HTML output; sanitize/validate external input; use nonces **and** capabilities for future privileged web operations.
- Every web-loaded PHP file needs an ABSPATH guard. Never add a web endpoint for setup scripts.
- Use WooCommerce CRUD for order/product writes and declare actual HPOS/block compatibility. Preserve `/wp-json/`, Store API, `wc-api` and webhook callbacks.
- Preserve the M02 design tokens, original provisional artwork, accessible native navigation, semantic landmarks, mobile-first CSS, visible focus and reduced motion. Keep PHP/JS business logic out of presentation patterns. Do not introduce a frontend build chain.
- Demo plugin currently suppresses all WordPress mail and labels every order. Review this policy before future use outside the prototype. No real payment credentials or processing in M01.

## Checks and reporting

- Run `python -m unittest discover -s tests -p "test_*.py" -v` for host tooling changes.
- With Docker available, run `python scripts/dev.py lint`, `setup`, `smoke` and `verify` as appropriate. `verify` temporarily stops only this project's services and compares persisted data and uploaded image hashes. Do not concurrently edit the demo while it runs.
- Real-service verification is required for M01 acceptance. Never substitute mocks for WordPress/WooCommerce integration results or claim unexecuted checks passed. Record executed commands, results and environmental blockers in `docs/verification.md`.
- For theme changes, inspect home/shop/product/cart/checkout at desktop and mobile sizes plus keyboard navigation when a browser and running services are available. Do not claim visual QA from source inspection alone.
- Update README, architecture and roadmap when behavior or scope changes. Mark future milestones pending. Report implementation separately from Definition of Done verification.

## M02 design and browser conventions

- See `docs/design-system.md` and `docs/artwork-prompts.md`. Retain legacy palette aliases for saved blocks. Use self-hosted/system fonts and original authorized assets; keep the independent-demo disclosure visible.
- HTML WooCommerce templates compose the pinned native block APIs. Recheck supported attributes and the real rendered application after upgrades. Do not replace native mini-cart state, quantity handling, stock validation, product queries, sorting, variations or structured data.
- Product copy, prices, attributes, images and related items come from WooCommerce. Stable SKUs identify demo fixtures; do not create a second catalog in frontend JavaScript/JSON.
- The contact form validates only. Do not claim email delivery or retain messages without an explicitly requested delivery implementation and verified transport. Prefix POST fields to avoid reserved WordPress query variables. Retain nonces, bounds, escaping, honeypot, rate limit, accessible errors and honest feedback.
- Never use WP-CLI's non-TTY `--prompt` for passwords: it echoes input. Use the silent installer / explicit environment-based password-reset script. Never print credential-bearing return values or expanded Compose configuration.
- `npm ci`, `npx playwright install chromium`, `npm run test:e2e` run the real browser suite; Node is test-only. Test all five widths (320/375/768/1024/1440) and inspect exported home/shop/product screenshots. Save generated captures under ignored `work/` or an explicitly requested external output directory.
- The browser suite creates one marked temporary out-of-stock product and cleans up only that fixture. Never alter existing product stock to simulate an out-of-stock case. Run tests on an idle local demo.
- Keep M01 smoke/idempotency/persistence checks passing after M02 content migration. Never restart services while browser tests are running.

## M03 commerce conventions

- Read `docs/checkout-flow.md`, `docs/shipping.md` and `docs/testing-m03.md`. Preserve native Cart/Checkout blocks, Store API, guest validation, coupons, HPOS, order keys, reservation locks and inventory lifecycle. Never add custom stock mutations/status handlers or mark a payment complete from a return-page visit. Retain the documented native reservation-read filter until upgrade verification proves it unnecessary; test its status-transition window and real concurrent checkout.
- Sample shipping defaults have one source: `includes/demo-config.php` in the foundation plugin. Native WooCommerce instances are the runtime source. Preserve the Montevideo-before-country zone order and subsequent admin edits. DAC/UES names must say DEMO; no carrier API, official tariff, dispatch or delivery claim exists.
- Native BACS is test-only, without bank accounts. On-hold is unpaid and does not expire with the pending-stock timer. M04 must explicitly disable it when replacing the test gateway.
- Every outgoing WordPress email stays suppressed. Capture only reserved `.invalid` recipients locally, bounded to ten previews; require both `manage_woocommerce` and `manage_options`, plus nonces for clearing. Never confuse preview generation with delivery.
- `npm run test:m03` uses isolated hidden products/coupons/orders, native status transitions and real concurrent Store API requests. Cleanup must prove ownership, cancel through native callbacks, and use WooCommerce CRUD. Never change merchant stock, SQL order internals, global tax rates or gateway notification state to force a test result.
- Keep both prior suites passing. Run restart verification only after browser fixtures are cleaned up. Retain screenshots for cart, empty cart, checkout, shipping, errors, confirmation and native administration at requested widths.

## M04 payments and public origin

- Read `docs/mercadopago-setup.md`, `docs/cloudflare-tunnel.md`, `docs/payment-flow.md` and `docs/testing-m04.md`. Pin/checksum-verify official Mercado Pago 8.9.7; never patch/fork/vendor it or add another gateway, preference builder, callback receiver or synchronization service.
- Checkout Pro tests use separate Uruguay test seller/buyer accounts and the WooCommerce-specific sales-mode procedure. Never infer test-account identity from an access-token prefix; `payments --mode pro` checks provider `/users/me`. Keep credentials in official plugin storage and logs private. Do not print full options, expanded Compose config, provider payloads, order keys or credentials.
- Only exact `PUBLIC_SITE_URL` HTTPS Quick Tunnel hosts are trusted. Loopback-only Apache is the boundary; ignore arbitrary forwarded headers. Preserve local DB URLs, native sessions, order access controls, Blocks, HPOS and M03 stock management. New hostnames require new carts/payment attempts and inspection of callback/return URLs.
- BACS must be disabled for MP demonstrations. Restore with `payments --mode offline` before M03 regression tests. MP stays unavailable without the official test-seller flag, MLU site, documented sales mode and public request. Do not use this demo eligibility filter as a substitute for authoritative payment verification.
- Invalid synthetic requests prove rejection/accessibility only. Never call `payment_complete`, change order statuses or fabricate provider events to claim payment verification. Valid duplicates, amounts, transitions and stock need a genuine test transaction. Inspect both IPN/Core paths in the pinned plugin; do not claim HMAC validation for the inspected receiver.
- Run host tests, PHP lint, checksum checks, `test-m04`, `npm run test:m04`, both prior browser suites and idle restart verification. M04 fixture cleanup uses ownership-checked CRUD. Record Passed/Failed/Blocked/Not executed honestly. Genuine provider acceptance is reserved for M06.

## M05 QA and rehearsal conventions

- Read M05 testing/reviews/runbook/storyboard/M06 plan. Stop after M05; no fake provider success or final video.
- WP 7.1.4 is a reviewed security patch with private backup/isolated QA. Existing volumes require explicit native update, not just a tag/setup change. Do not silently advance WC/MP pins. Preserve Spanish settings when using the available en_US core package.
- `ANOTTER_ENV_FILE` selects an absolute private environment for every helper/test login. `scripts/init-qa.py` creates separate anotter-m05-qa volumes/credentials at loopback 8095. Never print/package environments, backups, full options or provider data.
- Shared fixture guard requires managed local/development and loopback DB canonical URLs. M03 cleanup requires test identity/marker AND owned-only items; preflight product/coupon ownership. Never run shared fixture suites concurrently in one database.
- `demo-reset` defaults to dry run; writes need `--apply --confirm-project EXACT_NAME`. Reject production/staging/public exposure, unknown active orders and changed ownership. Delete only unpaid BACS orders explicitly tagged with reserved email/session/products. Never treat `_anotter_demo` alone as deletion authorization. Native cancellation/CRUD only. Reset integration runs only in the separate exact QA project.
- Doctor exit 0 may still contain BLOCKED payments. Credential presence is not payment validation. M06/M07 remain pending.
- `npm run test:m05` covers three engines/five widths/real offline checkout/axe. Keep captures ignored, trigger lazy media before export and inspect actual renders. Do not suppress serious rules to pass.
- Workarounds address confirmed WC 11.2.1 mini-cart/error/skeleton semantics only; native focus/validation/totals/order state remain in charge. Recheck after upgrades. Accessible brand names must include visible text.
- Performance uses exact recorded lab settings and real payload, not field INP. Xdebug/PCOV absent: no invented coverage. Access logs omit query/Referer; failure reports omit URL queries. Preserve callbacks/Store API.
