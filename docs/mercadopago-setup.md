# M04 — Mercado Pago setup (test accounts only)

## M06 checkpoint — 2026-11-10

Reuse the existing Uruguay test seller and buyer. The live installation had no saved seller, application/client ID, linking ID or credential pairs before M06 onboarding. The Mercado Pago MCP application inventory represents its authorized account. Seller ownership must be established in the seller's own browser session. See [current execution evidence](testing-m06.md) and the [acceptance report](payment-acceptance-report.md).

The Checkout Pro test guide describes creating an application under the test seller before linking. The current Developer Dashboard route for an ecommerce platform sends WooCommerce users to platform onboarding and says the platform creates the application. Version 8.9.7 starts that onboarding with a WooCommerce integration request without a manually entered application ID. After seller authorization its callback retrieves credential pairs and saves the validated client ID. Start with that official WooCommerce flow and check the resulting application in the test seller's dashboard. If Mercado Pago explicitly requires manual registration, confirm which application type it accepts before creating one. See the [WooCommerce test guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test) and [plugin linking guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-configuration/plugin-configuration).

The official plugin is pinned to **8.9.7**, selected from the [WordPress directory](https://wordpress.org/plugins/woocommerce-mercadopago/) on 2026-10-28. `python scripts/dev.py setup` installs it with WP-CLI, verifies official checksums, activates it and initializes Checkout Pro **disabled**. Reruns preserve settings. A version mismatch stops setup; it never silently upgrades a plugin. Downloads live in the WordPress named volume, not Git or an image.

Installed baseline: WordPress 7.1.4, PHP 8.3, WooCommerce 11.2.1, AnOtter native block theme, Cart/Checkout Blocks and HPOS. Plugin source declares both compatibility flags, and its header says WooCommerce tested through **11.0.1**. The local registration and Blocks/HPOS checks passed. Credential-dependent behavior still requires genuine test payments and notifications.

## Private account actions

Follow the current [WooCommerce Checkout Pro testing guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test), rather than Checkout API instructions:

1. Reuse the existing separate Uruguay test seller and buyer. The [Developer Dashboard](https://www.mercadopago.com.uy/developers) has the test-account management controls. Keep passwords and verification codes private.
2. Sign into the **test seller** in a private browser session and check for a suitable existing WooCommerce registration. The account owner completes any verification challenge privately.
3. Start `python scripts/dev.py tunnel`. Keep it running. Open the printed HTTPS origin, then native WordPress administration. The local administrator password is in the private `.env`; do not put it in chat, scripts or screenshots.
4. Under **WooCommerce → Mercado Pago → Vincula tu tienda**, start the official account-linking flow. Explicitly select the **test seller**, not the authorized developer's real account. Complete OAuth/login/security checks privately. This work does not authorize linking a real merchant.
5. In **Prueba tu tienda antes de vender**, choose **Modo de ventas (producción)** for this **test seller**. This is the Checkout Pro test-account procedure; Checkout API's standard test-mode toggle is a different flow. Never switch to a real seller to obtain notifications.
6. Review the official Checkout Pro settings below, then run `python scripts/dev.py payments --mode pro --seller-id $env:ANOTTER_TEST_SELLER_ID` for the existing M06 test seller. The command privately calls `/users/me`, requires the provider's `test_user` tag and MLU site, and matches the expected seller ID before enabling Checkout Pro and disabling BACS. It rejects local-only URLs, non-UYU stores, missing test-seller identity and custom callback/return URLs.
7. Sign into the separate **test buyer** in another private session and open the public store there. Use that account's synthetic contact information, Uruguay shipping, and only the provider's current test payment methods. Do not use real cards or real customer information.

## Official Checkout Pro settings

| Setting | Demo configuration |
| --- | --- |
| Gateway | `woo-mercado-pago-basic` (Checkout Pro) |
| Title | `Mercado Pago — DEMO · Checkout Pro` |
| Experience | Redirect (`method=redirect`) |
| Currency / conversion | UYU; conversion disabled |
| Auto return | Enabled; official preference uses approved return |
| Success / failure / pending overrides | Empty; official plugin creates per-order native URLs |
| IPN custom-domain override | Empty; derive receiver from current public origin |
| Gateway discount / commission | Zero; native WooCommerce coupon/shipping totals remain authoritative |
| Instant-only / binary mode | Initially off; pending scenarios depend on methods actually offered |
| Other Mercado Pago gateways | Not eligible in this demo |
| BACS | Disabled during MP demonstration; available explicitly for offline regression |
| Plugin verbose debug | Initialized off; review private logs before enabling |

The [official Checkout Pro settings guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/payments-configuration/checkout-pro) explains these fields. Do not introduce extra discounts, conversion, custom frontend card forms or a second preference builder.

Check safe status with `python scripts/dev.py payments --mode status`. Run `python scripts/dev.py test-m04` and `npm run test:m04` before attempting a purchase. Public CLI diagnostics explicitly use the public `--url`; ordinary local WP-CLI retains local URLs. Keep WooCommerce's original order-key returns; do not paste order keys into reports.

## Credentials and safeguards

Use official linking and official settings only. Plugin credentials are stored in WordPress options in the private database volume. They are not added to `.env`, source, Docker images or JavaScript. The plugin itself may expose its **public** key as required by its frontend; access tokens must remain server-side. Do not export full options, raw API responses or DB backups into source/output packages.

Card entry stays on the hosted Mercado Pago page; this project adds no PAN/CVV form or storage. Inspected official notification code can retain limited payment-method metadata, including card last-four digits. That official behavior has not been exercised without accounts and is not overridden. If acceptance requires literally zero card-related metadata in WordPress, this is an unresolved constraint to review with Mercado Pago rather than a reason to patch vendor code or claim compliance.

The official plugin's test-user and MLU site flags drive runtime eligibility. The CLI activation check additionally verifies live provider identity; it is not a custom payment gateway or an alternate production switch. Relinking an account must be followed by this check. Do not hand-edit private options/identity flags to bypass it. Real-account identity or non-public requests remove MP from the available gateway list. These safeguards reduce accidental real-account activation; they do not protect against an administrator deliberately rewriting application/database configuration.

`python scripts/dev.py payments --mode offline` disables Checkout Pro and restores labelled BACS without bank details. Use it when credentials are unavailable and before M03 tests. Public and local sessions are separate; do not transfer session cookies manually. All orders/products retain DEMO labels and all WordPress email stays suppressed.

## Notifications and URL changes

The plugin generates the callback from `WC()->api_request_url('WC_WooMercadoPago_Basic_Gateway')`, adding `source_news=ipn`. With pretty permalinks the expected path is `/wc-api/WC_WooMercadoPago_Basic_Gateway/?source_news=ipn`; the equivalent query route is `/?wc-api=WC_WooMercadoPago_Basic_Gateway&source_news=ipn`. Use the actual output from `payments --mode status` for this installation.

The preference carries the receiver automatically when the public site URL is valid. The [WooCommerce IPN guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-configuration/notifications) also describes seller-side IPN event settings. In the **test seller** dashboard inspect current notification delivery/event configuration if automatic preference notifications do not arrive. Do not paste a generic webhook secret into an unrelated endpoint or populate the plugin's custom URL with just a homepage. A developer integrator ID is not fabricated or required by this project.

Every new Quick Tunnel hostname requires fresh checkout attempts and inspection of public checkout, success/failure/pending and notification URLs. Existing preferences retain old endpoints; restoring connectivity under a different hostname does not repair them. If a dashboard URL was configured manually, update it there as well. The exact emitted notification path and whether this seller flow sends it must be observed from a real test purchase.

## Approved and rejected purchases

Use the current [Uruguay test-card/outcome instructions](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/additional-content/your-integrations/test/cards) in the hosted Mercado Pago page. Choose its documented approved or rejected holder/status scenario; do not enter card data into WordPress. Do not save card numbers in project reports. If a method or challenge does not support automated entry, finish it manually.

For each purchase compare the provider's authoritative payment record with the native order's currency, total including DAC/UES shipping, coupon effects, quantities, payment method, reference, notes, paid date and status. For physical seeded products inspected source predicts approved → processing; actual results remain unverified. Rejected should remain unpaid; the exact plugin path may leave a pending merchant order until a definitive notification. Pending/in-process is unpaid; on-hold can natively reduce stock without being paid. See [payment-flow.md](payment-flow.md) and the full [scenario matrix](testing-m04.md). Never mark an order paid to complete an acceptance checkbox.

## Troubleshooting

| Symptom | Inspection / supported action |
| --- | --- |
| MP absent | Confirm active plugin/version, UYU, public HTTPS origin, official MLU test-seller identity and sales mode; run safe status and `pro`; inspect Checkout Blocks network errors |
| Linking fails | Verify private test-seller login, application and OAuth return origin; complete user security challenge; never weaken authentication |
| Invalid credential / SSL | Re-link the test seller; check container clock, CA trust and outbound HTTPS; never disable certificate verification |
| Redirect to localhost | Check `PUBLIC_SITE_URL`, exact request Host, native return defaults and stale preference; create a fresh public cart/attempt |
| No callback / unchanged order | Keep tunnel alive; inspect callback status, test-seller delivery history, WooCommerce logs and order notes; compare authoritative payment state; do not force paid status |
| Stock unexpected | Compare reservations, native reduction flag, status history, cancellations and refunds; pending and on-hold have different stock behavior |
| Mixed content | Inspect the requested asset origin and saved editor customization; runtime render rewriting is limited to the configured local origin; never globally replace DB contents |

Use WooCommerce → Status → Logs, native order notes, official plugin diagnostics, `docker compose logs wordpress` and ignored `work/m04/cloudflared.log`. Restrict logs to the developer; debug remains off by default and Apache blocks their download. Before sharing excerpts remove Authorization/Bearer values, access/refresh tokens, OAuth codes, passwords, cookies, order keys, account identifiers and personal/card data. The safe CLI status prints only booleans and non-secret URLs. Plugin diagnostics can contain payment/customer data: do not publish raw logs or screenshots. No extra payment dashboard/service is added.
