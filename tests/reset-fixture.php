<?php
/** Destructive reset verification is confined to the separate named QA project. */
require_once __DIR__ . '/fixture-guard.php';
if ( 'anotter-m05-qa' !== getenv( 'ANOTTER_DEMO_PROJECT' ) || getenv( 'ANOTTER_PUBLIC_URL' ) ) {
	WP_CLI::error( 'Reset integration test requires the isolated anotter-m05-qa project without a tunnel.' );
}
$action = $args[0] ?? 'inspect';
$fixture = get_option( 'anotter_demo_m05_reset_fixture', array() );
if ( 'create' === $action ) {
	if ( $fixture || wc_get_orders( array( 'limit' => 1, 'status' => array_keys( wc_get_order_statuses() ) ) ) ) {
		WP_CLI::error( 'Requires an idle QA dataset; inspect and clean existing owned fixtures first.' );
	}
	$product = new WC_Product_Simple();
	$product->set_name( 'DEMO — M05 unrelated preservation fixture' );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_regular_price( '77' );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( 7 );
	$product->update_meta_data( '_anotter_m05_reset_test', 'yes' );
	$fixture['unrelated'] = $product->save();
	update_option( 'anotter_demo_m05_reset_fixture', $fixture, false );
	$main = wc_get_product( wc_get_product_id_by_sku( 'DEMO-CARDS-001' ) );
	$main->set_regular_price( '891' );
	$main->save();
	foreach ( array( 'eligible' => 'demo-m05@example.invalid', 'foreign' => 'unselected@example.invalid' ) as $name => $email ) {
		$order = wc_create_order();
		$order->set_billing_email( $email );
		$order->set_payment_method( 'bacs' );
		$order->add_product( $main, 1 );
		$order->update_meta_data( '_anotter_m05_reset_test', 'yes' );
		$order->calculate_totals();
		$fixture[ $name ] = $order->save();
		update_option( 'anotter_demo_m05_reset_fixture', $fixture, false );
		$order->update_status( 'on-hold' );
	}
}
if ( 'cancel-foreign' === $action ) {
	$order = wc_get_order( $fixture['foreign'] ?? 0 );
	if ( ! $order || 'yes' !== $order->get_meta( '_anotter_m05_reset_test' ) ) {
		WP_CLI::error( 'Foreign fixture ownership failed.' );
	}
	$order->update_status( 'cancelled' );
}
if ( 'cleanup' === $action ) {
	foreach ( array( 'eligible', 'foreign' ) as $name ) {
		$order = wc_get_order( $fixture[ $name ] ?? 0 );
		if ( $order ) {
			if ( 'yes' !== $order->get_meta( '_anotter_m05_reset_test' ) ) {
				WP_CLI::error( 'Order cleanup ownership failed.' );
			}
			$order->update_status( 'cancelled' );
			$order->delete( true );
		}
	}
	$product = wc_get_product( $fixture['unrelated'] ?? 0 );
	if ( $product && 'yes' === $product->get_meta( '_anotter_m05_reset_test' ) ) {
		$product->delete( true );
	}
	delete_option( 'anotter_demo_m05_reset_fixture' );
	return;
}
$unrelated = wc_get_product( $fixture['unrelated'] ?? 0 );
$main = wc_get_product( wc_get_product_id_by_sku( 'DEMO-CARDS-001' ) );
echo 'ANOTTER_RESET_TEST=' . wp_json_encode( array(
	'eligible' => $fixture['eligible'] ?? null, 'foreign_exists' => (bool) wc_get_order( $fixture['foreign'] ?? 0 ),
	'eligible_exists' => (bool) wc_get_order( $fixture['eligible'] ?? 0 ), 'main_stock' => $main->get_stock_quantity(), 'main_price' => $main->get_price(),
	'unrelated_stock' => $unrelated ? $unrelated->get_stock_quantity() : null, 'unrelated_price' => $unrelated ? $unrelated->get_price() : null,
	'theme' => get_stylesheet(), 'admin_count' => count( get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) ),
	'plugins' => get_option( 'active_plugins' ), 'gateways' => array( get_option( 'woocommerce_bacs_settings' ), get_option( 'woocommerce_woo-mercado-pago-basic_settings' ) ),
) ) . "\n";
