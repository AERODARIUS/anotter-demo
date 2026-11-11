# M04 — official Checkout Pro payment flow

**M06 checkpoint (2026-11-10): BLOCKED on seller authorization and genuine test transactions.** The pinned receiver is registered and the public-origin preflight passed. The official plugin derives the IPN endpoint from the exact public origin. Actual provider delivery and order/stock correlation are tracked separately. See [M06 results](testing-m06.md) and the [acceptance report](payment-acceptance-report.md).

This describes source inspected in official **8.9.7**, not a claim that a provider transaction has succeeded. Native WooCommerce owns cart, coupons, quantities, shipping, UYU order totals, HPOS, reservations and stock. The official plugin owns payment preferences and payment synchronization; the foundation adds only demo eligibility, origin handling and honest confirmation copy.

```mermaid
sequenceDiagram
    participant Buyer as Test buyer browser
    participant WC as Native Blocks / Store API / HPOS
    participant Plugin as Official Checkout Pro 8.9.7
    participant MP as Mercado Pago services
    Buyer->>WC: Products, coupon, Uruguay address, DAC/UES DEMO
    WC->>WC: Validate cart and compute order total; reserve stock
    WC->>Plugin: Process order with woo-mercado-pago-basic
    Plugin->>MP: Official preference client (items/shipping, reference, public URLs)
    MP-->>Plugin: Preference and hosted checkout URL
    Plugin-->>Buyer: Redirect to init_point (test seller in sales mode)
    Buyer->>MP: Hosted test purchase using separate test buyer
    MP->>MP: Determine actual test payment result
    par Browser return (may arrive first)
        MP-->>Buyer: Success / failure / pending back URL
        Buyer->>WC: Native order return / cancel URL
        WC-->>Buyer: Current order state; awaiting confirmation if unpaid
    and Asynchronous provider notification
        MP->>Plugin: Official wc-api receiver, source_news=ipn
        alt topic=merchant_order and source_news=ipn
            Plugin->>MP: Bearer-authenticated merchant order and payment reads
            MP-->>Plugin: Authoritative external_reference, payments and amounts
        else Core notification ID
            Plugin->>MP: Authenticated Core notification lookup
            MP-->>Plugin: Authoritative notification/payment data
        end
        Plugin->>WC: Locate order from provider external_reference; process status
        WC->>WC: Native payment_complete/status and guarded stock callbacks
        WC-->>Buyer: Subsequent native order view reflects current state
    end
```

## Inspected receiver behavior

`BasicGateway::WEBHOOK_API_NAME` is `WC_WooMercadoPago_Basic_Gateway`. `AbstractTransaction::getNotificationUrl()` derives the WooCommerce API URL from the public site unless a custom domain override is present; `NotificationType` maps Checkout Pro to `ipn`. Leaving custom override empty avoids stale tunnel URLs. The plugin omits automatic notifications for a localhost URL, making public origin configuration necessary.

`NotificationFactory` selects `IpnNotification` for `topic=merchant_order&source_news=ipn`. It retrieves `/merchant_orders/{id}` using the linked seller's server-side Bearer token, checks HTTP success and payments, and retrieves `/v1/payments/{id}`. Other notification shapes use `CoreNotification`, which accepts a provider notification ID (`P-…` / `M-…`) and retrieves authoritative notification data through the official Core client. A payment-webhook path also exists for `type=payment&source_news=webhooks`; it is not a reason to create a second project receiver.

The inspected IPN receiver does **not** perform custom HMAC/x-signature validation. Its verification boundary is the authenticated provider lookup, not browser-supplied status. We preserve that official behavior and do not claim stronger signature checks. `AbstractNotification` resolves a native order from the **provider-returned** external reference using the official store prefix. Actual identity/amount matching, valid replays, adversarial order association and out-of-order transitions remain provider acceptance checks; source review and malformed-request rejection do not prove them.

The official return hook can save a `payment_id` query value as payment-reference metadata. That identifier alone is **not** proof of payment. Browser `status`/`collection_status` parameters do not run native payment completion. Inspect provider records and receiver-driven notes/paid state together, not just visible metadata or a return page.

## Status and stock expectations

| Authoritative event | Inspected plugin/native behavior; verify on real purchase |
| --- | --- |
| Approved | Official approved flow calls native `payment_complete()` from eligible unpaid states. Physical products need processing; plugin maps approved to processing. Virtual/no-processing orders can complete natively. Do not hardcode a universal final status |
| Rejected | Rejection flow maps to failed; a merchant-order IPN can aggregate an unpaid result as pending. Observe the actual selected service path, customer result and supported retry |
| Pending | Remains unpaid/pending; native reservations/expiry apply |
| In process | Maps to on-hold, still unpaid; native stock reduction may occur |
| Interrupted | No successful provider confirmation means no verified paid order; inspect native pending expiry and supported pay/retry link |
| Duplicate approved | Approved handler is limited to pending/on-hold/failed; WooCommerce's native stock-reduced flag guards repeated stock reduction. Real valid-event replay still needs testing |
| Cancelled / failed | Native restoration/release follows actual prior reduction; never add stock manually |
| Browser before callback | Current state can still be unpaid; confirmation copy says verification is pending. Return alone is not financial confirmation |

Do not equate stock reduction with payment: on-hold can reduce stock while unpaid. Do not independently compute a payment amount or change native status to force expected stock behavior. M03's reservation-read race mitigation remains unchanged. See [checkout-flow.md](checkout-flow.md).

## Administration and outage handling

Native WooCommerce Orders provides DEMO number, synthetic customer details, line items, quantities, UYU total, DAC/UES method, MP method, payment metadata, status and payment-related notes. HPOS remains active. Payment ID metadata may be distinct from WooCommerce's standard transaction-ID field; inspect the plugin's payment metadata as well as that field. No custom tables, SQL status updates, fulfillment automation or payment dashboard are added.

To establish receipt, correlate a provider delivery timestamp/response with private server/plugin logs, order notes and the provider's payment record. Record a sanitized before/after order/stock observation, not card data or credentials. Re-send only an **actual** valid event through supported provider tools if offered; observe repeated status/stock/paid-date/fulfillment behavior. Synthetic invalid events only prove rejection.

With a tunnel outage, browser return and asynchronous calls can fail independently. Keep the order unpaid until authoritative processing occurs. Inspect the provider transaction before retrying a purchase to avoid a second attempt for an already-approved test payment. Restore the endpoint and inspect any official resend/sync action available. The plugin has provider-backed synchronization code, but its availability and recovery for a missing payment reference have not been verified here. No retry schedule or guarantee is asserted. Different ephemeral hosts require new preferences; do not promise old callback delivery.

The [current WooCommerce testing procedure](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test) specifies test-seller Checkout Pro in sales mode. Generic sandbox notification limitations must not be “fixed” using real seller credentials. If genuine notifications are unavailable even under that documented configuration, preserve the observation and leave notification acceptance incomplete.
