<?php
/** Isolated, marked test fixtures and native lifecycle assertions. No merchant stock writes. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
require_once __DIR__ . '/fixture-guard.php';
$action = $args[0] ?? 'data';
$fixture = get_option( 'anotter_demo_e2e_m03', array() );
$check = function ( $condition, $message ) {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
};
if ( 'create' === $action ) {
	$check( ! $fixture, 'Existing M03 fixtures need explicit cleanup before another run.' );
	$fixture = array( 'products' => array(), 'coupons' => array(), 'email' => 'm03-' . strtolower( wp_generate_password( 12, false ) ) . '@example.invalid' );
	update_option( 'anotter_demo_e2e_m03', $fixture, false );
	foreach ( array( 'main' => 6, 'second' => 6, 'last' => 1, 'zero' => 0, 'single' => 2 ) as $name => $stock ) {
		$original = wc_get_product( wc_get_product_id_by_sku( 'second' === $name ? 'DEMO-EXP-001' : 'DEMO-CARDS-001' ) );
		$product = new WC_Product_Simple();
		$product->set_name( $original->get_name() . ' · prueba M03 ' . $name );
		$product->set_sku( 'E2E-M03-' . strtoupper( $name ) );
		$product->set_regular_price( $original->get_price() );
		$product->set_image_id( $original->get_image_id() );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_status( 'publish' );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $stock );
		$product->set_low_stock_amount( 2 );
		$product->set_backorders( 'no' );
		$product->set_sold_individually( 'single' === $name );
		$product->add_meta_data( '_anotter_e2e_m03', 'yes' );
		$product->add_meta_data( '_anotter_demo', 'yes' );
		$fixture['products'][ $name ] = $product->save();
		update_option( 'anotter_demo_e2e_m03', $fixture, false );
	}
	$parent = new WC_Product_Variable();
	$parent->set_name( 'DEMO — Variación de prueba M03' );
	$parent->set_catalog_visibility( 'hidden' );
	$parent->set_status( 'publish' );
	$attribute = new WC_Product_Attribute();
	$attribute->set_name( 'Modelo' );
	$attribute->set_options( array( 'Prueba' ) );
	$attribute->set_variation( true );
	$parent->set_attributes( array( $attribute ) );
	$parent->add_meta_data( '_anotter_e2e_m03', 'yes' );
	$fixture['products']['variable'] = $parent->save();
	$variation = new WC_Product_Variation();
	$variation->set_parent_id( $parent->get_id() );
	$variation->set_attributes( array( 'modelo' => 'Prueba' ) );
	$variation->set_regular_price( '100' );
	$variation->set_manage_stock( true );
	$variation->set_stock_quantity( 3 );
	$variation->set_backorders( 'no' );
	$variation->set_status( 'publish' );
	$variation->add_meta_data( '_anotter_e2e_m03', 'yes' );
	$fixture['products']['variation'] = $variation->save();
	WC_Product_Variable::sync( $parent->get_id() );
	update_option( 'anotter_demo_e2e_m03', $fixture, false );
	foreach ( array( 'valid', 'expired', 'used' ) as $name ) {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'm03-' . $name . '-' . strtolower( wp_generate_password( 6, false ) ) );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->set_product_ids( array_values( $fixture['products'] ) );
		$coupon->set_usage_limit( 'used' === $name ? 1 : 10 );
		$coupon->set_usage_count( 'used' === $name ? 1 : 0 );
		$coupon->set_date_expires( time() + ( 'expired' === $name ? -DAY_IN_SECONDS : DAY_IN_SECONDS ) );
		$coupon->add_meta_data( '_anotter_e2e_m03', 'yes' );
		$fixture['coupons'][ $name ] = $coupon->save();
		update_option( 'anotter_demo_e2e_m03', $fixture, false );
	}
}
$orders = $fixture ? wc_get_orders( array( 'type' => 'shop_order', 'limit' => -1, 'status' => array_merge( array_keys( wc_get_order_statuses() ), array( 'wc-checkout-draft' ) ) ) ) : array();
$orders = array_values( array_filter( $orders, function ( $order ) use ( $fixture ) {
	if ( 'yes' !== $order->get_meta( '_anotter_e2e_m03' ) && $order->get_billing_email() !== ( $fixture['email'] ?? '' ) ) {
		return false;
	}
	$items = $order->get_items();
	if ( ! $items ) {
		return false;
	}
	foreach ( $items as $item ) {
		if ( ! in_array( $item->get_product_id(), array_values( $fixture['products'] ), true ) ) {
			return false;
		}
	}
	return true;
} ) );
if ( 'lifecycle' === $action ) {
	$check( ! empty( $fixture['products']['main'] ), 'Fixtures are required.' );
	$product_id = $fixture['products']['main'];
	$start = wc_get_product( $product_id )->get_stock_quantity();
	$order = wc_create_order();
	$order->update_meta_data( '_anotter_e2e_m03', 'yes' );
	$order->set_billing_email( $fixture['email'] );
	$order->add_product( wc_get_product( $product_id ), 1 );
	$order->calculate_totals();
	$order->save();
	$stock = function () use ( $product_id ) { return wc_get_product( $product_id )->get_stock_quantity(); };
	$check( $stock() === $start, 'Pending order should not directly reduce physical stock.' );
	wc_reserve_stock_for_order( $order );
	$observe_transition = function ( $id ) use ( $order, $product_id, $check ) {
		if ( $id === $order->get_id() ) {
			$check( wc_get_held_stock_quantity( wc_get_product( $product_id ) ) >= 1, 'Reservation must remain visible after on-hold is saved and before native reduction runs.' );
		}
	};
	add_action( 'woocommerce_order_status_on-hold', $observe_transition, 1 );
	$order->update_status( 'on-hold' );
	remove_action( 'woocommerce_order_status_on-hold', $observe_transition, 1 );
	$check( $stock() === $start - 1, 'On-hold must reduce once.' );
	$order->update_status( 'processing' );
	$order->update_status( 'completed' );
	$check( $stock() === $start - 1, 'Processing/completed must not reduce again.' );
	$order->update_status( 'cancelled' );
	$check( $stock() === $start, 'Cancellation must restore reduced stock.' );
	$order->update_status( 'pending' );
	$order->update_status( 'on-hold' );
	$order->update_status( 'failed' );
	$check( $stock() === $start, 'Installed WooCommerce restores reduced stock on failed status.' );
	$order->update_status( 'on-hold' );
	$refund = wc_create_refund( array( 'order_id' => $order->get_id(), 'amount' => $order->get_total(), 'reason' => 'DEMO native stock test', 'refund_payment' => false, 'restock_items' => true, 'line_items' => array( array_key_first( $order->get_items() ) => array( 'qty' => 1, 'refund_total' => $order->get_total(), 'refund_tax' => array() ) ) ) );
	$check( ! is_wp_error( $refund ) && $stock() === $start, 'Native refund with explicit restock must restore once.' );
	$pending = wc_create_order( array( 'created_via' => 'checkout' ) );
	$pending->set_billing_email( $fixture['email'] );
	$pending->update_meta_data( '_anotter_e2e_m03', 'yes' );
	$pending->add_product( wc_get_product( $product_id ), 1 );
	$pending->set_date_created( time() - 2 * HOUR_IN_SECONDS );
	$pending->set_date_modified( time() - 2 * HOUR_IN_SECONDS );
	$pending->save();
	wc_reserve_stock_for_order( $pending );
	$check( wc_get_held_stock_quantity( wc_get_product( $product_id ) ) >= 1, 'Pending order must reserve stock natively.' );
	$only_fixture = function ( $cancel, $candidate ) use ( $pending ) { return $candidate->get_id() === $pending->get_id() && $cancel; };
	add_filter( 'woocommerce_cancel_unpaid_order', $only_fixture, 10, 2 );
	wc_cancel_unpaid_orders();
	remove_filter( 'woocommerce_cancel_unpaid_order', $only_fixture, 10 );
	$check( wc_get_order( $pending->get_id() )->has_status( 'cancelled' ), 'Native expiration must cancel the aged pending fixture.' );
	$check( $stock() === $start, 'Expiration of an unreduced pending order must not increase physical stock.' );
	$check( 0 === wc_get_held_stock_quantity( wc_get_product( $product_id ) ), 'Expiration must release native reservation.' );
	WP_CLI::success( 'Aged pending fixture expired through native cancellation; reservation released without physical stock inflation.' );
	WP_CLI::success( 'Native pending/on-hold/processing/completed/cancelled/failed/refund stock lifecycle passed.' );
}
if ( 'cancel' === $action || 'cleanup' === $action ) {
	// Preflight every object before the first cancellation or deletion.
	foreach ( $fixture['products'] ?? array() as $id ) {
		$product = wc_get_product( $id );
		$check( ! $product || 'yes' === $product->get_meta( '_anotter_e2e_m03' ), 'Product ownership check failed.' );
	}
	foreach ( $fixture['coupons'] ?? array() as $id ) {
		$coupon = new WC_Coupon( $id );
		$check( ! $coupon->get_id() || 'yes' === $coupon->get_meta( '_anotter_e2e_m03' ), 'Coupon ownership check failed.' );
	}
	foreach ( $orders as $order ) {
		$owned = 'yes' === $order->get_meta( '_anotter_e2e_m03' ) || $order->get_billing_email() === ( $fixture['email'] ?? '' );
		foreach ( $order->get_items() as $item ) {
			$owned = $owned && in_array( $item->get_product_id(), array_values( $fixture['products'] ), true );
		}
		$check( $owned, 'Refusing to touch an order containing a non-test product.' );
		if ( ! $order->has_status( array( 'cancelled', 'refunded' ) ) ) {
			$order->update_status( 'cancelled', 'DEMO — limpieza de prueba M03, reposición nativa.' );
		}
	}
}
if ( 'cleanup' === $action ) {
	foreach ( $orders as $order ) {
		foreach ( $order->get_refunds() as $refund ) {
			$refund->delete( true );
		}
		$order->delete( true );
	}
	foreach ( $fixture['products'] ?? array() as $id ) {
		$product = wc_get_product( $id );
		if ( $product && 'yes' === $product->get_meta( '_anotter_e2e_m03' ) ) {
			$product->delete( true );
		}
	}
	foreach ( $fixture['coupons'] ?? array() as $id ) {
		$coupon = new WC_Coupon( $id );
		if ( 'yes' === $coupon->get_meta( '_anotter_e2e_m03' ) ) {
			$coupon->delete( true );
		}
	}
	delete_option( 'anotter_demo_e2e_m03' );
	WP_CLI::success( 'Only owned M03 test orders, coupons and products cleaned up.' );
	return;
}
$data = array( 'email' => $fixture['email'] ?? '', 'products' => array(), 'coupons' => array(), 'orders' => array() );
foreach ( $fixture['products'] ?? array() as $name => $id ) {
	$product = wc_get_product( $id );
	$data['products'][ $name ] = array( 'id' => $id, 'name' => $product->get_name(), 'stock' => $product->get_stock_quantity(), 'price' => $product->get_price(), 'url' => $product->get_permalink() );
}
foreach ( $fixture['coupons'] ?? array() as $name => $id ) {
	$data['coupons'][ $name ] = ( new WC_Coupon( $id ) )->get_code();
}
foreach ( $orders as $order ) {
	$data['orders'][] = array( 'id' => $order->get_id(), 'status' => $order->get_status(), 'paid' => $order->is_paid(), 'payment' => $order->get_payment_method(), 'total' => $order->get_total(), 'shipping' => $order->get_shipping_total(), 'shipping_method' => $order->get_shipping_method(), 'customer_id' => $order->get_customer_id(), 'notes' => count( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) ), 'admin' => $order->get_edit_order_url() );
}
echo 'ANOTTER_M03_FIXTURES=' . wp_json_encode( $data ) . "\n";
