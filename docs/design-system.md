# M02 design system

## M05 verified presentation extension

The original tokens, layout, provisional art, typography and native commerce blocks are preserved. M05 adds a Spanish 404/recovery template, derives brand link names from visible text (with hidden Inicio context), hides only the natively closed mini-cart from keyboard focus and associates native checkout errors/price-loading placeholders with appropriate descriptions/live/status semantics. Native validation, menu/cart state, focus and submission are retained. See [accessibility-review.md](accessibility-review.md) for confirmed vendor gaps and retest/removal criteria.

The legacy emoji detection/download path is removed for current native-emoji browsers. Responsive WebP, explicit dimensions, eager/high-priority hero and self-hosted font/swap remain. No redesign or new customer feature was introduced. Browser captures now trigger below-fold lazy media before export and include desktop/mobile viewport crops; visual review is separate from DOM/axe assertions. WebKit on Windows is an engine check, not proof of Safari/VoiceOver behavior.

This is an original, provisional identity for an **independent demonstration**. It is not an official AnOtter Game identity. All product art, rules and specifications are fictional. No official logo, packaging, video, social account or commercial policy is represented.

## Visual principles

Warm paper, tactile product photography, deep teal typography and restrained yellow/coral geometry suggest a shared tabletop experience. Typography and product imagery carry the design; decoration stays small. The persistent demo strip and footer disclosure explain the prototype without covering the shopping controls. The duplicated native floating store banner is visually suppressed by the theme; its M01 setting remains enabled.

## Tokens

The semantic palette is registered in `theme.json` and mirrored by `--aot-*` CSS custom properties in `style.css`.

| Role | CSS variable | Value |
| --- | --- | --- |
| Primary | `--aot-primary` | `#124C46` |
| Secondary | `--aot-secondary` | `#DD664B` |
| Accent | `--aot-accent` | `#F5CC56` |
| Background | `--aot-background` | `#FAF7EF` |
| Surface | `--aot-surface` | `#FFFFFF` |
| Text | `--aot-text` | `#182E2A` |
| Muted text | `--aot-muted` | `#53655F` |
| Decorative border | `--aot-border` | `#D4DCD3` |
| Input border | `--aot-border-strong` | `#70847E` |
| Success | `--aot-success` | `#236244` |
| Error | `--aot-error` | `#A72F36` |
| Warning | `--aot-warning` | `#805500` |
| Focus | `--aot-focus` | `#983C16` |

Use primary/white and accent/primary for filled buttons. Coral is decorative, not a small-text background. Input borders use the stronger token; pale borders only separate noninteractive surfaces. Color is accompanied by text for availability and errors. The M01 `ink`, `paper`, `muted` and `action` palette slugs remain available for existing saved blocks.

## Typography

Manrope variable, weights 400–800, is self-hosted as a 24.8 KB Latin WOFF2 with Spanish characters and system fallbacks. Its SIL Open Font License is in `theme/anotter-demo/assets/fonts/OFL.txt`. There are no Google Fonts requests at runtime. `font-display: swap` and a single preload keep loading simple.

| Use | Size / treatment |
| --- | --- |
| Display | `clamp(2.7rem, 5.5vw, 4.5rem)`, 800, line-height 1.04 |
| H1 | `clamp(2.3rem, 5vw, 3.5rem)`, 800 |
| H2 | `clamp(1.9rem, 3.4vw, 2.75rem)`, 800 |
| H3 | `clamp(1.18rem, 2vw, 1.45rem)`, 800 |
| H4–H6 | Native heading hierarchy with the same family, weight and readable line-height |
| Body | 1rem, line-height 1.75 |
| Labels / buttons | 700–800; at least 44–48px control height |
| Prices | 800, native WooCommerce formatting, explicit UYU caption |
| Supporting text | Muted, normally .8–.92rem; short uppercase eyebrows use increased tracking |

## Layout and responsive rules

- Maximum content width: 1200px; reading width: 740px.
- Gutters: `clamp(1rem, 3vw, 2.5rem)`; section spacing: `clamp(3.5rem, 7vw, 6rem)`.
- Spacing scale: .5, 1, 1.5, 2, 3rem plus fluid section spacing.
- Radii: 10px controls, 22px cards, 32px artwork, pill buttons.
- Cards use a subtle teal shadow and a solid border; no essential information depends on hover.
- Native product collections use a CSS Grid with flexible columns, a 300px minimum card width and a one-column mobile override. Pagination, ordering, taxonomy queries, visibility, price and inventory remain native WooCommerce behavior.
- At 600px, small editorial grids can gain columns; at 782px, hero/product introduction and contact content can split into two columns. Navigation switches to the native overlay below 901px. The header is sticky from 1100px upward.
- Hero, featured and catalog images use a 3:2 ratio with uncropped media. The native related-product collection retains WooCommerce's thumbnail crop. Original artwork is available at 720×480 and 1440×960. WordPress-generated product images provide responsive `srcset` values.
- The primary hero image is eager/high-priority; below-the-fold media is lazy. Media dimensions reserve space. Motion is disabled when reduced motion is requested.

## Reusable components

| Component | Source / native mechanism |
| --- | --- |
| Header, provisional card mark, links | `patterns/header.php`; native Navigation block |
| Cart count and drawer | WooCommerce Mini-Cart block; no custom cart store |
| Hero, section heading, benefits, steps | Native Group, Heading, Paragraph, Button and Image blocks in patterns |
| Featured game | Native Single Product, image, summary, specifications, price and availability blocks; stable SKU resolves the current product |
| Product card / product showcase | Product Collection + Product Template blocks |
| Catalog sorting / categories | Native catalog sorting and product-category archive links |
| Product details | Native gallery, add-to-cart form, details/attributes, breadcrumbs and related-product collection |
| FAQ | Native Details/Summary disclosure blocks |
| Contact | Native Shortcode block rendering the small secure demo-form plugin |
| Empty catalog / shipping note / CTA | Named reusable patterns |
| Footer and provisional legal links | Native navigation and theme patterns |

Theme templates compose the installed WooCommerce 11.2.1 block APIs. They do not copy PHP commerce templates or replace stock, variations, cart totals or checkout validation. Review these HTML compositions when upgrading WooCommerce.

## Accessibility decisions

Use one H1 and one main landmark per page, logical section headings, semantic links/buttons, visible focus outlines, explicit field labels and native keyboard behavior. WordPress controls the mobile dialog's focus trap and Escape/restore behavior. A small enhancement reflects its class state in `aria-expanded`/`aria-controls`; it does not replace menu logic. Missing core es_UY navigation strings receive Spanish theme fallbacks.

Form errors are shown in an alert summary with field links, `aria-invalid` and error descriptions. Success states explain that the message was neither sent nor stored. Native HTML constraints provide client validation; the server independently validates all input. No clickable div or fake video button is used.

The design targets WCAG 2.2 AA. The checks in `verification.md` document tested behaviors; this is not a claim of a complete assistive-technology certification. A broader browser/screen-reader audit belongs in M05.

## Provisional content and boundaries

Game steps, player counts and duration are marked fictional. Video, email, location, social accounts, shipping promises and commercial policies remain unfilled/explicitly provisional. The legal pages explain the demo rather than inventing company policies. Artwork prompts and provenance are recorded in [artwork-prompts.md](artwork-prompts.md).

## M03 commerce extension

Cart, checkout and confirmation retain the same paper/surface/teal tokens, fluid shell, rounded summary panels, visible focus and 44–48px controls. Native WooCommerce fields and error semantics remain intact. On narrow screens the cart table becomes a two-column item layout with its native line subtotal below the product controls, avoiding a squeezed third column. Native checkout summaries collapse/reorder at WooCommerce's breakpoints. The empty-cart pattern provides one clear store CTA. No custom cart JavaScript or frontend dependency was added.

The confirmation uses WooCommerce's native order blocks and access checks, with explicit native status/unverified DEMO payment text. Native transactional email colors/footer match the storefront, with local preview only. Illustrative DAC/UES rates are configured in WooCommerce, never theme tokens or CSS. Real payments, transport, official policies and public tunneling remain outside M03.
