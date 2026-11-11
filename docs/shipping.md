# M03 Uruguay demonstration shipping

These options are **native WooCommerce flat rates**, not DAC/UES API integrations, official prices, coverage commitments or delivery-time promises. No carrier credentials are required. No labels, tracking, pickups or real dispatch occur.

## Initial configuration

The single source for sample defaults is `plugins/anotter-demo-foundation/includes/demo-config.php`. Monetary values below are UYU, per order, with the inherited demo tax calculation disabled.

| Priority | Native zone | Location | DAC sample | UES sample |
| --- | --- | --- | ---: | ---: |
| 0 | Montevideo — DEMO | State `UY:UY-MO` | 180 | 210 |
| 10 | Resto de Uruguay — DEMO | Country `UY` | 260 | 290 |

Both zones expose exactly two native `flat_rate` instances, named **DAC — DEMO · tarifa de ejemplo** and **UES — DEMO · tarifa de ejemplo**. Specific Montevideo matches first; country-wide Uruguay catches the remaining departments. There are no enabled methods in the native rest-of-world zone. Selling and shipping countries remain restricted to Uruguay.

The installed `WC()->countries->get_states('UY')` was inspected: it supplies all 19 departments and identifies Montevideo as `UY-MO`, Canelones as `UY-CA`, and Artigas as `UY-AR`. The zone's state location combines country and state as `UY:UY-MO`; `UY:MO` is not valid in this installation. The migration verifies the actual Montevideo mapping rather than assuming an uninspected identifier.

The original M01 **Uruguay — DEMO** zone and its method records are preserved at priority **100**. It is an administrative legacy record: both M03 zones match all permitted Uruguay destinations first, so customers never receive those old estimates or the old pickup option. No pickup location was authorized, so neither active M03 zone offers pickup. Do not reorder the legacy zone ahead of M03. Custom installations need a review of any additional overlapping zones before migration.

## Change rates

Before first M03 setup, edit `demo-config.php`, then run `python scripts/dev.py setup`. The CLI migration saves native zone/instance IDs in `anotter_demo_shipping_v3` and marks completed initialization. Rerunning setup preserves later administrative changes and does not reapply sample rates. The old `.env` `DEMO_FLAT_RATE_COST` applies only to the M01 legacy zone.

After initialization, use **WooCommerce → Settings → Shipping → Shipping zones**, open the relevant DAC/UES instance and change its native Cost field. WooCommerce settings are the runtime source of truth. Review both zones, retain the DEMO labels, save, and verify checkout. Editing the defaults file after initialization alone does not overwrite rates. To use new defaults on another fresh clone, keep the file in source control.

Do not insert rates into theme templates or JavaScript. Native shipping calculation, caches, selected rate IDs and order shipping items remain authoritative. A department change switches zones and instance IDs, so WooCommerce may select the first available rate; customers can then choose the other carrier. The selected method name, instance and amount are saved to the order and visible in native administration/confirmation.

## Address and platform limits

Matching uses the selected **department**, not municipality name or a verified carrier service area. Native country/department choices and postal validation do not prove an address exists, that a postcode belongs to a department, or that a carrier can deliver there. No postcode-to-department lookup or invented carrier coverage table is installed. Postcodes remain required under the native address schema; do not treat acceptance as postal verification.

The cart may show an estimate from the configured base country before a full address is supplied. Checkout recalculates after the customer supplies a department and shipping details. Unsupported countries are rejected through native selling/shipping validation even if a forged request bypasses the country dropdown. Taxes remain unconfigured for real business use.

## Replace estimates later

A real provider integration needs authorized business accounts, verified current API/plugin documentation, credentials stored privately, tariff/service-area rules, commercial agreements, error handling and a test environment. Confirm responsibilities for labels, tracking, dispatch and pickup scheduling. Add the verified provider's supported WooCommerce shipping method inside the correct zones, test it, then disable the corresponding demo flat-rate instances. Historical orders retain their recorded method/amount. Do not rewrite old order shipping items or claim these sample instances are connected to the carriers.
