<?php
/** Owned unpaid fixture for negative HTTP tests; never calls payment_complete. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
require_once __DIR__ . '/fixture-guard.php';
$action = $args[0] ?? 'inspect';
$fixture = get_option( 'anotter_demo_e2e_m04', array() );
if ( 'create' === $action ) {
	if ( $fixture ) {
		WP_CLI::error( 'Existing M04 fixture requires explicit cleanup.' );
	}
	$product = new WC_Product_Simple();
	$product->set_name( 'DEMO — M04 negative notification test' );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_regular_price( '100' );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( 3 );
	$product->add_meta_data( '_anotter_e2e_m04', 'yes' );
	$fixture['product'] = $product->save();
	update_option( 'anotter_demo_e2e_m04', $fixture, false );
	$order = wc_create_order( array( 'status' => 'pending', 'created_via' => 'anotter-m04-negative-test' ) );
	$order->update_meta_data( '_anotter_e2e_m04', 'yes' );
	$order->set_payment_method( 'woo-mercado-pago-basic' );
	$order->set_billing_email( 'negative-m04@example.invalid' );
	$order->add_product( $product, 1 );
	$order->calculate_totals();
	$fixture['order'] = $order->save();
	update_option( 'anotter_demo_e2e_m04', $fixture, false );
}
$product = isset( $fixture['product'] ) ? wc_get_product( $fixture['product'] ) : false;
$order = isset( $fixture['order'] ) ? wc_get_order( $fixture['order'] ) : false;
if ( 'cleanup' === $action ) {
	if ( ( $order && 'yes' !== $order->get_meta( '_anotter_e2e_m04' ) ) || ( $product && 'yes' !== $product->get_meta( '_anotter_e2e_m04' ) ) ) {
		WP_CLI::error( 'Ownership preflight failed; no objects changed.' );
	}
	if ( $order ) {
		if ( 'yes' !== $order->get_meta( '_anotter_e2e_m04' ) ) {
			WP_CLI::error( 'Order ownership check failed.' );
		}
		$order->update_status( 'cancelled' );
		$order->delete( true );
	}
	if ( $product ) {
		if ( 'yes' !== $product->get_meta( '_anotter_e2e_m04' ) ) {
			WP_CLI::error( 'Product ownership check failed.' );
		}
		$product->delete( true );
	}
	delete_option( 'anotter_demo_e2e_m04' );
	WP_CLI::success( 'Owned M04 unpaid fixtures cleaned through native CRUD.' );
	return;
}
if ( ! $order || ! $product ) {
	WP_CLI::error( 'No M04 unpaid fixture.' );
}
echo 'ANOTTER_M04_FIXTURE=' . wp_json_encode( array(
	'status' => $order->get_status(), 'paid' => $order->is_paid(),
	'stock' => $product->get_stock_quantity(), 'stock_reduced' => $order->get_data_store()->get_stock_reduced( $order->get_id() ),
	'total' => $order->get_total(), 'transaction' => $order->get_transaction_id(),
	// This output is captured privately by the test runner, never logged in reports.
	'return_url' => $order->get_checkout_order_received_url(),
	'cancel_url' => html_entity_decode( $order->get_cancel_order_url() ),
	'product' => $product->get_id(),
) ) . "\n";
