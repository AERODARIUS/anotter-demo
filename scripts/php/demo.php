<?php
/** M05 CLI interface; default is a read-only plan. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
require_once __DIR__ . '/demo-library.php';
try {
	$action = $args[0] ?? 'reset';
	$session = anotter_demo_session( $args[1] ?? 'rehearsal' );
	$apply = 'apply' === ( $args[2] ?? '' );
	$confirmation = $args[3] ?? '';
	$delete_orders = 'delete-orders' === ( $args[4] ?? '' );
	anotter_demo_require_local_tools();
	if ( $apply && ( ! getenv( 'ANOTTER_DEMO_PROJECT' ) || $confirmation !== getenv( 'ANOTTER_DEMO_PROJECT' ) ) ) {
		throw new RuntimeException( 'Applying requires exact Compose project confirmation.' );
	}
	if ( 'tag' === $action ) {
		$order = wc_get_order( absint( $args[5] ?? 0 ) );
		if ( ! $order || $order->get_meta( '_anotter_demo_session_v5' ) ) {
			throw new RuntimeException( 'Order absent or already enrolled. Inspect privately.' );
		}
		// Validate against the same strict ownership policy before persisting enrollment.
		$order->update_meta_data( '_anotter_demo_session_v5', $session );
		if ( ! anotter_demo_reset_order_owned( $order, $session, array_keys( anotter_demo_reset_products() ) ) ) {
			throw new RuntimeException( 'Only unpaid BACS rehearsal orders with the reserved demo identity and known products can be tagged.' );
		}
		if ( $apply ) {
			$order->save();
		}
		WP_CLI::success( $apply ? 'Rehearsal order enrolled.' : 'DRY RUN: eligible rehearsal order; no write.' );
	} elseif ( 'reset' === $action ) {
		$plan = anotter_demo_reset_plan( $session, $delete_orders );
		WP_CLI::log( sprintf( '%s: %d known demo products, %d explicitly owned offline orders, %d sample shipping methods. UYU/local settings restored; payment configuration preserved.', $apply ? 'APPLY' : 'DRY RUN', count( $plan['products'] ), count( $plan['orders'] ), count( $plan['shipping'] ) ) );
		if ( $apply ) {
			anotter_demo_apply_reset( $plan );
		}
		WP_CLI::success( $apply ? 'Demo reset complete. Start a fresh buyer browser context.' : 'No data changed. Apply requires exact-project confirmation.' );
	} else {
		throw new RuntimeException( 'Unknown demo action.' );
	}
} catch ( Throwable $error ) {
	WP_CLI::error( $error->getMessage() );
}
