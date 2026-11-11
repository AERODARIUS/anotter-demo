#!/bin/sh
set -eu
for directory in \
    /var/www/html/wp-content/themes/anotter-demo \
    /var/www/html/wp-content/plugins/anotter-demo-foundation \
    /opt/anotter/scripts/php /opt/anotter/tests; do
    find "$directory" -type f -name '*.php' -exec php -l '{}' \; > /tmp/anotter-lint.log 2>&1
    cat /tmp/anotter-lint.log
    if grep -qE 'Errors parsing|Parse error|Fatal error' /tmp/anotter-lint.log; then
        exit 1
    fi
done
php -l /var/www/html/wp-config.php
php -l /opt/anotter/public-origin.php
