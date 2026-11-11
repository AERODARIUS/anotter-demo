# Roadmap

Source published on 2026-11-11: [AERODARIUS/anotter-demo](https://github.com/AERODARIUS/anotter-demo). Public GitHub source is available; M06 payment acceptance and M07 recording remain pending.

Public source preparation (2026-11-11) sanitizes documentation and adds offline source/index/history checks. The project root is the publication repository, with a new anonymous initial `main` history and a private backup of old Git metadata outside OneDrive. Local recovery/editor refs are not public branches. Checked snapshot export remains optional. It does not change milestone acceptance: M06 provider evidence and M07 recording remain pending. See [publishing.md](publishing.md).

Latest checkpoint: **2026-11-10**. M06 local and public infrastructure is running. Provider acceptance and M07 readiness remain BLOCKED. See the [acceptance report](payment-acceptance-report.md) and [M07 readiness](m07-readiness.md).

| Milestone | Status | Scope |
| --- | --- | --- |
| M01 — Project Foundation & Local Development Environment | Complete; real-service acceptance passed | Compose, silent CLI installation, Uruguay defaults, fictional seed data, persistence/idempotency checks and documentation |
| M02 — Storefront UI/UX & Product Experience | Complete; browser and visual acceptance passed | Original provisional identity/artwork, responsive native storefront and product experience, informational pages, validation-only contact form and design documentation |
| M03 — Cart, Checkout, Inventory & Shipping | Complete; real checkout, concurrency, visual and regression acceptance passed | Native guest/offline orders, cart/confirmation, Uruguay DAC/UES demonstration zones, inventory lifecycle, safe local email previews and documentation |
| M04 — Mercado Pago Sandbox & Payment Notifications | Implementation/infrastructure verified without credentials; provider acceptance incomplete | Official 8.9.7 plugin, reversible public HTTPS, native receiver/configuration/security checks and passing regressions; genuine purchases and notifications blocked by missing authorized test accounts |
| M05 — QA, Security, Performance & Demo Preparation | Complete; scoped QA/demo acceptance verified, with documented P2/measurement limits | Real-service/cross-browser QA, security patch/review, accessibility fixes, lab performance, isolated demo reset/diagnostics, runbook/storyboard and M06 plan |
| M06 — Final Integration & Acceptance Testing | In progress; provider acceptance BLOCKED | The existing MLU test seller is verified and Checkout Pro appears in the HTTPS checkout. Genuine test buyer payments and related provider notifications and stock behavior are pending. |
| M07 — Final Demo & Video Recording | Pending | Record the final commercial demonstration only after M06; storyboard prepared in M05 |

M01/M02 acceptance first passed on 2026-10-27. M03 and M01/M02 regressions passed on 2026-10-28 against real WordPress/WooCommerce services, including repeated setup, restart persistence and ten repeated last-unit races. Historical results remain in [verification.md](verification.md) and [testing-m03.md](testing-m03.md). M04 execution and incomplete acceptance are tracked in [testing-m04.md](testing-m04.md). M05 preparation is tracked in testing-m05.md; genuine provider acceptance remains incomplete for M06.

M05 completed on 2026-10-29; see [testing-m05.md](testing-m05.md). M04 genuine provider acceptance remains incomplete for M06; M07 has not been executed. No production-readiness or full-payment-acceptance claim.
