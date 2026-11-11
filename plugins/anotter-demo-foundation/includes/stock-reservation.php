<?php
/** Keep native reservations visible until native stock reduction/release finishes. */
defined( 'ABSPATH' ) || exit;

// WooCommerce 11.2.1 saves the new order status before its stock callbacks run.
// A second checkout could otherwise ignore the first reservation in that window,
// while physical stock still has its old value. Count the existing reservation
// through reducing statuses; WooCommerce itself reduces stock and releases it.
// This changes only the supported reservation-read filter, never stock/order writes.
add_filter( 'woocommerce_query_for_reserved_stock', function ( $query ) {
	return str_replace(
		"IN ( 'wc-checkout-draft', 'wc-pending' )",
		"IN ( 'wc-checkout-draft', 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed' )",
		$query
	);
} );
