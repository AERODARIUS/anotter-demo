#!/bin/sh
# Executed inside the official WP-CLI container, never on the Windows host.
set -eu

if ! wp core is-installed >/dev/null 2>&1; then
    # Distinguish a genuinely empty DB from an existing, partially installed site.
    wp eval-file /opt/anotter/scripts/php/preinstall.php --skip-wordpress
    # WP-CLI --prompt echoes input/assembled arguments without a TTY. Never use it for secrets.
    wp eval-file /opt/anotter/scripts/php/install.php --skip-wordpress
fi

wp eval-file /opt/anotter/scripts/php/guard.php

# Ask WordPress which translation packages actually exist for this core release.
locale="$(wp eval-file /opt/anotter/scripts/php/locale.php)"
if [ "$locale" != en_US ]; then
    if ! wp language core is-installed "$locale"; then
        wp language core install "$locale"
    fi
fi
wp site switch-language "$locale"

if ! wp plugin is-installed woocommerce; then
    wp plugin install woocommerce --version="$WOOCOMMERCE_VERSION"
fi
installed="$(wp plugin get woocommerce --field=version)"
if [ "$installed" != "$WOOCOMMERCE_VERSION" ]; then
    echo 'WooCommerce version differs from .env. Review an explicit upgrade; setup never replaces it.' >&2
    exit 1
fi
if ! wp language plugin is-installed woocommerce "$locale"; then
    wp language plugin install woocommerce "$locale"
fi
if wp language plugin is-installed woocommerce "$locale"; then
    wp option update anotter_demo_woocommerce_locale "$locale"
else
    wp language plugin install woocommerce "$WP_FALLBACK_LOCALE"
    wp language plugin is-installed woocommerce "$WP_FALLBACK_LOCALE"
    wp option update anotter_demo_woocommerce_locale "$WP_FALLBACK_LOCALE"
fi
wp plugin activate woocommerce anotter-demo-foundation
wp theme activate anotter-demo
wp eval-file /opt/anotter/scripts/php/configure.php
wp eval-file /opt/anotter/scripts/php/seed.php
wp eval-file /opt/anotter/scripts/php/storefront.php
wp eval-file /opt/anotter/scripts/php/commerce.php
wp eval-file /opt/anotter/scripts/php/checkout-pages.php
if ! wp plugin is-installed woocommerce-mercadopago; then
    wp plugin install woocommerce-mercadopago --version="${MERCADOPAGO_VERSION:-8.9.7}"
fi
installed="$(wp plugin get woocommerce-mercadopago --field=version)"
if [ "$installed" != "${MERCADOPAGO_VERSION:-8.9.7}" ]; then
    echo 'Mercado Pago version differs from configured pin. Review an explicit upgrade.' >&2
    exit 1
fi
wp plugin verify-checksums woocommerce-mercadopago
wp plugin activate woocommerce-mercadopago
wp eval-file /opt/anotter/scripts/php/payments.php initialize
wp rewrite flush --hard
wp eval-file /opt/anotter/tests/smoke.php
