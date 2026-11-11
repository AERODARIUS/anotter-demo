<?php
/** Only relative routes leave the container; host performs real HTTP requests. */
$fixtures = require '/opt/anotter/scripts/php/products.php';
$product  = wc_get_product_id_by_sku( $fixtures[0]['sku'] );
$urls = array(
	'home'      => home_url( '/' ),
	'shop'      => wc_get_page_permalink( 'shop' ),
	'product'   => get_permalink( $product ),
	'add-to-cart' => add_query_arg( 'add-to-cart', $product, wc_get_cart_url() ),
	'cart'      => wc_get_cart_url(),
	'checkout'  => wc_get_checkout_url(),
	'account'   => wc_get_page_permalink( 'myaccount' ),
	'store-api' => rest_url( 'wc/store/v1/products' ),
);
$routes = array();
foreach ( $urls as $key => $url ) {
	$path  = wp_parse_url( $url, PHP_URL_PATH ) ?: '/';
	$query = wp_parse_url( $url, PHP_URL_QUERY );
	$routes[ $key ] = $path . ( $query ? '?' . $query : '' );
}
echo 'ANOTTER_ROUTES=' . wp_json_encode( $routes ) . "\n";
