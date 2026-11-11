<?php
/** Project configuration, mounted read-only. WordPress core is unmodified. */

foreach ( array( 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST' ) as $anotter_constant ) {
	$anotter_value = getenv( 'WORDPRESS_' . $anotter_constant );
	if ( false === $anotter_value || '' === $anotter_value ) {
		throw new RuntimeException( 'Missing required database environment variable.' );
	}
	define( $anotter_constant, $anotter_value );
}
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $anotter_constant ) {
	$anotter_value = getenv( 'WORDPRESS_' . $anotter_constant );
	if ( false === $anotter_value || strlen( $anotter_value ) < 24 ) {
		throw new RuntimeException( 'Missing or weak WordPress authentication key. Run init-env before setup.' );
	}
	define( $anotter_constant, $anotter_value );
}
$table_prefix = 'wp_';

$anotter_environment = getenv( 'APP_ENV' ) ?: 'local';
define( 'WP_ENVIRONMENT_TYPE', $anotter_environment );
define( 'WP_DEBUG', 'local' === $anotter_environment );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', 'local' === $anotter_environment ? '/proc/self/fd/2' : false );
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
require '/opt/anotter/public-origin.php';
unset( $anotter_constant, $anotter_value, $anotter_environment );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
