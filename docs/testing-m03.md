# M03 testing and manual verification

Execution date: **2026-10-28**. Real local WordPress 7.1.3 / WooCommerce 11.2.1 / HPOS / PHP 8.3.36 / MySQL 8.4.13 services are used. Chromium/Playwright 1.62.1 exercises the installed application; no cart, order, stock or shipping response is mocked.

## Baseline and fixes

The initial M01 native smoke checks and six host tests passed. The first M02 browser baseline found WooCommerce's guest **coming-soon mode** enabled: anonymous shop visitors received a holding page. Disabling that setting for the loopback demo restored access, after which all **14 M02 browser groups passed**. M03 initialization records the intended guest visibility. This does not publish the site externally.

M03 visual checks found native cart line totals overflowing at 320px. A theme-only two-column mobile item layout resolved that issue. During test development, assertions were aligned with native behavior: add-item returns HTTP 201, new zones select new method instances, unavailable rate IDs fall back safely to a real rate, and the submit button's text changes while processing. Test cleanup now queries `shop_order` explicitly and handles refunds separately. These test-development failures are not counted as passing final runs.

A repeated concurrency run also exposed a real intermittent last-unit oversell between the native order-status save and stock reduction. A supported reservation-read filter closes that visibility gap without changing native stock writes. The lifecycle suite observes the exact transition window deterministically; repeated two-customer races verify it under real requests. See [checkout-flow.md](checkout-flow.md) for the version-specific reasoning. The commerce header scrolls normally so native validation focus remains clear of the storefront's desktop sticky header.

## Commands

Run sequentially from the repository root on an idle local demo:

```powershell
python scripts/dev.py start
python scripts/dev.py lint
python -m unittest discover -s tests -p "test_*.py" -v
python scripts/dev.py wp -- eval-file /opt/anotter/tests/commerce.php
python scripts/dev.py wp -- eval-file /opt/anotter/tests/storefront.php
npm ci
npx playwright install chromium
npm run test:m03
npm run test:e2e
python scripts/dev.py verify
```

`verify` restarts this project's two services. Never run it during a browser suite or while editing demonstration orders. The fixture cleanup must finish before verification snapshots are taken.

`SCREENSHOT_DIR` can specify an absolute export folder. M03 defaults to ignored `work/m03-screenshots/`; M02 defaults to ignored `work/screenshots/`. `results.json` records the passing groups, errors and failure details. Failed-run screenshots are removed only after a successful M03 run, so final exports contain the completed workflow.

## Automated scenarios

| Area | Real checks |
| --- | --- |
| Foundation | UYU, Uruguay-only destinations, actual 19 departments, native blocks, HPOS, noindex, guest checkout, stock/phone/tax settings, safe mail suppression |
| Cart API | Add multiple products, exact native subtotals, update, remove, empty cart, negative/excess quantities, zero stock, sold-individually limit, variation stock |
| Cart browser | Keyboard quantity and remove controls, native recalculation, reload/cookie-session persistence, checkout link and empty-state CTA |
| Coupons | Native restricted 10% test coupon, invalid/expired/exhausted rejection, shipping retained, stock restrictions still enforced |
| Shipping | Montevideo priority, Canelones/interior rates, DAC/UES selection and cost differences, safe unavailable-rate fallback, foreign country rejection, persisted order method |
| Server validation | Missing required name/postcode/phone, malformed email, unsupported country, invalid department and stale expected total |
| Client validation | Native accessible field errors and `aria-invalid`, Uruguay labels/options, actual department/carrier UI changes |
| Guest order | Native checkout POST creates exactly one unpaid on-hold BACS order; totals match the reviewed cart; customer ID is zero; shipping, notes and stock reduction persist |
| Duplicate submission | Real checkout request is temporarily held, never replaced; native submit control is disabled while in flight; retrying a completed cart does not create another successful order |
| Confirmation | Native order-received blocks, unpaid/status explanation, quantities, total/shipping/payment, no personal details in URL; missing/invalid keys deny address access to another browser session |
| Administration/email | Real wp-login and native HPOS order screen, items/shipping/status/notes, protected local mail capture with generated native email previews |
| Concurrency | Two independent real Store API carts submit the same last unit concurrently; exactly one succeeds and final stock is zero |
| Lifecycle | Pending reservation, on-hold reduction, processing/completed without duplicate reduction, cancellation restoration, failed-order restoration in this installed version, native refund with explicit restocking |
| Expiration | Fixture order is aged through public order date setters, reserved natively, then processed by `wc_cancel_unpaid_orders`; a temporary test filter limits cancellation to that fixture, preserving other orders; reservation releases without physical-stock inflation |
| Layout/errors | Cart/checkout/confirmation at 320/375/768/1024/1440px, one main/H1, labelled fields, no horizontal overflow, no unexpected JS errors |

Fixtures are hidden, marked products and restricted coupons. They use synthetic **PRUEBA / NO ENTREGAR** identity/address text, non-contactable `.invalid` email and a zero-filled test phone. Postal values are validation input, not claims that an address exists or can receive delivery. No business contact data, recipient identity or authorized pickup location is invented for the storefront. Existing seeded product images/prices are read for realistic test presentation; their stock is never modified.

Cleanup proves each order contains only owned fixture products, transitions it through native cancellation where needed, and deletes owned orders/refunds/products/coupons through CRUD. It does not delete shared artwork, merchant orders or arbitrary customer records. If interrupted:

```powershell
python scripts/dev.py wp -- eval-file /opt/anotter/tests/commerce-fixtures.php data
# Review ownership, then clean only this suite's fixtures:
python scripts/dev.py wp -- eval-file /opt/anotter/tests/commerce-fixtures.php cleanup
```

## Manual walkthrough

1. Open the storefront as a guest. Add two different products, increase/decrease quantities, remove one, reload, and verify the cart persists and native totals change.
2. Empty the cart and follow **Explorar juegos**. Add an in-stock item and proceed to checkout.
3. Submit without required fields; verify readable field-linked native errors. Complete Uruguay details using authorized test data, select Montevideo and compare DAC/UES prices. Switch to another department, review the new zone's first selected rate and choose UES.
4. Review quantities, discount (if a native coupon was created), shipping and total. Taxes should remain unconfigured. Confirm the provisional terms. Choose the clearly labelled offline DEMO method, then submit once.
5. On confirmation verify DEMO number/date, products/quantities, shipping/payment, total and **payment unverified / on hold**. Do not transfer money. Open a URL with missing/incorrect key in another session; customer address must not be disclosed.
6. Log in using private `.env` credentials. Open WooCommerce Orders: inspect items, shipping, status and notes. In Products → Inventory inspect current stock, no-backorder policy and low-stock threshold. Cancel the unused offline order and verify native stock restoration. Never mark a real order paid just for a demonstration.
7. In WooCommerce → Correos DEMO inspect the native email design and clear local previews if desired. No external email should arrive. Order generation and email preview are not payment/delivery confirmation.
8. Repeat cart/checkout/confirmation with keyboard and at all five target widths. A broader screen-reader/cross-browser audit remains M05 work.

## Result record and remaining limits

**Final result: PASS. M03 is complete.** The final `npm run test:m03` exited successfully with **16 acceptance groups**, no unexpected browser errors and successful owned-fixture cleanup. The ten repeated two-customer races also passed: each produced one successful order and one rejection, followed by verified native cancellation/restoration. The lifecycle suite additionally proved reservation visibility during the status-save/reduction window.

| Final check | Result |
| --- | --- |
| M03 browser/Store API suite | 16 groups passed; final capture report `2026-10-28T16:58:42.745Z` |
| Repeated last-unit diagnostic | 10/10 real two-customer rounds passed, native restoration after every round |
| M02 browser regressions | All 14 groups passed on the M03 installation |
| PHP syntax | All 45 files passed in the official CLI container |
| Host regression tests | All 6 passed |
| Live M03 / M02 assertions | Both passed |
| Repeated setup / stop-start | Complete managed snapshot and uploaded-image hashes preserved; all eight native HTTP routes passed |
| Merchant data | All three baseline product prices, stock quantities, tracking and backorder policies preserved; test products removed |
| Visual review | Cart, empty cart, checkout, shipping, errors, confirmation and native administration inspected; mobile currency amounts remain on one line |

Final exported screenshots are in the sibling `m03-screenshots/` folder and screenshot archive: `cart-{375,1440}.png`, `empty-cart-{375,1440}.png`, `checkout-{375,1440}.png`, `shipping-{375,1440}.png`, `confirmation-{375,1440}.png`, `validation-1440.png`, `order-admin-1440.png`, and `mail-capture-1440.png`. Passing manifests are `results.json`, `m02-regression-results.json` and `race-results.json`. Captured order records are isolated fixtures and are deleted after testing; the images document the executed workflow, not retained customer orders.

The store and database remain running locally. No blocking M03 issue remains. All seven native statuses are retained; broader M04/M05 work has not begun.

No real DAC/UES quotes, tracking, labels, pickups, online payments or production tax policy are tested or implemented. Native postcode acceptance does not verify a real address or carrier coverage. The pending-stock timer does not expire BACS on-hold orders. Browser testing uses Chromium with targeted keyboard/semantic checks, not a complete WCAG or cross-browser certification. Exact Mercado Pago/WooCommerce version compatibility is deferred to M04, with official architectural support documented in [checkout-flow.md](checkout-flow.md).
