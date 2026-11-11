<?php
/** Read-only M04 installation/configuration checks. Never invent a provider event. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$errors = array();
$check = function ( $ok, $message ) use ( &$errors ) {
	if ( ! $ok ) {
		$errors[] = $message;
	}
};
$file = 'woocommerce-mercadopago/woocommerce-mercadopago.php';
$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $file, false, false );
$check( is_plugin_active( $file ), 'Official Mercado Pago plugin must be active' );
$check( ( getenv( 'MERCADOPAGO_VERSION' ) ?: '8.9.7' ) === ( $data['Version'] ?? '' ), 'Mercado Pago must match the documented pin' );
$gateways = WC()->payment_gateways()->payment_gateways();
$check( isset( $gateways['woo-mercado-pago-basic'] ), 'Official Checkout Pro gateway must register' );
$check( has_action( 'woocommerce_api_wc_woomercadopago_basic_gateway' ), 'Official notification receiver must register' );
$check( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(), 'HPOS must remain enabled' );
$check( has_block( 'woocommerce/checkout', get_post_field( 'post_content', wc_get_page_id( 'checkout' ) ) ), 'Preserve Checkout Blocks' );
$check( class_exists( '\MercadoPago\Woocommerce\Blocks\BasicBlock' ), 'Official Checkout Pro block support must load' );
$check( 'UYU' === get_woocommerce_currency(), 'Currency must remain UYU' );
foreach ( array( 'UY-MO', 'UY-CA' ) as $state ) {
	$zone = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'UY', 'state' => $state, 'postcode' => '', 'city' => '' ) ) );
	$methods = $zone->get_shipping_methods( true );
	$check( 2 === count( $methods ), 'Preserve two shipping estimates in ' . $state );
	foreach ( $methods as $method ) {
		$check( 'flat_rate' === $method->id && str_contains( $method->title, 'DEMO' ), 'Retain labelled native sample rates' );
	}
}
$settings = get_option( 'woocommerce_woo-mercado-pago-basic_settings', array() );
$check( 'redirect' === ( $settings['method'] ?? '' ), 'Checkout Pro must use redirect experience' );
$check( 'no' === ( $settings['currency_conversion'] ?? '' ), 'Do not convert UYU' );
foreach ( array( 'success_url', 'failure_url', 'pending_url' ) as $field ) {
	$check( empty( $settings[ $field ] ), 'Use native per-order return URLs: ' . $field );
}
$available = WC()->payment_gateways()->get_available_payment_gateways();
if ( 'yes' === ( $settings['enabled'] ?? '' ) ) {
	$check( anotter_demo_test_seller_ready(), 'Enabled Checkout Pro requires the official MLU test seller identity' );
	$check( 'no' === ( get_option( 'woocommerce_bacs_settings', array() )['enabled'] ?? '' ), 'Disable offline gateway during the MP demo' );
} else {
	$check( ! isset( $available['woo-mercado-pago-basic'] ), 'Disabled/unconfigured MP must be unavailable' );
}
if ( ANOTTER_PUBLIC_REQUEST ) {
	foreach ( array( home_url(), site_url(), wc_get_checkout_url(), WC()->api_request_url( 'WC_WooMercadoPago_Basic_Gateway' ) ) as $url ) {
		$check( str_starts_with( $url, rtrim( getenv( 'ANOTTER_PUBLIC_URL' ), '/' ) . '/' ) || $url === rtrim( getenv( 'ANOTTER_PUBLIC_URL' ), '/' ), 'Public URLs must use exact HTTPS tunnel origin' );
	}
	$check( is_ssl(), 'WordPress must recognize public HTTPS' );
} else {
	$check( ! isset( $available['woo-mercado-pago-basic'] ), 'Local origin must not offer MP' );
}
$check( empty( get_option( 'woocommerce_bacs_accounts' ) ), 'No real offline bank accounts' );
if ( get_option( '_mp_access_token_prod' ) || get_option( '_mp_access_token_test' ) ) {
	global $mercadopago;
	$check( $mercadopago->sellerConfig->isTestUser() && 'MLU' === $mercadopago->sellerConfig->getSiteId(), 'Any linked credential must belong to the official Uruguay test-seller identity' );
}
if ( $errors ) {
	foreach ( $errors as $error ) {
		WP_CLI::warning( $error );
	}
	WP_CLI::error( 'M04 configuration checks failed.' );
}
WP_CLI::success( 'M04 installation, gateway, Blocks/HPOS, UYU, shipping, return-origin and eligibility checks passed. Provider transactions/notifications remain separately unverified.' );
