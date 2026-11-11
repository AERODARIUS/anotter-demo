<?php
/** Silent native installation. Never print the return value: it can contain a password. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
define( 'WP_INSTALLING', true );
define( 'WP_HOME', rtrim( getenv( 'SITE_URL' ), '/' ) );
define( 'WP_SITEURL', WP_HOME );
$_SERVER['HTTP_HOST'] = parse_url( WP_HOME, PHP_URL_HOST ) ?: 'localhost';
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
add_filter( 'pre_wp_mail', '__return_true' );
$result = wp_install( 'AnOtter Game — Demo', getenv( 'WP_ADMIN_USER' ), getenv( 'WP_ADMIN_EMAIL' ), false, '', getenv( 'WP_ADMIN_PASSWORD' ) );
if ( empty( $result['user_id'] ) ) {
	WP_CLI::error( 'WordPress installation did not create an administrator.' );
}
add_option( 'anotter_demo_environment', 'm01' );
WP_CLI::success( 'WordPress installed; credentials were read privately from the environment.' );
