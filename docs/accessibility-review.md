# M05 accessibility review

Target: WCAG 2.2 AA where applicable. This combines actual browser/keyboard checks and axe-core 4.14.0 rules tagged wcag2a/2aa/21a/21aa/22aa. It is **not WCAG certification** or a full assistive-technology evaluation. Final browser/scan counts and remaining findings are in `testing-m05.md` and ignored `work/m05/browser/final-combined.json`.

## Confirmed issues and fixes

| ID | Severity | Finding | Fix |
| --- | --- | --- | --- |
| A11Y-01 | P1 / axe serious | WC 11.2.1's closed mini-cart was moved off screen while `aria-hidden=true` descendants remained keyboard focusable | Theme CSS sets visibility hidden only for the native closed state. Actual native open/close, cart state and focus remain intact; open-state axe and keyboard behavior are retested |
| A11Y-02 | P1 / axe serious | Header/footer brand visible text wasn't fully contained in the overriding accessible name | Derive both link names from actual visible content, with hidden Inicio context; remove the conflicting override |
| A11Y-03 | P1 / axe critical | Native Checkout validation referenced error IDs without sufficient announced/described association | Checkout-only enhancement associates existing native error IDs through aria-describedby and polite atomic error regions. No field validation, error text, order submission or focus replacement |
| A11Y-04 | P1 / axe serious | Native price-loading skeleton divs had aria-label/live but no naming-permitted role during updates | Checkout-only enhancement gives those existing live placeholders role=status. Dynamic loading and totals remain native |
| A11Y-05 | P2 | Unknown URLs returned HTTP 404 but the index fallback had no useful main heading/recovery | Added Spanish 404 template with H1, explanation and home CTA; retains native status, header/footer and disclosure |

Source-specific compatibility workarounds are isolated in theme CSS and `assets/checkout-accessibility.js`; review/remove only after the installed vendor version's rendered behavior proves the defect fixed. Mutation observation is restricted to checkout's main content and native error/child changes; enhancement attributes do not trigger its attribute observer. Native Woo focus/error/validation/order mechanics remain authoritative.

## Test inventory

- Home, shop, hidden real product detail, How to Play, FAQ and contact: one main/H1, images, no horizontal overflow at 320/375/768/1024/1440; axe at 375/1440.
- Cart and checkout: same five widths, native quantity labels, real cookie session, accessible validation failures, email failure, keyboard shipping radio, price loading and hydrated native order summary.
- Confirmation: native unpaid status, shipping, readable totals and layouts at all five widths; axe and screenshots at desktop/mobile. This is BACS, never financial verification.
- Mini-cart: closed controls unavailable, opened panel visible and axe checked; native close retained.
- Keyboard: menu Enter/Escape, focus moves inside and returns to opener; native FAQ disclosure, quantity controls and shipping radios. Checkout error focus stays in the main flow; associations/live regions checked separately. Contact server feedback receives focus; HTML/server error semantics tested.
- Reduced motion is requested in browser contexts; theme's media query removes animation/transition/scroll effects. No custom focus trap is introduced. Native dialogs retain their own trap/restore behavior.
- Color contrast, semantic names/labels/headings/landmarks and target-size rules are included where axe can evaluate them. Brand/logo text and form association issues were discovered by the broader wcag21a/22aa audit, beyond the initial narrow scan.

## Manual/browser-assisted and visual review

Inspect actual screenshots after natural scrolling triggers lazy image loading. An early mobile capture had blank below-fold images because full-page screenshots alone don't trigger native lazy loading; the capture helper now scrolls/decode-waits before export. This was corrected in the artifact workflow rather than disabling lazy loading in the product. Focus outlines, labels, errors, responsive controls and readable summaries are visually inspected at mobile and desktop. Critical images include home/shop/product/cart/checkout/confirmation for each available engine; primary informational forms/pages are visited too.

No NVDA/JAWS/VoiceOver spoken-output session was executed. Browser-observed aria/live/focus behavior cannot prove real announcement timing across all assistive technologies. Zoom 200–400%, forced-colors mode, cognitive/usability sessions and comprehensive physical touch-target measurement are not established by these automated checks. Native customer interfaces were tested without authenticated merchant configuration or hosted Mercado Pago controls; account-dependent payment UI and external accessibility belong to M06. Re-evaluate the workarounds after any WordPress/WooCommerce patch, with special attention to native validation and mini-cart.
