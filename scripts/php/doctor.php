<?php
/** Read-only state diagnostics; never emit private values or full options. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
$check = function ( $condition, $name ) { WP_CLI::log( ( $condition ? 'PASS: ' : 'FAIL: ' ) . $name ); };
$check( is_blog_installed() && 'm01' === get_option( 'anotter_demo_environment' ), 'Managed WordPress installation' );
$check( untrailingslashit( get_option( 'home' ) ) === untrailingslashit( getenv( 'SITE_URL' ) ) && untrailingslashit( get_option( 'siteurl' ) ) === untrailingslashit( getenv( 'SITE_URL' ) ), 'Canonical hostname matches configuration' );
$check( 'anotter-demo' === get_stylesheet(), 'Custom block theme active' );
$check( class_exists( 'WooCommerce' ), 'WooCommerce active' );
if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::log( 'NOT RUN: Commerce diagnostics; WooCommerce absent' );
	return;
}
$check( 'UYU' === get_woocommerce_currency(), 'Currency UYU' );
foreach ( require __DIR__ . '/products.php' as $fixture ) {
	$product = wc_get_product( wc_get_product_id_by_sku( $fixture['sku'] ) );
	$check( $product && 'yes' === $product->get_meta( '_anotter_demo' ) && $product->is_visible() && $product->is_in_stock() && $product->get_stock_quantity() > 0 && $product->get_image_id() && file_exists( get_attached_file( $product->get_image_id() ) ), 'Demo product/image/stock: ' . $fixture['sku'] );
}
foreach ( array( 'UY-MO', 'UY-CA' ) as $state ) {
	$zone = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'UY', 'state' => $state, 'postcode' => '' ) ) );
	$titles = array_map( function ( $method ) { return $method->get_title(); }, $zone->get_shipping_methods( true ) );
	foreach ( array( 'DAC', 'UES' ) as $carrier ) {
		$check( (bool) array_filter( $titles, function ( $title ) use ( $carrier ) { return str_starts_with( $title, $carrier . ' — DEMO' ); } ), $state . ' ' . $carrier . ' sample method' );
	}
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$check( is_plugin_active( 'woocommerce-mercadopago/woocommerce-mercadopago.php' ), 'Official Mercado Pago plugin installed/active' );
global $mercadopago;
$credentials = isset( $mercadopago->sellerConfig ) && '' !== $mercadopago->sellerConfig->getCredentialsAccessToken();
WP_CLI::log( ( $credentials ? 'PASS: ' : 'BLOCKED: ' ) . 'Mercado Pago credential presence (not payment validation)' );
WP_CLI::log( ( anotter_demo_test_seller_ready() ? 'PASS: ' : 'BLOCKED: ' ) . 'Official MLU test-seller flags and selected mode (provider acceptance pending)' );
$metadata = get_file_data( WP_PLUGIN_DIR . '/woocommerce-mercadopago/woocommerce-mercadopago.php', array( 'version' => 'Version', 'tested' => 'WC tested up to' ) );
$covered = ! empty( $metadata['tested'] ) && version_compare( WC_VERSION, $metadata['tested'], '<=' );
WP_CLI::log( sprintf( '%s: MP %s declared WC tested-through %s; installed WC %s. Credential-dependent acceptance still requires M06.', $covered ? 'PASS' : 'BLOCKED', $metadata['version'] ?: 'unknown', $metadata['tested'] ?: 'undeclared', WC_VERSION ) );
