<?php
/** Read-only, non-customer M03 configuration for real-service browser checks. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
$data = array(
	'wordpress' => get_bloginfo( 'version' ), 'woocommerce' => WC_VERSION,
	'currency' => get_woocommerce_currency(),
	'states' => WC()->countries->get_states( 'UY' ),
	'address_fields' => WC()->countries->get_address_fields( 'UY', 'shipping_' ),
	'pages' => array( 'home' => home_url( '/' ), 'shop' => wc_get_page_permalink( 'shop' ), 'cart' => wc_get_cart_url(), 'checkout' => wc_get_checkout_url(), 'admin' => admin_url() ),
	'content' => array(), 'options' => array(), 'zones' => array(), 'products' => array(),
	'hpos' => \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(),
	'config' => require WP_PLUGIN_DIR . '/anotter-demo-foundation/includes/demo-config.php',
);
foreach ( array( 'cart', 'checkout' ) as $page ) {
	$data['content'][ $page ] = get_post_field( 'post_content', wc_get_page_id( $page ) );
}
foreach ( array( 'woocommerce_manage_stock', 'woocommerce_hold_stock_minutes', 'woocommerce_notify_low_stock_amount', 'woocommerce_notify_no_stock_amount', 'woocommerce_hide_out_of_stock_items', 'woocommerce_calc_taxes', 'woocommerce_enable_coupons', 'woocommerce_enable_guest_checkout', 'woocommerce_checkout_phone_field', 'woocommerce_bacs_settings', 'woocommerce_email_base_color', 'woocommerce_email_background_color' ) as $key ) {
	$data['options'][ $key ] = get_option( $key );
}
foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
	$zone['shipping_methods'] = array_map( function ( $method ) {
		return array( 'id' => $method->id, 'instance' => $method->instance_id, 'enabled' => $method->enabled, 'settings' => $method->instance_settings );
	}, $zone['shipping_methods'] );
	$data['zones'][] = $zone;
}
foreach ( wc_get_products( array( 'limit' => -1 ) ) as $product ) {
	$data['products'][] = array( 'id' => $product->get_id(), 'name' => $product->get_name(), 'sku' => $product->get_sku(), 'price' => $product->get_price(), 'stock' => $product->get_stock_quantity(), 'manage_stock' => $product->get_manage_stock(), 'backorders' => $product->get_backorders(), 'low_stock' => $product->get_low_stock_amount(), 'url' => $product->get_permalink() );
}
echo 'ANOTTER_CHECKOUT_DATA=' . wp_json_encode( $data ) . "\n";
