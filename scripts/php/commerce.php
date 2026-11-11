<?php
/** One-time managed M03 migration. Repeated setup preserves subsequent admin edits. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'WooCommerce' ) ) {
	exit( 1 );
}
if ( get_option( 'anotter_demo_commerce_v3' ) ) {
	WP_CLI::success( 'M03 already initialized; merchant shipping, checkout and inventory edits preserved.' );
	return;
}
$config = require WP_PLUGIN_DIR . '/anotter-demo-foundation/includes/demo-config.php';
$states = WC()->countries->get_states( 'UY' );
if ( ! isset( $states['UY-MO'] ) || 'Montevideo' !== $states['UY-MO'] ) {
	WP_CLI::error( 'Review the installed WooCommerce Uruguay state identifiers before migrating shipping.' );
}
$map = get_option( 'anotter_demo_shipping_v3', array() );
foreach ( $config['shipping'] as $key => $settings ) {
	$zone = empty( $map[ $key ]['id'] ) ? new WC_Shipping_Zone() : new WC_Shipping_Zone( $map[ $key ]['id'] );
	if ( empty( $map[ $key ]['id'] ) ) {
		// Keep the old managed zone and method records for audit, after both new zones.
		$zone->set_zone_name( $settings['name'] );
		$zone->set_zone_order( $settings['order'] );
		$zone->add_location( $settings['location'], $settings['type'] );
		$map[ $key ] = array( 'id' => $zone->save(), 'methods' => array() );
		update_option( 'anotter_demo_shipping_v3', $map );
	}
	if ( ! $zone->get_id() ) {
		WP_CLI::error( 'A managed M03 shipping zone was removed during setup. Review before retrying.' );
	}
	foreach ( array( 'dac' => 'DAC', 'ues' => 'UES' ) as $carrier => $label ) {
		if ( isset( $map[ $key ]['methods'][ $carrier ] ) ) {
			continue;
		}
		$instance = $zone->add_shipping_method( 'flat_rate' );
		if ( ! $instance ) {
			WP_CLI::error( 'Could not add a native demo flat-rate instance.' );
		}
		update_option( 'woocommerce_flat_rate_' . $instance . '_settings', array(
			'title' => $label . ' — DEMO · tarifa de ejemplo',
			'tax_status' => 'none', 'cost' => wc_format_decimal( $settings[ $carrier ] ),
		) );
		$map[ $key ]['methods'][ $carrier ] = $instance;
		update_option( 'anotter_demo_shipping_v3', $map );
	}
}
$legacy = new WC_Shipping_Zone( (int) get_option( 'anotter_demo_shipping_zone_id' ) );
if ( $legacy->get_id() ) {
	$legacy->set_zone_order( 100 );
	$legacy->save();
	// Preserve original instances for audit. Both new zones match first, so these are unreachable.
}
WC_Cache_Helper::get_transient_version( 'shipping', true );
$options = array(
	'woocommerce_coming_soon' => 'no',
	'woocommerce_enable_guest_checkout' => 'yes',
	'woocommerce_enable_coupons' => 'yes',
	'woocommerce_manage_stock' => 'yes',
	'woocommerce_hold_stock_minutes' => (string) $config['hold_minutes'],
	'woocommerce_notify_low_stock_amount' => (string) $config['low_stock'],
	'woocommerce_notify_no_stock_amount' => (string) $config['no_stock'],
	'woocommerce_notify_low_stock' => 'yes', 'woocommerce_notify_no_stock' => 'yes',
	'woocommerce_checkout_phone_field' => 'required',
	'woocommerce_email_base_color' => '#124c46',
	'woocommerce_email_background_color' => '#faf7ef',
	'woocommerce_email_body_background_color' => '#ffffff',
	'woocommerce_email_text_color' => '#182e2a',
	'woocommerce_email_footer_text' => 'AnOtter Game — DEMO independiente. Sin cobros ni envíos reales. Pago no verificado.',
);
foreach ( $options as $name => $value ) {
	update_option( $name, $value );
}
$pages = get_option( 'anotter_demo_pages_v2', array() );
if ( ! get_option( 'woocommerce_terms_page_id' ) && ! empty( $pages['terms'] ) ) {
	update_option( 'woocommerce_terms_page_id', $pages['terms'] );
}
if ( ! get_option( 'wp_page_for_privacy_policy' ) && ! empty( $pages['privacy'] ) ) {
	update_option( 'wp_page_for_privacy_policy', $pages['privacy'] );
}
// Native BACS creates on-hold orders and reduces stock without marking payment complete.
update_option( 'woocommerce_bacs_settings', array(
	'enabled' => 'yes', 'title' => 'DEMO — Pedido de prueba · sin pago',
	'description' => 'Solo demostración. No transfieras dinero. El pedido quedará en espera; el pago no está verificado.',
	'instructions' => 'Pedido DEMO en espera. Pago no verificado. No transfieras dinero: no hay datos bancarios ni entrega real.',
) );
update_option( 'woocommerce_bacs_accounts', array() );
$cod = get_option( 'woocommerce_cod_settings', array() );
$cod['enabled'] = 'no';
update_option( 'woocommerce_cod_settings', $cod );
foreach ( require __DIR__ . '/products.php' as $fixture ) {
	$product = wc_get_product( wc_get_product_id_by_sku( $fixture['sku'] ) );
	if ( $product && 'yes' === $product->get_meta( '_anotter_demo' ) ) {
		$product->set_manage_stock( true );
		$product->set_backorders( 'no' );
		if ( '' === $product->get_low_stock_amount() ) {
			$product->set_low_stock_amount( $config['low_stock'] );
		}
		$product->save();
	}
}
update_option( 'anotter_demo_commerce_v3', 1 );
WP_CLI::success( 'M03 initialized: native blocks, Uruguay demo shipping, offline BACS and stock lifecycle.' );
