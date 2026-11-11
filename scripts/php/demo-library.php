<?php
/** CLI-only M05 rehearsal ownership and reset policy. All writes use native APIs. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

/** Fail closed even if a caller bypasses the Python helper. */
function anotter_demo_require_local_tools( $allow_public = false ) {
	// get_option('home') is filtered by WP_HOME for an intentional public CLI request.
	// Native raw autoloaded options preserve the DB's canonical origin without overriding filters.
	$canonical = wp_load_alloptions()['home'] ?? '';
	if ( ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true )
		|| 'm01' !== get_option( 'anotter_demo_environment' )
		|| ! in_array( wp_parse_url( $canonical, PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true )
		|| ( ! $allow_public && getenv( 'ANOTTER_PUBLIC_URL' ) ) ) {
		throw new RuntimeException( 'Refused: requires managed local/development demo, loopback canonical URL and no active public origin.' );
	}
}

function anotter_demo_session( $session ) {
	if ( ! is_string( $session ) || ! preg_match( '/^[a-z0-9][a-z0-9-]{3,39}$/D', $session ) ) {
		throw new RuntimeException( 'Session must be 4–40 lowercase letters, digits or hyphens.' );
	}
	return $session;
}

function anotter_demo_reset_products() {
	$products = array();
	foreach ( require __DIR__ . '/products.php' as $fixture ) {
		$product = wc_get_product( wc_get_product_id_by_sku( $fixture['sku'] ) );
		if ( ! $product || 'yes' !== $product->get_meta( '_anotter_demo' ) || ! $product->is_type( 'simple' ) ) {
			throw new RuntimeException( 'Missing or unmanaged demo SKU: ' . $fixture['sku'] . '. Run setup and inspect ownership.' );
		}
		$products[ $product->get_id() ] = array( 'object' => $product, 'fixture' => $fixture );
	}
	return $products;
}

/** An order's broad DEMO marker alone NEVER authorizes reset deletion. */
function anotter_demo_reset_order_owned( $order, $session, $ids ) {
	if ( ! $order instanceof WC_Order || $order instanceof WC_Order_Refund
		|| 'yes' !== $order->get_meta( '_anotter_demo' )
		|| $session !== $order->get_meta( '_anotter_demo_session_v5' )
		|| 'demo-m05@example.invalid' !== $order->get_billing_email()
		|| $order->is_paid() || $order->get_date_paid() || $order->get_transaction_id()
		|| 'bacs' !== $order->get_payment_method() || ! $order->get_items() || $order->get_refunds() ) {
		return false;
	}
	foreach ( $order->get_items() as $item ) {
		if ( ! in_array( $item->get_product_id(), $ids, true ) ) {
			return false;
		}
	}
	return true;
}

/** Validate the entire plan BEFORE any mutation. Unknown active orders block stock reset. */
function anotter_demo_reset_plan( $session, $delete_orders ) {
	anotter_demo_require_local_tools();
	anotter_demo_session( $session );
	$products = anotter_demo_reset_products();
	$ids = array_keys( $products );
	$orders = array();
	$page = 1;
	do {
		$batch = wc_get_orders( array( 'type' => 'shop_order', 'limit' => 100, 'page' => $page++, 'status' => array_merge( array_keys( wc_get_order_statuses() ), array( 'wc-checkout-draft' ) ), 'orderby' => 'ID', 'order' => 'ASC' ) );
		foreach ( $batch as $order ) {
			$uses_demo = false;
			foreach ( $order->get_items() as $item ) {
				$uses_demo = $uses_demo || in_array( $item->get_product_id(), $ids, true );
			}
			$selected = $session === $order->get_meta( '_anotter_demo_session_v5' );
			if ( $selected && ! anotter_demo_reset_order_owned( $order, $session, $ids ) ) {
				throw new RuntimeException( 'Session contains an ineligible order; inspect privately. Provider/paid orders are never reset.' );
			}
			if ( $uses_demo && ! $order->has_status( array( 'cancelled', 'refunded', 'failed' ) ) && ! ( $selected && $delete_orders ) ) {
				throw new RuntimeException( 'Active order references demo inventory. Cancel it natively or explicitly tag/select an eligible offline rehearsal order.' );
			}
			if ( $selected && $delete_orders ) {
				$orders[] = $order;
			}
		}
	} while ( count( $batch ) === 100 );
	$config = require WP_PLUGIN_DIR . '/anotter-demo-foundation/includes/demo-config.php';
	$map = get_option( 'anotter_demo_shipping_v3', array() );
	$shipping = array();
	foreach ( $config['shipping'] as $key => $expected ) {
		$zone = new WC_Shipping_Zone( $map[ $key ]['id'] ?? 0 );
		$locations = array_map( function ( $location ) { return $location->type . ':' . $location->code; }, $zone->get_zone_locations() );
		if ( ! $zone->get_id() || $zone->get_zone_name() !== $expected['name'] || array( $expected['type'] . ':' . $expected['location'] ) !== $locations ) {
			throw new RuntimeException( 'Shipping ownership/location changed; inspect before reset.' );
		}
		$methods = $zone->get_shipping_methods();
		foreach ( array( 'dac', 'ues' ) as $carrier ) {
			$id = $map[ $key ]['methods'][ $carrier ] ?? 0;
			if ( ! $id || ! isset( $methods[ $id ] ) || 'flat_rate' !== $methods[ $id ]->id || 'yes' !== $methods[ $id ]->enabled || ! str_contains( $methods[ $id ]->get_title(), strtoupper( $carrier ) . ' — DEMO' ) ) {
				throw new RuntimeException( 'Shipping method ownership changed; inspect before reset.' );
			}
			$shipping[ $id ] = array( 'cost' => $expected[ $carrier ], 'zone' => $zone, 'order' => $expected['order'] );
		}
	}
	return array( 'products' => $products, 'orders' => $orders, 'shipping' => $shipping, 'config' => $config );
}

function anotter_demo_apply_reset( $plan ) {
	foreach ( $plan['orders'] as $order ) {
		// Native cancellation releases reservations/restores reduced stock BEFORE deterministic reset.
		if ( ! $order->has_status( array( 'cancelled', 'refunded' ) ) ) {
			$order->update_status( 'cancelled', 'DEMO M05 — explicit rehearsal reset, native stock restoration.' );
		}
		foreach ( $order->get_refunds() as $refund ) {
			$refund->delete( true );
		}
		$order->delete( true );
	}
	foreach ( $plan['products'] as $entry ) {
		$product = $entry['object'];
		$product->set_regular_price( $entry['fixture']['price'] );
		$product->set_sale_price( '' );
		$product->set_date_on_sale_from( null );
		$product->set_date_on_sale_to( null );
		$product->set_price( $entry['fixture']['price'] );
		$product->set_manage_stock( true );
		$product->set_backorders( 'no' );
		$product->set_stock_quantity( $entry['fixture']['stock'] );
		$product->set_stock_status( 'instock' );
		$product->save();
	}
	foreach ( $plan['shipping'] as $id => $entry ) {
		$key = 'woocommerce_flat_rate_' . $id . '_settings';
		$settings = get_option( $key, array() );
		$settings['cost'] = $entry['cost'];
		$settings['tax_status'] = 'none';
		update_option( $key, $settings );
		$entry['zone']->set_zone_order( $entry['order'] );
		$entry['zone']->save();
	}
	WC_Cache_Helper::get_transient_version( 'shipping', true );
	foreach ( array(
		'woocommerce_currency' => 'UYU', 'woocommerce_manage_stock' => 'yes', 'woocommerce_enable_guest_checkout' => 'yes',
		'woocommerce_calc_taxes' => 'no', 'woocommerce_allowed_countries' => 'specific', 'woocommerce_specific_allowed_countries' => array( 'UY' ),
		'woocommerce_ship_to_countries' => 'specific', 'woocommerce_specific_ship_to_countries' => array( 'UY' ),
		'woocommerce_hold_stock_minutes' => (string) $plan['config']['hold_minutes'], 'woocommerce_checkout_phone_field' => 'required',
	) as $key => $value ) {
		update_option( $key, $value );
	}
	// Gateway credentials, identity and enabled state are deliberately outside reset ownership.
}
