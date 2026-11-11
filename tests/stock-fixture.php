<?php
/** Isolated test-owned product, never change the inventory of existing demo products. */
require_once __DIR__ . '/fixture-guard.php';
$sku = 'E2E-M02-STOCK-ZERO';
$id = wc_get_product_id_by_sku( $sku );
$product = $id ? wc_get_product( $id ) : null;
if ( $product && 'yes' !== $product->get_meta( '_anotter_e2e_fixture' ) ) {
	WP_CLI::error( 'Refusing to touch a product not owned by this test.' );
}
if ( isset( $args[0] ) && 'cleanup' === $args[0] ) {
	if ( $product ) {
		$product->delete( true );
	}
	WP_CLI::success( 'Removed only the test-owned out-of-stock fixture.' );
	return;
}
if ( ! $product ) {
	$product = new WC_Product_Simple();
	$product->set_name( 'DEMO — Prueba de producto agotado' );
	$product->set_sku( $sku );
	$product->set_status( 'publish' );
	$product->set_regular_price( '100' );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( 0 );
	$product->set_stock_status( 'outofstock' );
	$product->set_backorders( 'no' );
	$product->update_meta_data( '_anotter_demo', 'yes' );
	$product->update_meta_data( '_anotter_e2e_fixture', 'yes' );
	$product->save();
}
echo 'ANOTTER_STOCK=' . wp_json_encode( array( 'id' => $product->get_id(), 'url' => $product->get_permalink() ) ) . "\n";
