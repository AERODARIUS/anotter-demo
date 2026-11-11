<?php
/** Read live test expectations from WordPress/WooCommerce, never a second catalog. */
$product = wc_get_product( wc_get_product_id_by_sku( 'DEMO-CARDS-001' ) );
if ( ! $product ) {
	WP_CLI::error( 'Primary demo product is missing.' );
}
$pages = array( 'home' => home_url( '/' ), 'shop' => wc_get_page_permalink( 'shop' ), 'product' => $product->get_permalink(), 'cart' => wc_get_cart_url(), 'checkout' => wc_get_checkout_url() );
foreach ( array( 'how', 'faq', 'contact', 'privacy', 'terms' ) as $key ) {
	$pages[ $key ] = anotter_demo_url( $key );
}
echo 'ANOTTER_E2E=' . wp_json_encode( array(
	'pages' => $pages,
	'product' => array( 'id' => $product->get_id(), 'name' => $product->get_name(), 'price' => wc_get_price_to_display( $product ), 'stock' => $product->get_stock_quantity() ),
	'currency' => get_woocommerce_currency(),
) ) . "\n";
