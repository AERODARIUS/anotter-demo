<?php
/** CLI-only supported gateway settings. Never print options containing secrets. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
$action = $args[0] ?? 'status';
$expected_seller_id = $args[1] ?? '';
if ( ! in_array( $action, array( 'initialize', 'offline', 'pro', 'status' ), true ) ) {
	WP_CLI::error( 'Use initialize, offline, pro or status.' );
}
if ( '' !== $expected_seller_id && ( 'pro' !== $action || ! preg_match( '/\A[1-9][0-9]*\z/', $expected_seller_id ) ) ) {
	WP_CLI::error( 'Use a positive numeric expected seller ID with pro mode.' );
}
if ( 'm01' !== get_option( 'anotter_demo_environment' ) ) {
	WP_CLI::error( 'Only managed independent demonstrations are supported.' );
}
$key = 'woocommerce_woo-mercado-pago-basic_settings';
$settings = get_option( $key, array() );
if ( 'initialize' === $action && ! get_option( 'anotter_demo_payments_v4' ) ) {
	// Never adopt an already-linked account or overwrite an existing payment setup.
	if ( get_option( '_mp_access_token_prod' ) || get_option( '_mp_access_token_test' ) || 'yes' === ( $settings['enabled'] ?? 'no' ) ) {
		WP_CLI::error( 'Existing Mercado Pago configuration needs private account review before initialization.' );
	}
	$settings = array_merge( $settings, array( 'enabled' => 'no', 'title' => 'Mercado Pago — DEMO · Checkout Pro', 'method' => 'redirect', 'currency_conversion' => 'no', 'auto_return' => 'yes', 'success_url' => '', 'failure_url' => '', 'pending_url' => '', 'gateway_discount' => '0', 'commission' => '0', 'binary_mode' => 'no' ) );
	update_option( $key, $settings );
	update_option( '_mp_debug_mode', 'no' );
	update_option( 'anotter_demo_payments_v4', 'initialized', false );
	WP_CLI::success( 'M04 initialized disabled; existing offline testing preserved. Link only a Uruguay test seller.' );
}
if ( 'pro' === $action ) {
	if ( ! ANOTTER_PUBLIC_REQUEST || ! anotter_demo_test_seller_ready() || 'UYU' !== get_woocommerce_currency() ) {
		WP_CLI::error( 'Requires public --url, UYU, an officially linked MLU test seller and documented sales mode. See docs/mercadopago-setup.md.' );
	}
	global $mercadopago;
	// Confirm the linked credential against authoritative provider identity. No token-prefix guessing.
	$response = wp_remote_get( 'https://api.mercadopago.com/users/me', array( 'timeout' => 25, 'headers' => array( 'Authorization' => 'Bearer ' . $mercadopago->sellerConfig->getCredentialsAccessToken() ) ) );
	$user = is_wp_error( $response ) ? array() : json_decode( wp_remote_retrieve_body( $response ), true );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) || ! in_array( 'test_user', $user['tags'] ?? array(), true ) || 'MLU' !== ( $user['site_id'] ?? '' ) ) {
		WP_CLI::error( 'Provider did not verify a Uruguay test seller. No gateways were enabled.' );
	}
	if ( '' !== $expected_seller_id && $expected_seller_id !== (string) ( $user['id'] ?? '' ) ) {
		WP_CLI::error( 'Provider returned a different seller than expected. No gateways were enabled.' );
	}
	if ( get_option( '_mp_custom_domain' ) ) {
		WP_CLI::error( 'Clear the official custom IPN URL so the plugin derives the current public receiver.' );
	}
	foreach ( array( 'success_url', 'failure_url', 'pending_url' ) as $field ) {
		if ( ! empty( $settings[ $field ] ) ) {
			WP_CLI::error( 'Clear custom return URLs in the official plugin before enabling this demo.' );
		}
	}
	$settings = array_merge( $settings, array( 'enabled' => 'yes', 'title' => 'Mercado Pago — DEMO · Checkout Pro', 'method' => 'redirect', 'currency_conversion' => 'no', 'gateway_discount' => '0', 'commission' => '0', 'auto_return' => 'yes' ) );
	update_option( $key, $settings );
	$bacs = get_option( 'woocommerce_bacs_settings', array() );
	$bacs['enabled'] = 'no';
	update_option( 'woocommerce_bacs_settings', $bacs );
	WP_CLI::success( 'Official Checkout Pro enabled for verified test seller; BACS disabled. No payment has been verified.' );
}
if ( 'offline' === $action ) {
	$settings['enabled'] = 'no';
	update_option( $key, $settings );
	$bacs = get_option( 'woocommerce_bacs_settings', array() );
	$bacs['enabled'] = 'yes';
	update_option( 'woocommerce_bacs_settings', $bacs );
	WP_CLI::success( 'Checkout Pro disabled; labelled native offline demonstration restored.' );
}
$settings = get_option( $key, array() );
echo 'ANOTTER_PAYMENTS=' . wp_json_encode( array(
	'public_origin' => ANOTTER_PUBLIC_REQUEST ? home_url() : null,
	'test_seller_ready' => anotter_demo_test_seller_ready(),
	'seller_site_saved' => (bool) get_option( '_site_id_v1' ),
	'collector_id_saved' => (bool) get_option( '_collector_id_v1' ),
	'test_seller_flag' => (bool) get_option( '_test_user_v1' ),
	'client_id_saved' => (bool) get_option( '_mp_client_id' ),
	'integration_id_saved' => (bool) get_option( '_mp_integration_id' ),
	'sales_mode' => 'no' === get_option( 'checkbox_checkout_test_mode', 'yes' ),
	'sales_credentials_saved' => (bool) get_option( '_mp_public_key_prod' ) && (bool) get_option( '_mp_access_token_prod' ),
	'test_credentials_saved' => (bool) get_option( '_mp_public_key_test' ) && (bool) get_option( '_mp_access_token_test' ),
	'checkout_pro_enabled' => 'yes' === ( $settings['enabled'] ?? 'no' ),
	'checkout_pro_redirect' => 'redirect' === ( $settings['method'] ?? '' ),
	'checkout_pro_auto_return' => 'yes' === ( $settings['auto_return'] ?? '' ),
	'custom_return_urls' => ! empty( $settings['success_url'] ) || ! empty( $settings['failure_url'] ) || ! empty( $settings['pending_url'] ),
	'custom_ipn_url' => (bool) get_option( '_mp_custom_domain' ),
	'offline_enabled' => 'yes' === ( get_option( 'woocommerce_bacs_settings', array() )['enabled'] ?? 'no' ),
	'currency' => get_woocommerce_currency(),
	'notification_url' => ANOTTER_PUBLIC_REQUEST ? add_query_arg( 'source_news', 'ipn', WC()->api_request_url( 'WC_WooMercadoPago_Basic_Gateway' ) ) : null,
) ) . "\n";
