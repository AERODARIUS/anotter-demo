# M05 frontend/backend performance review

Lighthouse 13.6.0 and real Chromium/Puppeteer use the five actual HTTP pages (home, shop, product, populated cart, checkout). Orders/payments are never submitted by the measurement script. Payload is observed separately through the browser Resource Timing API, including decoded JS/CSS/image bytes and transfer sizes. Native cookies remain intact; final URL and hydrated product assertions ensure cart/checkout do not silently redirect or get measured as loading skeletons.

## Environment and methodology

Windows, Intel Core i7-10710U, 16 GiB host RAM; Docker Linux engine has approximately 1.94 GiB available. Runtime versions/final results are in `testing-m05.md`. Local loopback HTTP, warm PHP/MySQL services, no CDN, one lab sample per page. Lighthouse uses its pinned desktop simulated-throttling preset and 1440×1000 viewport; actual throttle parameters and Chromium version are written to `work/m05/performance/final.json`. Payload measurement precedes Lighthouse after full native hydration at the same explicit viewport and records cache hits. Lighthouse then clears HTTP cache while retaining native cookies/localStorage; only service-worker/cache-storage data is cleared. Resource Timing decoded bytes represent resource payload, not compressed network transfer. Exploratory runs retained cached static resources; those zero transferred JS/CSS bytes must never be described as zero payload. The final run uses Lighthouse's own HTTP-cache reset rather than a separate session that the audit can override.

LCP/CLS/TBT and document response time are synthetic lab diagnostics. TBT is not INP. **INP: NOT RUN** — no representative real-user dataset/interaction distribution exists. No field Core Web Vitals claim is made. Local response time is not public-tunnel latency; the transient tunnel has no performance guarantee. Samples vary with CPU, Docker's small memory allocation and simultaneous activity; do not infer capacity/load-test results.

## Baseline and measurement corrections

The first exploratory run on WP 7.1.3 used a desktop viewport but Lighthouse's inherited mobile throttle, warm asset caches and concurrent regression activity. It reported home LCP 9.00 s, shop 1.44 s, product 1.42 s, cart 1.66 s, checkout 1.57 s; cart/checkout TBT 823/2445 ms. A second exploratory run showed similarly variable home LCP 10.08 s and cart/checkout TBT 1280/2716 ms. These are **exploratory, non-comparable samples**, retained privately as `baseline.json`/`baseline-cold.json`. The attempted CDP cache clear did not yield an uncached Lighthouse navigation; asset transfer totals near zero made that limitation visible. Do not use those runs to claim a speedup.

The final script explicitly uses Lighthouse's desktop preset, records browser version correctly (awaited browser version), and separates Resource Timing payload from Lighthouse transfer. A trace-engine insight diagnostic appeared during one exploratory checkout run while headline metrics remained available; the discarded exploratory results are not treated as a controlled benchmark. Final environment/measurement status is reported explicitly.

## Improvements

- Removed WordPress's legacy frontend emoji detection/download path from the theme. Current target browsers render emoji natively; no commerce module was dequeued. One unnecessary frontend script request is avoided, verified in rendered resource inspection.
- Preserved the existing 720/1440 WebP variants, responsive sizes/srcset, explicit media dimensions, eager high-priority hero, lazy below-fold images and self-hosted variable font preload/swap. Replacing that existing image strategy was not justified by a confirmed defect.
- Added bounded contact reflection, reducing worst-case invalid-response size and sanitization work. Native field validation still uses original input, not truncated input that could become falsely valid.
- Kept native cart/checkout sessions, prices and stock correct. No aggressive cache, personalized-response caching, third-party optimization service or vendor/core template replacement was introduced.
- Native mini-cart/checkout accessibility fixes add only state-specific CSS and a small checkout-only semantics script. They do not introduce a frontend framework/build service.

## Final measured results

See the final measured table in `testing-m05.md` and the exact JSON under ignored `work/m05/performance/`. Final values are reproduced below; measurement completion does not imply every optimization target passed. Goals where measurable: LCP ≤2.5 s, CLS ≤0.1; INP ≤200 ms remains unmeasured. Record TBT as a lab diagnostic and retain remaining heavy native checkout work as a measured concern if present.

Backend checks include health, actual document response time, native DB/HTTP/cart/checkout assertions and container memory snapshot. Baseline main containers were approximately WordPress 209 MiB and MySQL 478 MiB within the 1.94 GiB engine allocation during test activity; these are point observations, not peak/resource guarantees. No query profiler, sustained load/concurrency capacity benchmark or full memory leak study was run. M03 separately exercises a real two-customer last-unit race for correctness.

## Remaining concerns

Native WooCommerce cart/checkout load more JavaScript and API work than editorial pages; profile representative buyer interactions after genuine MP eligibility in M06. Do not drop modules that maintain totals/session/payment behavior to improve a score. A small Docker memory budget and loopback-only samples limit performance conclusions. Repeat controlled cold/warm multi-sample and public-origin measurements on the final target infrastructure before any production performance claim. Existing responsive imagery/font strategy remains sound; broader asset consolidation or caching needs evidence and explicit personalized-page exclusions.

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
