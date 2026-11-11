<?php
/** Read-only post-suite accounting; never removes unidentified data. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
$ledgers = array_filter( array(
	get_option( 'anotter_demo_e2e_m03' ), get_option( 'anotter_demo_e2e_m04' ), get_option( 'anotter_demo_m05_reset_fixture' ),
) );
$owned = 0;
$orphaned = 0;
$page = 1;
do {
	$orders = wc_get_orders( array( 'type' => 'shop_order', 'limit' => 100, 'page' => $page++, 'status' => array_merge( array_keys( wc_get_order_statuses() ), array( 'wc-checkout-draft' ) ) ) );
	foreach ( $orders as $order ) {
		if ( 'yes' === $order->get_meta( '_anotter_e2e_m03' ) || 'yes' === $order->get_meta( '_anotter_e2e_m04' ) || 'yes' === $order->get_meta( '_anotter_m05_reset_test' ) || preg_match( '/^m03-[a-z0-9]+@example\.invalid$/D', $order->get_billing_email() ) ) {
			++$owned;
		}
		foreach ( $order->get_items() as $item ) {
			if ( str_contains( $item->get_name(), 'prueba M03' ) && ! $item->get_product() ) {
				++$orphaned;
			}
		}
	}
} while ( count( $orders ) === 100 );
if ( $ledgers || $owned || $orphaned || wc_get_product_id_by_sku( 'E2E-M02-STOCK-ZERO' ) ) {
	WP_CLI::error( 'Post-suite owned ledgers/orders/products or unidentified orphan references require private inspection; nothing deleted.' );
}
WP_CLI::success( 'No owned fixture ledger/order, M02 stock product or orphan M03 item references remain.' );
