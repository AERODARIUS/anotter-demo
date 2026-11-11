# M05 demonstration runbook

## Windows development workspace

In each PowerShell terminal for the main demo, select the existing private environment before running project commands.

```powershell
# Run from the root of your local clone.
$env:ANOTTER_ENV_FILE = Join-Path $env:LOCALAPPDATA 'AnOtterDemo\main.env'
$env:DOCKER_CONTEXT = 'desktop-linux'
if (-not (Test-Path -LiteralPath $env:ANOTTER_ENV_FILE -PathType Leaf)) {
    throw 'The existing main.env was not found in LocalAppData.'
}
docker compose --env-file $env:ANOTTER_ENV_FILE config --quiet
if ($LASTEXITCODE -ne 0) { throw 'Compose configuration validation failed.' }
```

Keep private environments and database backups under LocalAppData. OneDrive can sync files ignored by Git. The original checkout holds historical logs and backups. Run `npm ci` in the new checkout when browser tests are needed.

## Start and diagnose

Check the Linux engine and the two existing volumes before starting the migrated checkout.

```powershell
$dockerOs = docker version --format '{{if .Server}}{{.Server.Os}}{{end}}'
if ($LASTEXITCODE -ne 0 -or $dockerOs -ne 'linux') {
    throw 'The existing Docker Linux engine is unavailable.'
}
docker volume inspect --format '{{.Name}}' anotter-demo_database anotter-demo_wordpress
if ($LASTEXITCODE -ne 0) { throw 'The existing demo volumes are unavailable in this Docker context.' }
python scripts/dev.py start
if ($LASTEXITCODE -ne 0) { throw 'The demo did not start.' }
python scripts/dev.py doctor
```

The volume check prevents starting a fresh store when the existing data is unavailable. For Docker Desktop with Hyper-V, allow this exact checkout folder under **Settings → Resources → File sharing**. The live start and read-only source mounts from OneDrive were verified on 2026-11-10. See [verification.md](verification.md).

Open <http://localhost:8080/> after startup. `doctor` reports local checks and payment prerequisites separately. Exit 0 can include BLOCKED payment checks. If the existing managed demo starts with missing fixtures, review the diagnostics before using `python scripts/dev.py setup` to repair them.

## M06 resume checkpoint

Reuse the existing Uruguay test seller and separate test buyer. The live store was unlinked at M06 preflight. Use the official plugin's account-linking action from the exact public HTTPS origin while signed in as the test seller. The [M06 setup guide](mercadopago-setup.md) records the Developer Dashboard and plugin onboarding paths. See [M06 execution evidence](testing-m06.md) and the [payment acceptance report](payment-acceptance-report.md).

The demo uses fictional products and customers. Use an idle demo and a dedicated browser profile without real accounts, password-manager overlays, extensions or saved addresses. Capture at 1920×1080 and rehearse at 1440×900. Check browser zoom and system scaling, silence notifications, and keep credentials, account details, order keys and developer tools out of the capture.

## Deterministic dataset and safe reset

The authoritative fixture definitions are `scripts/php/products.php`: DEMO-CARDS-001 = UYU 890 / 24 units, DEMO-EXP-001 = UYU 390 / 12, DEMO-ACC-001 = UYU 250 / 30. Original artwork is supplied by the existing M02 setup. Do not replace authorized custom artwork or import proprietary brand assets. Shipping defaults are in the foundation plugin's `includes/demo-config.php`: Montevideo DAC/UES 180/210, interior 260/290, all UYU/sample only.

Stop the tunnel and restore local-only origin first. Close buyer sessions. Reset defaults to **dry run**, checks actual environment/managed marker/loopback URL, preflights all selected objects before writing and refuses production/staging or configured public exposure. It changes only three owned simple products' price/stock and the documented sample shipping/Uruguay checkout settings. It preserves users, IDs, pages, media, plugins, theme, authentication configuration and payment credentials/enablement. Missing, disabled or renamed methods require native administrative review; reset does not adopt them. Run on an idle environment; it is not a concurrent maintenance transaction.

```powershell
python scripts/dev.py demo-reset --session rehearsal --dry-run
# Review its plan. Explicit confirmation is required for ANY write:
python scripts/dev.py demo-reset --session rehearsal --apply --confirm-project anotter-demo
python scripts/dev.py doctor
```

Restore payment mode separately and deliberately: `python scripts/dev.py payments --mode offline` for M05 offline rehearsal. BACS has no bank details, leaves the order unpaid/on-hold and natively reduces stock. Never set processing/completed to fake financial confirmation.

For an offline rehearsal use **demo-m05@example.invalid**, PRUEBA / NO ENTREGAR, street DATO SINTETICO - NO ENTREGAR, Uruguay, Montevideo, postcode 10000, phone 00000000. These are non-contactable synthetic data, not a real address/telephone or shipping assurance. Choose quantity 1, Montevideo UES: product 890 + sample shipping 210 = **UYU 1,100**, with inherited demo taxes disabled and no coupon. Interior UES is 290; review the native total after changing department.

To remove an order, explicitly enroll its native ID in a session. Only unpaid BACS orders using the reserved identity, DEMO metadata and exclusively the three owned products qualify. Paid orders, transaction IDs, refunds and Mercado Pago orders are refused. The broad `_anotter_demo` flag alone never authorizes deletion.

```powershell
# Replace 123 with the actual rehearsal order ID; never rely on a fixed ID.
python scripts/dev.py demo-tag --order 123 --session rehearsal --dry-run
python scripts/dev.py demo-tag --order 123 --session rehearsal --apply --confirm-project anotter-demo
python scripts/dev.py demo-reset --session rehearsal --delete-orders --dry-run
python scripts/dev.py demo-reset --session rehearsal --delete-orders --apply --confirm-project anotter-demo
```

Cancellation/restoration uses native WooCommerce callbacks, then CRUD deletion and deterministic fixture stock restoration. Unknown active orders referencing these products block the entire reset before writes; cancel/review them in native administration, never mass-delete. Unselected historical orders are preserved. Repeating the reset is safe. Gateway credentials, financial evidence, global browser sessions and historical mail previews are not deleted. Start a fresh buyer profile after reset. Clear synthetic mail previews separately through WooCommerce → Correos DEMO using its protected Clear action when appropriate. Global previews require administrator settings capability as well as store management; Shop Managers cannot access account-recovery messages.

## Separate QA environment

Start with the main demo terminal configured above. If QA containers or volumes already exist, reuse the private environment that created them. On this machine the stopped QA container may still refer to the original checkout's private QA environment. Use `scripts/init-qa.py` only when provisioning a new isolated QA project.

```powershell
$mainEnvFile = $env:ANOTTER_ENV_FILE
$qaEnvFile = Join-Path (Split-Path -Parent $mainEnvFile) 'qa.env'
if (-not (Test-Path -LiteralPath $qaEnvFile -PathType Leaf)) {
    throw 'The private QA environment file was not found.'
}
$env:ANOTTER_ENV_FILE = $qaEnvFile
```

Run the following commands in order. Stop on the first failure and finish fixture cleanup before stopping QA or changing environments.

```powershell
python scripts/dev.py setup
npm ci
npx playwright install chromium firefox webkit
npm run test:reset
npm run test:m05
python scripts/dev.py wp -- eval-file /opt/anotter/tests/security.php
```

When QA is finished, stop its services and restore the main environment.

```powershell
python scripts/dev.py stop
if ($LASTEXITCODE -ne 0) { throw 'QA did not stop. Check Docker before switching environments.' }
$env:ANOTTER_ENV_FILE = $mainEnvFile
```

The QA environment uses project `anotter-m05-qa`, port 8095 and separate named volumes. The reset integration test refuses other projects. Run fixture suites sequentially against an idle database. Browser contexts are isolated and owned fixtures are removed in `finally`. If interrupted, use the fixture cleanup steps in the applicable testing guide.

## Public HTTPS and payment readiness

Select the same private environment and Docker context in both terminals. Finish the local checks before starting the tunnel.

```powershell
python scripts/dev.py tunnel
# Keep terminal A running; in terminal B:
python scripts/dev.py doctor
python scripts/dev.py payments --mode status
python scripts/dev.py test-m04
npm run test:m04
```

Open the exact printed HTTPS hostname in a fresh public buyer session. Check product, cart and checkout, exact public URLs and callbacks, and mixed-content errors. For M06, use `payments --mode pro --seller-id $env:ANOTTER_TEST_SELLER_ID` after official linking and the documented test-seller sales-mode step. The command verifies that seller with Mercado Pago before enabling Checkout Pro and disabling BACS. Record payment acceptance from genuine provider transactions and the resulting native orders. Provider callbacks need noninteractive HTTPS.

Until M06, an internal rehearsal stops at the payment handoff and explicitly says the real provider portion is pending. Do not splice an offline BACS confirmation into the payment scene. For the eventual provider demo, keep the tunnel alive until actual notifications and WooCommerce state are observed; follow `m06-acceptance-plan.md`.

## Administration and teardown

Open the local/public `/wp-admin/` for the intended origin. Sign in with the administrator credentials in the selected private environment file. Use WooCommerce → Orders (`admin.php?page=wc-orders` with HPOS), select the current DEMO order, inspect items/quantities, UYU total, persisted UES/DAC method, unpaid/paid state and notes. WooCommerce → Products (`edit.php?post_type=product`) shows stock. Do not show passwords, official linking screens, API tokens, cookies, order keys, real account information or card data. Administrator access is intentionally exposed while the tunnel is running; use a strong unique password and synthetic data only. Noindex is not access control.

Ctrl+C in terminal A stops this helper's tunnel and restores the previous origin. On abnormal termination, stop only that task's cloudflared process, then `python scripts/dev.py public-url`. Run doctor/smoke locally; public access should no longer work. Keep provider acceptance evidence privately if any exists. Use selective reset only for enrolled offline rehearsals. Stop QA when finished; main volumes remain intact.

## Reviewed core patch and recovery

M05 reviewed the official WordPress 7.1.4 security release and tested it in separate QA before updating the main demo. Existing volumes require an explicit patch; changing the image tag alone does not update core. Preserve private credentials and the working tree, then run `python scripts/backup.py` for a timestamped private MySQL dump and WordPress-volume archive under `%LOCALAPPDATA%\AnOtterDemo\private-backups\` when using the migrated environment. The matching MySQL client is required; Alpine WP-CLI's MariaDB dump client cannot authenticate this MySQL installation. Mounted host config/theme/plugin source is excluded from the archive and must be preserved separately.

The reviewed sequence is `wp core update --version=7.1.4 --locale=en_US`, `wp core update-db`, `wp core verify-checksums`, set only `WORDPRESS_IMAGE=wordpress:7.1.4-php8.3-apache` in the private selected environment, recreate/start, smoke and full regressions. The `en_US` package avoids the nonexistent es_UY core zip; the stored site locale and installed Spanish fallback are preserved. Do not apply this automatically on future setup.

Rollback is an explicit maintenance operation on idle local services: stop the selected project, preserve a current backup, restore the same project's prepatch database using the matching MySQL client and the volume archive using the original image and correct UID, restore its matching private image pin and host source/configuration, then smoke/regress. Never point restore at another project's volumes. M05 created backups and checked archive readability; a full disaster-recovery restore was not executed against the working demo. Partial failed backup directories are not valid restore points.
