# AnOtter Game — M05 QA and demonstration preparation

Independent commercial prototype, **not the official AnOtter Game website**. All products/orders are fictional. M05 adds QA/security/accessibility/performance evidence, isolated testing, selective demo reset, diagnostics and recording preparation. M04's genuine payment acceptance remains incomplete and belongs to M06; recording belongs to M07. Neither is executed by M05. See [current results](docs/testing-m05.md) for milestone status and tested limits.

## Public repository preparation

This project folder is the repository to publish. `.gitignore` excludes private environments, keys, backups, dependencies, runtime data and browser evidence. Its public `main` starts with a new anonymous commit. Prior Git metadata is backed up privately outside the checkout and OneDrive; local recovery refs/objects are excluded from publication. See the [publication guide](docs/publishing.md).

Run `python scripts/check-secrets.py --staged --history` before pushing. Local pre-commit and pre-push guards are enabled here; fresh clones must enable `.githooks` themselves. `python scripts/prepare-public.py` remains an optional export tool; a second checkout is not needed for the normal publication workflow. No GitHub repository or push is created automatically.

Keep only reserved synthetic addresses in fixtures and leave `.env.example` secrets blank. Payment account/application identifiers in the documentation are placeholders. Set `ANOTTER_TEST_SELLER_ID` privately to the authorized test seller ID before using the PowerShell activation examples; no provider identity is supplied by the repository.

## M05 demonstration workflow

For the existing demo in OneDrive, first select the private environment and verify the existing Docker volumes. Follow the [Windows development guide](docs/demo-runbook.md#windows-development-workspace) before running the commands below.

```powershell
python scripts/dev.py start
python scripts/dev.py doctor
python scripts/dev.py demo-reset --session rehearsal --dry-run
# On an idle owned local dataset, after reviewing the plan:
python scripts/dev.py demo-reset --session rehearsal --apply --confirm-project anotter-demo
```

Doctor distinguishes PASS/FAIL/BLOCKED/NOT RUN; exit 0 may contain blocked provider prerequisites. Selective reset preserves unknown data and refuses active unrelated orders, paid/provider orders, production/staging and configured public exposure. Enroll explicit offline orders with `demo-tag` before optional session deletion. **The older `reset --confirm-reset` deletes volumes and is not a rehearsal tool.** See the [demo runbook](docs/demo-runbook.md) for safe fixtures/admin/browser/tunnel/reset/teardown and the reviewed patch backup/rollback procedure.

For the isolated QA suites and private LocalAppData settings, follow the [QA workflow](docs/demo-runbook.md#separate-qa-environment).

All helpers honor absolute `ANOTTER_ENV_FILE`. Run suites sequentially per project. M05 screenshots/JSON stay ignored in `work/m05`; credentials/backups/vendor plugins are never exported. `npm run audit:performance` measures real pages without creating an order. PHP line/branch percentages are unavailable without Xdebug/PCOV; practical branch/permission/boundary evidence is documented honestly.

Reviews: [security](docs/security-review.md), [accessibility](docs/accessibility-review.md), [performance](docs/performance-review.md). Preparation: [4:30 storyboard](docs/video-storyboard.md), [M06 acceptance checklist](docs/m06-acceptance-plan.md). M05 deliberately patches WP 7.1.3 → **7.1.4** after the new security advisory, private backup and QA staging. Existing core volumes require explicit update and matching private image pin; setup does not silently upgrade them. WC/MP pins stay unchanged.

## M04 — Mercado Pago test accounts only

M06 has verified the live stack, temporary HTTPS and the linked Uruguay test seller. Checkout Pro is available on the public demo. Genuine test payment acceptance remains pending. See the [M06 acceptance report](docs/payment-acceptance-report.md).

`setup` installs and checksum-verifies official `woocommerce-mercadopago` **8.9.7** in the Docker volume, initially disabled. No downloaded vendor source is committed. WordPress is now 7.1.4 after the reviewed M05 patch; WooCommerce 11.2.1, Checkout Blocks and HPOS remain. The plugin declares Blocks/HPOS support, but its header only declares WooCommerce tested through 11.0.1; credential-dependent compatibility remains an M06 acceptance check.

```powershell
python scripts/dev.py setup
python scripts/dev.py tunnel
# Keep the tunnel terminal open. In a second terminal:
python scripts/dev.py payments --mode status
# Link only a Uruguay test seller via WooCommerce → Mercado Pago;
# select the documented Checkout Pro sales mode for that TEST account.
python scripts/dev.py payments --mode pro --seller-id $env:ANOTTER_TEST_SELLER_ID
python scripts/dev.py test-m04
npm run test:m04
# Restore offline regression testing when needed:
python scripts/dev.py payments --mode offline
```

The `pro` command verifies the Uruguay test seller via `/users/me` and checks the expected seller ID when supplied. It then enables Checkout Pro and disables BACS. The plugin keeps credentials in its private WordPress options. Use the Checkout Pro procedure for the test seller in sales mode and the separate test buyer with official test cards. See the [WooCommerce testing procedure](https://www.mercadopago.com.uy/developers/es/docs/woocommerce/integration-test).

The tunnel helper runs `cloudflared tunnel --url http://localhost:8080`, accepts only its exact HTTPS Quick Tunnel hostname and restores the previous origin on Ctrl+C. Local access stays available. Browser cookies do not transfer between local and public origins; start a fresh public cart. No database URL replacement occurs. Recreate checkout attempts after hostname changes because existing provider preferences retain their old URLs. See [setup](docs/mercadopago-setup.md), [tunnel](docs/cloudflare-tunnel.md), [payment flow](docs/payment-flow.md) and [actual M04 results](docs/testing-m04.md).

## Prerequisites

- Docker Desktop using **Linux containers** on Windows (WSL2 preferred; Hyper-V also supported), or Docker Engine on Linux/macOS. Docker Compose v2.10+ is required; a current maintained Docker Desktop/Compose release is recommended. Hyper-V requires sharing this repository folder in Docker Desktop → Resources → File Sharing.
- Python 3.10+ and Git. No host PHP, MySQL, Composer or paid service is needed. Node.js 20+ is optional for the Playwright browser acceptance tests; it is not needed to run or edit the theme.
- Internet access for official Docker images, WordPress translations and WooCommerce downloads. Allow approximately 4 GB of Docker memory and several GB of disk space.
- On Windows, put the clone in a normal writable folder and allow Docker Desktop access to that folder. WSL2 filesystem clones offer better performance, but PowerShell paths also work.

## First-time setup

Use the following command for a new environment. To resume the migrated demo, use the [Windows start procedure](docs/demo-runbook.md#start-and-diagnose).

```powershell
python scripts/dev.py setup
```

The command creates `.env` **only when absent**, generates independent 48-character random administrator/database secrets and eight WordPress authentication keys/salts, starts the services, waits for database and HTTP health, installs WordPress and pinned WooCommerce/Mercado Pago, activates the demo plugin/theme, initializes Mercado Pago disabled, applies Uruguay defaults, seeds three fictional products and runs live smoke checks. It never prints the secrets. `.env` is ignored by Git and must stay private. Unix creation mode is `0600`; Windows uses the containing folder's inherited ACL, so use a private user folder.

- Storefront: <http://localhost:8080/>
- Administration: <http://localhost:8080/wp-admin/>
- Login: `WP_ADMIN_USER` and `WP_ADMIN_PASSWORD` in your local `.env`. Open that file locally to retrieve the generated password. `demo@example.invalid` is a reserved placeholder, and **all outgoing WordPress email is suppressed** by the foundation plugin. Password reset email will not arrive; use WP-CLI for a local reset.
- Header navigation leads to the shop, How to Play, FAQ and contact pages. WooCommerce owns cart, checkout and account pages; page URLs are read from native settings rather than assumed slugs.

To choose the port or other defaults **before first setup**:

```powershell
python scripts/dev.py init-env
# Edit .env locally: for example, HTTP_PORT=8090 and SITE_URL=http://localhost:8090
python scripts/dev.py setup
```

`.env.example` documents every variable. If copying it by hand, supply distinct strong values of at least 24 characters for all three passwords and all eight authentication keys/salts. This project's dotenv subset accepts simple literal values with optional surrounding quotes; `$` interpolation, inline comments and embedded quotes are intentionally rejected by the helper. Use generated URL-safe credentials. Variables in `.env` take precedence over ambient shell variables when using the Python helper.

## Store defaults and repeatability

Spanish Uruguay (`es_UY`) is selected only if WordPress publishes that translation for the pinned core version; otherwise Spanish Spain (`es_ES`) is selected. A translation/network failure stops setup rather than silently switching to English. Timezone is `America/Montevideo`, URLs use pretty permalinks, indexing is discouraged, and debugging goes to container stderr **only with `APP_ENV=local`**. Discouraging indexing does not provide access control.

WooCommerce uses UYU, native `$` formatting, Uruguay-only selling/shipping, tracked inventory and guest checkout. M03 adds Montevideo and rest-of-Uruguay zones with native DAC/UES sample flat rates. The old M01 zone remains behind both new zones for audit and is never selected for permitted destinations. Taxes remain disabled; business tax treatment requires confirmation. Native BACS is enabled as **DEMO — Pedido de prueba · sin pago**, without bank details. It creates unpaid **on-hold** orders and reduces stock natively; it does not charge money or verify payment. M03 disables legacy demo COD. All new orders receive demo metadata, an order note and a `DEMO-` display-number prefix. Completed initialization preserves later administrative settings, prices and quantities.

| SKU | Fictional product | Initial UYU price | Initial stock |
| --- | --- | ---: | ---: |
| DEMO-CARDS-001 | DEMO — Ronda de cartas | 890 | 24 |
| DEMO-EXP-001 | DEMO — Nuevas rondas (expansión) | 390 | 12 |
| DEMO-ACC-001 | DEMO — Bolsa de fichas | 250 | 30 |

Each product has its own category, descriptions and stable SKU. M02 replaces only the known M01 placeholder images with original conceptual product artwork, adds visibly fictional primary-game attributes when none exist, and appends a shared native demo tag so WooCommerce can discover related products. Existing custom images, tags, attributes, prices and stock are preserved. Known untouched English WooCommerce page titles are translated without changing their IDs/slugs. Seeding creates missing SKUs; collisions with unmarked products are rejected. Setup also rejects unrelated installations, a nonempty database before installation, mismatched canonical URLs and a WooCommerce version differing from the configured pin. Completed store/shipping defaults are never reapplied. Do not run setup concurrently.

The preferred site locale remains `es_UY`. Because WooCommerce does not publish that pack, setup installs its available Spanish fallback (`es_ES`) and the foundation plugin loads PHP/script translations for the site locale. WordPress navigation labels missing from the older core es_UY pack receive a small theme fallback.

## Verification

```powershell
# Real application state, HTTP requests, cookie-based add-to-cart/checkout and error logs
python scripts/dev.py smoke
# Run setup again, compare database/content snapshots, stop/start, compare again, smoke
python scripts/dev.py verify
# PHP syntax validation in the pinned CLI image
python scripts/dev.py lint
# Credential/config helper regression tests, independent of Docker
python -m unittest discover -s tests -p "test_*.py" -v
```

`verify` expects a successful initial setup and temporarily stops **this Compose project's** two services. Run it on an idle demo. It compares user identities, product/page/media records, inventory/prices, shipping configuration, key settings and uploaded image SHA-256 hashes. Background WooCommerce transients and scheduled-action timestamps are excluded. HTTP tests use a new cookie session, add one product without creating an order or reducing stock, and verify checkout does not redirect to an empty cart. Logs are checked for PHP fatal/parse errors in the test window. Smoke checks intentionally accept later legitimate price/stock edits; newly created products' exact initial values are checked by the seeder.

M02's live assertions and browser suite extend these checks:

```powershell
python scripts/dev.py wp -- eval-file /opt/anotter/tests/storefront.php
npm ci
npx playwright install chromium
npm run test:e2e
```

The browser suite uses real WordPress/WooCommerce at `SITE_URL`, visits six pages at 320, 375, 768, 1024 and 1440px, tests native cart quantities/subtotals and an isolated out-of-stock fixture, checks contact/FAQ/navigation behavior, and writes screenshots to ignored `work/screenshots/`. Its temporary stock fixture is removed through WooCommerce CRUD; existing product inventory is never changed. To export screenshots elsewhere, set `SCREENSHOT_DIR` to an absolute path. `npm run screenshots` runs the viewport/image checks and captures without the purchasing/form tests. No Node process is needed by the storefront. See [docs/verification.md](docs/verification.md) for actual results.

## M02 pages and theme development

| Page | URL / mechanism |
| --- | --- |
| Home | `/`; hero, live featured game, steps, benefits, native product showcase, FAQ and CTA |
| Shop | Native WooCommerce shop URL; sorting, category archives and responsive product cards |
| Product | Native product permalink; gallery, price, quantity/cart form, attributes, shipping placeholder and native related products |
| How to Play | `/como-se-juega/`; clearly fictional illustrated instructions and an honest video placeholder |
| FAQ | `/preguntas-frecuentes/`; native keyboard-operable Details blocks |
| Contact | `/contacto/`; accessible validation-only demo form |
| Demo legal notices | `/privacidad-demo/`, `/terminos-demo/`; provisional disclosures, not company policies |

Edit `theme/anotter-demo/theme.json` and `style.css` for design tokens, `patterns/` for reusable editorial components, and `templates/` for native WooCommerce block compositions. Changes appear through the existing source mounts. The block theme remains compatible with the Site Editor; database-saved template customizations can override files. Keep intentional changes in source control and preserve existing data. No separate frontend, custom cart state or frontend build step exists. The only small theme script focuses form results and exposes the native menu's expanded state.

Read [docs/design-system.md](docs/design-system.md) for tokens, component patterns, responsive/accessibility rules, and [docs/artwork-prompts.md](docs/artwork-prompts.md) for original artwork provenance. Manrope is self-hosted with its OFL license. Artwork is provisional and visibly marked DEMO.

## M03 purchasing workflow

Add native catalog/product items, edit quantities or coupons in Cart, then complete the Checkout block as a guest. Choose a Uruguay department and a DAC/UES sample rate, review the native total, acknowledge the provisional terms, and place an offline DEMO order. Confirmation displays the order, status, quantities, shipping/payment methods and an explicit **payment unverified** notice. Use only synthetic, non-contactable test data; no real delivery occurs.

| Zone | DAC sample UYU | UES sample UYU |
| --- | ---: | ---: |
| Montevideo (`UY:UY-MO`) | 180 | 210 |
| Rest of Uruguay (`UY`) | 260 | 290 |

Sample defaults live in `plugins/anotter-demo-foundation/includes/demo-config.php`. After initialization, edit native instances in WooCommerce → Settings → Shipping. Setup preserves those changes; theme templates contain no rates. A department change can reset selection to that zone's first method; review and select again. These are not official carrier tariffs, coverage or delivery promises. No pickup location has been authorized.

Global stock is managed, seeded products disallow backorders, initial low/out-of-stock thresholds are 2/0, and unpaid pending orders have a 30-minute hold. **Offline on-hold orders do not expire under that pending timer**: cancel unused demo orders in native administration to restore stock. Native guarded callbacks own every stock write. A documented supported reservation-read filter keeps a hold visible across the status-save/reduction window that concurrency tests exposed in the pinned version; no custom status or stock adjustment is added. HPOS remains active.

WooCommerce → Orders provides native administration, items, shipping, notes and status controls. WooCommerce → **Correos DEMO** provides protected local email previews: every outgoing WordPress message is suppressed; only `.invalid` recipients are captured in local/development environments, with at most ten bounded previews. The Clear action removes them. This is generation preview, not delivery. Native email colors/footer match the storefront.

```powershell
python scripts/dev.py wp -- eval-file /opt/anotter/tests/commerce.php
npm run test:m03
```

The M03 suite creates marked hidden products, restricted test coupons and native orders, including a two-customer last-unit race. It cancels/deletes only its owned fixtures through native APIs, preserving merchant stock. Captures default to ignored `work/m03-screenshots/`; set absolute `SCREENSHOT_DIR` to export. Run suites sequentially, on an idle local demo. After an interrupted run, inspect ownership and run `python scripts/dev.py wp -- eval-file /opt/anotter/tests/commerce-fixtures.php cleanup` before retrying. Never reset volumes for a test failure.

See [checkout-flow.md](docs/checkout-flow.md) for lifecycle/sequence diagrams, [shipping.md](docs/shipping.md) for rates/regions/limits, and [testing-m03.md](docs/testing-m03.md) for historical M03 results. M04 now installs the official plugin while retaining Blocks/HPOS; its account-dependent compatibility and real payment acceptance remain incomplete. See [testing-m04.md](docs/testing-m04.md).

### Contact form and future email setup

The custom form is deliberately small: native WordPress nonces, bounded field/email validation, a honeypot and an eight-attempt/minute transient counter keyed by a salted connection-address hash. POST field names are prefixed to avoid WordPress routing collisions. Inputs are escaped; feedback includes linked errors, `aria-invalid`, status/alert semantics and focus. The form never sends mail or persists the message; success explicitly says so. Use fictitious data.

A real contact channel requires an authorized destination, a configured/tested SMTP or transactional transport, a production spam/privacy review, and an explicitly implemented delivery path. Enabling an SMTP plugin alone is insufficient: the foundation plugin still suppresses all WordPress mail and this form never calls `wp_mail`. Delivery must only be reported after transport success. Those changes are outside M02.

To rotate the local administrator password without echoing it, edit `WP_ADMIN_PASSWORD` privately in `.env`, then run:

```powershell
python scripts/dev.py wp -- eval-file /opt/anotter/scripts/php/reset-admin-password.php
```

Installation uses native `wp_install()` inside a CLI-only PHP script, reading the environment silently. Never switch back to WP-CLI's non-TTY `--prompt` flow for secrets: it can echo both input and assembled arguments.

Compose syntax can be checked without an engine after `init-env`:

```powershell
docker compose config --quiet
```

Do not paste `docker compose config` without `--quiet` into issue reports: expanded configuration contains local credentials.

## Day-to-day operations

```powershell
python scripts/dev.py stop
python scripts/dev.py start
python scripts/dev.py smoke
# Full container removal preserves both named volumes:
docker compose down
python scripts/dev.py start
# Native WP-CLI, with arguments after --:
python scripts/dev.py wp -- plugin list
python scripts/dev.py wp -- option get woocommerce_currency
docker compose logs --tail 100 wordpress
docker compose ps
```

Theme/plugin edits on the host are immediately mounted read-only in the containers. Use the Site Editor normally; its changes persist in the database and can take precedence over theme files. Export intentional design changes into source control. WordPress core, uploads, installed third-party extensions and generated `.htaccess` stay in the persistent WordPress volume. `docker/wp-config.php` is project-owned configuration mounted read-only in both application and CLI; it reads credentials from the environment and does not modify core. Database data stays in its own persistent volume. No database port or administration tool is exposed.

Changing `WORDPRESS_IMAGE` does **not** automatically replace core already in the persistent volume. Upgrades require backups, a matching image pin and an explicit `wp core update --version=...` followed by `wp core update-db`; plugin upgrades likewise require an explicit `wp plugin update woocommerce --version=...`, then any WooCommerce database migration and full verification. Automatic updates are disabled to avoid silently drifting from the pinned demo. Keep pins reviewed for security; these are not immutable digest locks.

## Reset, backups and URL changes

Back up the database **and** WordPress volume before any reset or upgrade. `docker compose down` preserves them; `down --volumes` deletes them. A reset is explicit and destructive:

```powershell
python scripts/dev.py reset --confirm-reset anotter-demo
python scripts/dev.py setup
```

Replace `anotter-demo` with the exact `COMPOSE_PROJECT_NAME` if customized. Reset deletes this project's database, core, uploads and installed plugins; it retains host source and `.env`. Never use it to resolve a routine error when data matters.

For a **local port change** after installation: stop services, edit both `HTTP_PORT` and `SITE_URL` in `.env`, start, then explicitly migrate the old URL (including serialized content) with native WP-CLI:

```powershell
python scripts/dev.py stop
# Edit .env: HTTP_PORT=8090; SITE_URL=http://localhost:8090
python scripts/dev.py start
python scripts/dev.py wp -- search-replace http://localhost:8080 http://localhost:8090 --all-tables-with-prefix --skip-columns=guid --dry-run
# After reviewing the dry-run, execute the same command without --dry-run.
python scripts/dev.py wp -- search-replace http://localhost:8080 http://localhost:8090 --all-tables-with-prefix --skip-columns=guid
python scripts/dev.py smoke
```

For **temporary public HTTPS**, use the M04 [Cloudflare workflow](docs/cloudflare-tunnel.md), which sets an exact public hostname per request and keeps local database URLs intact. Do not use the permanent local-port search/replace procedure for a Quick Tunnel. Arbitrary forwarded headers remain untrusted; native Store API, `wc-api` callbacks and `/wp-json/` remain available. Stop the temporary tunnel after testing.

## Troubleshooting

- **Docker daemon unavailable / missing named pipe:** start Docker Desktop and select Linux containers. Check `docker version`, `docker info` and `docker context ls`. On this verified Windows host the working context is `desktop-linux`: set `$env:DOCKER_CONTEXT='desktop-linux'` in each PowerShell test/tunnel terminal if the default pipe is unavailable. WSL2/virtualization setup is an OS prerequisite; the project does not change Windows features or install a Docker daemon.
- **Port in use:** set a free `HTTP_PORT` and matching `SITE_URL` before setup; migrate the URL explicitly for existing sites.
- **Unhealthy database:** inspect `docker compose logs db`. Changing DB secrets in `.env` does not change accounts already stored in the volume. Restore the original values or rotate the database accounts explicitly; do not delete data as a shortcut.
- **Cannot write plugins/uploads:** use the provided named volumes and UID `33:33` for CLI. Do not bind-mount the whole WordPress directory from Windows or run setup as root. Ensure Docker Desktop can read the clone; source directories are deliberately read-only to containers.
- **Downloads fail:** check internet/DNS access to Docker Hub, WordPress.org and downloads.wordpress.org. Setup retries preserve completed data. A CLI download error is a failure, not a completed installation.
- **Partial core install before ownership marker:** inspect and back up the database. Setup deliberately refuses to adopt an unmarked existing site. For disposable empty-demo data only, use the explicit reset command and rerun.
- **404s:** `python scripts/dev.py wp -- rewrite flush --hard`; check Apache can read its generated `.htaccess` in the WordPress volume. The mounted `wp-cli.yml` declares `mod_rewrite` for the separate PHP CLI process.
- **Checkout returns to cart:** add a product first; native WooCommerce redirects empty checkout sessions to cart. M03 enables the labelled offline DEMO method for order creation.
- **Guests see a holding page:** check WooCommerce → Settings → Site visibility. M03 initialization disables coming-soon mode for the local demo; this does not expose the loopback server publicly.
- **A source edit seems ignored:** check Site Editor template customizations stored in the DB and browser caches before changing code.

Architecture and extension conventions: [docs/architecture.md](docs/architecture.md), [AGENTS.md](AGENTS.md). Scope: [docs/roadmap.md](docs/roadmap.md).
