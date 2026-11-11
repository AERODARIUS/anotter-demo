<?php
/** Live WordPress/WooCommerce state checks. Requires a bootstrapped WP-CLI process. */
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$failures = array();
$check = function ( $condition, $message ) use ( &$failures ) {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};
$check( is_plugin_active( 'woocommerce/woocommerce.php' ), 'WooCommerce must be active' );
$check( is_plugin_active( 'anotter-demo-foundation/anotter-demo-foundation.php' ), 'Demo foundation must be active' );
$check( 'anotter-demo' === get_stylesheet() && wp_is_block_theme(), 'The custom block theme must be active' );
if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is unavailable' );
}
$check( 'UYU' === get_woocommerce_currency(), 'Currency must be UYU' );
$check( '$' === html_entity_decode( get_woocommerce_currency_symbol( 'UYU' ) ), 'UYU symbol must be $' );
$check( 'UY' === get_option( 'woocommerce_default_country' ), 'Store country must be Uruguay' );
$check( array( 'UY' ) === array_keys( WC()->countries->get_allowed_countries() ), 'Selling countries must be Uruguay only' );
$check( array( 'UY' ) === array_keys( WC()->countries->get_shipping_countries() ), 'Shipping countries must be Uruguay only' );
$check( 'America/Montevideo' === wp_timezone_string(), 'Timezone must be America/Montevideo' );
$check( str_starts_with( get_locale(), 'es_' ), 'A Spanish locale must be active' );
$check( '/%postname%/' === get_option( 'permalink_structure' ), 'Pretty permalinks must be configured' );
$check( '0' === (string) get_option( 'blog_public' ), 'Search indexing must be discouraged' );
$check( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ), 'Guest checkout must be enabled' );
$check( 'yes' === get_option( 'woocommerce_manage_stock' ), 'Global inventory must be enabled' );

foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $page ) {
	$id = wc_get_page_id( $page );
	$check( $id > 0 && 'publish' === get_post_status( $id ), $page . ' page must be published' );
	if ( in_array( $page, array( 'cart', 'checkout' ), true ) ) {
		$content = get_post_field( 'post_content', $id );
		$check( has_block( 'woocommerce/' . $page, $content ) || has_shortcode( $content, 'woocommerce_' . $page ), $page . ' needs WooCommerce content' );
	}
}
$home = (int) get_option( 'page_on_front' );
$check( $home > 0 && 'publish' === get_post_status( $home ), 'Homepage must exist' );
$check( (bool) WP_Block_Patterns_Registry::get_instance()->get_registered( 'anotter-demo/demo-notice' ), 'Demo notice pattern must be registered' );

$fixtures = require '/opt/anotter/scripts/php/products.php';
foreach ( $fixtures as $fixture ) {
	$ids = get_posts( array(
		'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1,
		'meta_key' => '_sku', 'meta_value' => $fixture['sku'], 'fields' => 'ids',
	) );
	$check( 1 === count( $ids ), $fixture['sku'] . ' must exist exactly once' );
	$product = $ids ? wc_get_product( $ids[0] ) : false;
	if ( ! $product ) {
		continue;
	}
	$check( $product->get_manage_stock() && is_numeric( $product->get_stock_quantity() ) && $product->get_stock_quantity() >= 0, $fixture['sku'] . ' requires tracked, nonnegative stock' );
	$check( (float) $product->get_price() > 0, $fixture['sku'] . ' requires a positive price' );
	$check( 'yes' === $product->get_meta( '_anotter_demo' ), $fixture['sku'] . ' must be marked as demo' );
	$check( str_contains( $product->get_name(), 'DEMO' ), $fixture['sku'] . ' name must identify demo data' );
	$check( count( $product->get_category_ids() ) > 0, $fixture['sku'] . ' must have a category' );
	$file = get_attached_file( $product->get_image_id() );
	$check( $file && is_file( $file ), $fixture['sku'] . ' must have a persisted placeholder image' );
}

$zone = new WC_Shipping_Zone( (int) get_option( 'anotter_demo_shipping_zone_id' ) );
$methods = array();
foreach ( $zone->get_shipping_methods( true ) as $method ) {
	$methods[] = $method->id;
}
$check( in_array( 'flat_rate', $methods, true ) && in_array( 'local_pickup', $methods, true ), 'Demo zone needs flat rate and local pickup' );
foreach ( WC()->payment_gateways()->payment_gateways() as $id => $gateway ) {
	$allowed = get_option( 'anotter_demo_commerce_v3' ) ? array( 'bacs' ) : array( 'cod' );
	if ( get_option( 'anotter_demo_payments_v4' ) && anotter_demo_test_seller_ready() ) {
		$allowed[] = 'woo-mercado-pago-basic';
	}
	$check( 'yes' !== $gateway->enabled || in_array( $id, $allowed, true ), 'Only milestone-authorized demonstration gateways may be enabled' );
}
$mp_active = is_plugin_active( 'woocommerce-mercadopago/woocommerce-mercadopago.php' );
$check( get_option( 'anotter_demo_payments_v4' ) ? $mp_active : ! $mp_active, 'Mercado Pago activation must match M04 initialization' );
$mp_settings = get_option( 'woocommerce_woo-mercado-pago-basic_settings', array() );
if ( 'yes' === ( $mp_settings['enabled'] ?? 'no' ) ) {
	$check( 'no' === ( get_option( 'woocommerce_bacs_settings', array() )['enabled'] ?? '' ), 'BACS must be disabled during Checkout Pro demonstration' );
}
if ( $failures ) {
	foreach ( $failures as $failure ) {
		WP_CLI::warning( $failure );
	}
	WP_CLI::error( count( $failures ) . ' live assertions failed' );
}
WP_CLI::success( 'Live foundation assertions passed. Current stock and merchant edits were not changed.' );
