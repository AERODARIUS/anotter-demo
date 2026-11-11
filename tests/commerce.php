<?php
/** Read-only live M03 configuration acceptance. Browser tests exercise order-producing paths. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
$errors = array();
$check = function ( $condition, $message ) use ( &$errors ) {
	if ( ! $condition ) {
		$errors[] = $message;
	}
};
$check( get_option( 'anotter_demo_commerce_v3' ) && get_option( 'anotter_demo_checkout_pages_v3' ), 'M03 migrations must be complete' );
$check( 'no' === get_option( 'woocommerce_coming_soon' ), 'Guest store access must be available' );
$check( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ), 'Guest checkout must remain available' );
$check( 'UYU' === get_woocommerce_currency() && array( 'UY' ) === array_keys( WC()->countries->get_shipping_countries() ), 'UYU and Uruguay-only shipping must remain intact' );
$check( 19 === count( WC()->countries->get_states( 'UY' ) ), 'Inspect all 19 installed native departments' );
$check( 'required' === get_option( 'woocommerce_checkout_phone_field' ), 'Native phone field must be required' );
$check( 'yes' === get_option( 'woocommerce_manage_stock' ) && (int) get_option( 'woocommerce_hold_stock_minutes' ) > 0, 'Global stock and bounded unpaid hold must be configured' );
$check( 'yes' === get_option( 'woocommerce_enable_coupons' ), 'Native coupon support must remain enabled' );
$check( 'no' === get_option( 'woocommerce_calc_taxes' ), 'No invented tax rates may be applied' );
$check( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(), 'HPOS must remain active' );
$map = get_option( 'anotter_demo_shipping_v3', array() );
foreach ( array( 'UY-MO' => 'montevideo', 'UY-CA' => 'interior', 'UY-AR' => 'interior' ) as $state => $key ) {
	$zone = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'UY', 'state' => $state, 'postcode' => '', 'city' => '' ) ) );
	$check( $zone->get_id() === (int) ( $map[ $key ]['id'] ?? -1 ), 'Native zone precedence must match ' . $state );
	$methods = $zone->get_shipping_methods( true );
	$check( 2 === count( $methods ), 'Each matched zone must expose only DAC/UES estimates' );
	foreach ( $methods as $method ) {
		$check( 'flat_rate' === $method->id && str_contains( $method->title, 'DEMO' ), 'Carriers must be native labelled flat-rate estimates' );
	}
}
$foreign = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'AR', 'state' => '', 'postcode' => '', 'city' => '' ) ) );
$check( empty( $foreign->get_shipping_methods( true ) ), 'Foreign destinations must not expose shipping methods' );
$bacs = get_option( 'woocommerce_bacs_settings', array() );
$check( 'yes' === ( $bacs['enabled'] ?? '' ) && str_contains( $bacs['title'] ?? '', 'DEMO' ), 'The native offline gateway must be visibly test-only' );
$check( empty( get_option( 'woocommerce_bacs_accounts' ) ), 'No real bank account details may be present' );
foreach ( require '/opt/anotter/scripts/php/products.php' as $fixture ) {
	$product = wc_get_product( wc_get_product_id_by_sku( $fixture['sku'] ) );
	$check( $product && $product->get_manage_stock() && 'no' === $product->get_backorders() && wc_get_low_stock_amount( $product ) >= 0, 'Tracked physical inventory, no backorders and low-stock threshold required for ' . $fixture['sku'] );
}
foreach ( array( 'cart', 'checkout' ) as $name ) {
	$check( has_block( 'woocommerce/' . $name, get_post_field( 'post_content', wc_get_page_id( $name ) ) ), 'Native ' . $name . ' block must remain present' );
}
$before = get_option( 'anotter_demo_mail_capture', array() );
$suppressed = apply_filters( 'pre_wp_mail', null, array( 'to' => 'do-not-send@example.org', 'subject' => 'Do not retain', 'message' => 'Synthetic suppression assertion', 'headers' => array(), 'attachments' => array() ) );
$check( true === $suppressed && $before === get_option( 'anotter_demo_mail_capture', array() ), 'External mail must be suppressed without capture' );
$check( count( $before ) <= 10, 'Local mail capture must be bounded' );
foreach ( $before as $mail ) {
	$check( str_contains( $mail['to'], '.invalid' ), 'Captured recipients must use reserved test addresses' );
}
if ( $errors ) {
	foreach ( $errors as $error ) {
		WP_CLI::warning( $error );
	}
	WP_CLI::error( 'M03 configuration assertions failed.' );
}
WP_CLI::success( 'M03 native regions/zones, stock, guest checkout, offline gateway, HPOS, taxes and mail-suppression assertions passed.' );
