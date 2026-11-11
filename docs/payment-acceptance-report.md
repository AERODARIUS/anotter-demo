# M06 Mercado Pago payment acceptance

**2026-11-10 · BLOCKED on payment acceptance.** The Uruguay test seller is linked and verified. Checkout Pro is available on the public demo. Genuine approved and rejected transactions and their notifications are pending.

## Application and account decision

The Uruguay WooCommerce test guide instructs the operator to register an application as the test seller and then link through WooCommerce. The Developer Dashboard's ecommerce platform route told the operator that WooCommerce creates the application during onboarding. The generic Developer Dashboard instructions describe manual registration but do not identify a manual Checkout Pro application type for this WooCommerce test case. See the [WooCommerce test guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test), [WooCommerce overview](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/overview) and [Developer Dashboard guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/additional-content/your-integrations/dashboard).

The operator linked the seller through the official WooCommerce flow. The seller dashboard then showed automatically created WooCommerce application `TEST_APPLICATION_ID`. Its application number matched the plugin's stored client ID, and the provider's `/users/me` response matched the expected MLU seller and test-user flag. Installed 8.9.7 source shows the plugin exchanges its onboarding integration ID and verifier for the validated credentials. See the [plugin linking guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-configuration/plugin-configuration).

Use the existing Uruguay test seller `TEST_SELLER_ID` and separate test buyer `TEST_BUYER_ID`. The authorized MCP account's application list does not identify the seller's registrations. The MCP was used for Uruguay-specific documentation. No application was created through it. The test buyer's UYU 1,000 fictitious funding was requested during account creation. Independent balance reconciliation remains pending. See the [test accounts guide](https://www.mercadopago.com.uy/developers/es/docs/checkout-pro-preferences/test-accounts).

The operator saved sales mode for the linked test seller. `payments --mode pro --seller-id $env:ANOTTER_TEST_SELLER_ID` reverified the exact seller, saved the native redirect and auto-return configuration, and disabled BACS. A fresh browser cart reached public checkout and displayed the demo Checkout Pro option in UYU. The next evidence comes from the separate test buyer using the official `APRO` and `OTHE` test card scenarios. See the [WooCommerce test guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test) and [Uruguay test card scenarios](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/additional-content/your-integrations/test/cards).

## HTTPS and notifications

The project helper verified a temporary Quick Tunnel to loopback Apache using the exact assigned HTTPS host. Public home, Store API and login returned 200. The local origin remained available. Mercado Pago requires HTTPS return URLs for Checkout Pro preferences and warns against loopback addresses. The official plugin's Checkout Pro IPN route is `/wc-api/WC_WooMercadoPago_Basic_Gateway/?source_news=ipn` on the public host. The pinned receiver uses authenticated provider reads for the inspected IPN/Core paths. No HMAC validation was found in those paths. The plugin's return and IPN overrides were blank. Keep those defaults, and create a fresh cart and preference after a hostname change. The OAuth onboarding callback is separate from the payment receiver. See the [preference API reference](https://www.mercadopago.com.uy/developers/es/reference/online-payments/checkout-pro-preferences/update-preference/put), [Quick Tunnel guide](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/), [WooCommerce IPN guide](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-configuration/notifications) and [pinned notification flow](payment-flow.md).

## Acceptance matrix

| Requirement | Status | Evidence |
| --- | --- | --- |
| Recoverable backup | PASS | Native MySQL dump completion marker and WordPress archive inventory verified in private LocalAppData. |
| Healthy stack and named volumes | PASS | WP 7.1.4, WC 11.2.1 and official MP 8.9.7. Official MP strict checksum passed. |
| Public HTTPS and local origin | PASS | Public storefront, Store API, login and exact-origin WP-CLI checks passed. Local HTTP remained available. |
| Official gateway registration, Blocks, HPOS, UYU and shipping | PASS | Local and public `test-m04` preflights passed. The declared WooCommerce tested-through version is 11.0.1. |
| Expected test-seller authorization and application ownership | PASS | Provider verified seller `TEST_SELLER_ID` as MLU test user. The automatically created seller application matched the stored client ID. |
| Credential-dependent gateway availability | PASS | A fresh Chromium cart reached checkout over public HTTPS. The demo Checkout Pro option was visible and BACS was absent. |
| Invalid notification and forged return | PASS | The public browser suite rejected invalid and repeated requests. A forged approval return left the temporary order unpaid and stock unchanged. Fixture cleanup passed. |
| Provider preference and return URLs | BLOCKED | No hosted payment preference has been observed. |
| Approved and rejected transactions | BLOCKED | No genuine provider transaction or native paid/unpaid result recorded. |
| Genuine notifications and order/stock correlation | BLOCKED | No provider delivery, payment reference or provider-driven stock transition observed. |
| Pending, retry, abandonment, delivery ordering, valid duplicate and tunnel interruption | NOT RUN | Each needs genuine hosted checkout and supported provider behavior. |
| No real funds used in executed work | PASS | Setup and infrastructure checks only. Earlier funding requests used test-account tooling. |
| Host and live smoke regressions | PASS | Twelve Python tests, targeted secret scan, PHP lint and live smoke passed. See [verification](verification.md) for the full regression run. |
| Credential-dependent WooCommerce compatibility | BLOCKED | Requires hosted checkout, native return, payment processing and stock evidence. |
| Production homologation | NOT APPLICABLE | Independent test-only demo. |
| M07 recording | NOT RUN | [Readiness](m07-readiness.md) remains blocked. |

## Mercado Pago integration review

The vendor plugin owns Checkout Pro preferences and reads Payments API records to reconcile notifications. It includes the native order reference and generates HTTPS return/callback URLs on the public origin. WordPress options hold credentials privately. Checkout Pro creates hosted preferences, and the demo does not submit direct card payment or Orders API creation requests. The reviewed IPN/Core paths use provider lookups and lack an observed HMAC check. The credential-dependent delivery and idempotency cases remain open. Official quality evaluation requires real payment evidence. See the [source and test notes](payment-flow.md) and [execution log](testing-m06.md).
