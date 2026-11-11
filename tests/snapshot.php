<?php
/** Stable state for setup-repeat and persistence comparisons; excludes transients. */
global $wpdb;
$state = array();
// Core-owned tables are read only; no custom table or direct mutation.
$state['users'] = $wpdb->get_results( "SELECT ID, user_login, user_email FROM {$wpdb->users} ORDER BY ID", ARRAY_A );
$state['posts'] = $wpdb->get_results(
	"SELECT ID, post_type, post_status, post_name, post_title, post_content FROM {$wpdb->posts} WHERE post_type IN ('page','product','attachment') ORDER BY ID",
	ARRAY_A
);
$state['products'] = array();
foreach ( wc_get_products( array( 'limit' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $product ) {
	$state['products'][] = array(
		'id' => $product->get_id(), 'sku' => $product->get_sku(),
		'price' => $product->get_regular_price(), 'stock' => $product->get_stock_quantity(),
		'backorders' => $product->get_backorders(), 'low_stock' => $product->get_low_stock_amount(), 'manage_stock' => $product->get_manage_stock(), 'image_id' => $product->get_image_id(),
		'gallery_ids' => $product->get_gallery_image_ids(),
		'category_ids' => $product->get_category_ids(), 'tag_ids' => $product->get_tag_ids(),
		'attributes' => array_map( function ( $attribute ) { return $attribute->get_data(); }, $product->get_attributes() ),
	);
}
$state['images'] = array();
foreach ( get_posts( array( 'post_type' => 'attachment', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $attachment ) {
	$file = get_attached_file( $attachment->ID );
	$state['images'][ $attachment->ID ] = is_file( $file ) ? hash_file( 'sha256', $file ) : 'MISSING';
}
$state['zones'] = WC_Shipping_Zones::get_zones();
// Serialize public zone/method configuration, avoiding internal object state.
foreach ( $state['zones'] as &$zone ) {
	$zone['shipping_methods'] = array_map( function ( $method ) {
		return array( 'id' => $method->id, 'instance' => $method->instance_id, 'enabled' => $method->enabled, 'settings' => $method->instance_settings );
	}, $zone['shipping_methods'] );
}
unset( $zone );
$state['options'] = array();
foreach ( array( 'blogname', 'timezone_string', 'WPLANG', 'blog_public', 'home', 'siteurl', 'permalink_structure', 'show_on_front', 'page_on_front', 'woocommerce_currency', 'woocommerce_default_country', 'woocommerce_manage_stock', 'woocommerce_enable_guest_checkout', 'woocommerce_cod_settings', 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_shop_page_id', 'woocommerce_myaccount_page_id', 'anotter_demo_pages_v2', 'anotter_demo_woocommerce_locale', 'anotter_demo_commerce_v3', 'anotter_demo_checkout_pages_v3', 'anotter_demo_shipping_v3', 'woocommerce_coming_soon', 'woocommerce_bacs_settings', 'woocommerce_bacs_accounts', 'woocommerce_hold_stock_minutes', 'woocommerce_notify_low_stock_amount', 'woocommerce_notify_no_stock_amount', 'woocommerce_checkout_phone_field', 'woocommerce_calc_taxes', 'woocommerce_enable_coupons', 'woocommerce_terms_page_id', 'wp_page_for_privacy_policy' ) as $key ) {
	$state['options'][ $key ] = get_option( $key );
}
// Only non-secret gateway configuration participates in repeat/persistence checks.
$state['options']['woocommerce_woo-mercado-pago-basic_settings'] = array_intersect_key(
	get_option( 'woocommerce_woo-mercado-pago-basic_settings', array() ),
	array_flip( array( 'enabled', 'title', 'method', 'currency_conversion', 'auto_return', 'success_url', 'failure_url', 'pending_url', 'gateway_discount', 'commission', 'binary_mode' ) )
);
$state['options']['anotter_demo_payments_v4'] = get_option( 'anotter_demo_payments_v4' );
echo 'ANOTTER_SNAPSHOT=' . wp_json_encode( $state ) . "\n";
