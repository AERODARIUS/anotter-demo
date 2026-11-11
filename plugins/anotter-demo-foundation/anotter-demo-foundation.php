<?php
/**
 * Plugin Name: AnOtter Demo Foundation
 * Description: Labels demo orders and suppresses outgoing email in this independent prototype.
 * Version: 0.5.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Text Domain: anotter-demo-foundation
 * License: GPL-2.0-or-later
 *
 * @package AnotterDemoFoundation
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/includes/contact.php';
require_once __DIR__ . '/includes/commerce.php';
require_once __DIR__ . '/includes/stock-reservation.php';
require_once __DIR__ . '/includes/mail-capture.php';
require_once __DIR__ . '/includes/payments.php';

// Register the available Woo translation under the site's locale. Core remains es_UY.
add_action( 'init', function () {
	$locale = get_locale();
	$fallback = get_option( 'anotter_demo_woocommerce_locale', $locale );
	$file = WP_LANG_DIR . '/plugins/woocommerce-' . $fallback . '.mo';
	if ( $fallback !== $locale && is_readable( $file ) ) {
		load_textdomain( 'woocommerce', $file, $locale );
	}
}, -1 );

add_filter( 'load_script_translation_file', function ( $file, $handle, $domain ) {
	if ( 'woocommerce' !== $domain || ! $file ) {
		return $file;
	}
	$locale = get_locale();
	$fallback = get_option( 'anotter_demo_woocommerce_locale', $locale );
	$candidate = str_replace( '-' . $locale . '-', '-' . $fallback . '-', $file );
	return is_readable( $candidate ) ? $candidate : $file;
}, 10, 3 );

add_action( 'init', function () {
	load_plugin_textdomain( 'anotter-demo-foundation', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/**
 * Mark every newly created order, including admin, REST, and checkout block orders.
 *
 * @param int      $order_id Order ID.
 * @param WC_Order $order    Order instance.
 */
function anotter_demo_mark_order( $order_id, $order = null ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
	if ( ! $order || 'yes' === $order->get_meta( '_anotter_demo' ) ) {
		return;
	}
	$order->update_meta_data( '_anotter_demo', 'yes' );
	$order->save();
	$order->add_order_note( __( 'DEMO INDEPENDIENTE — Pedido ficticio sin cobro ni envío real.', 'anotter-demo-foundation' ) );
}
add_action( 'woocommerce_new_order', 'anotter_demo_mark_order', 10, 2 );

add_filter( 'woocommerce_order_number', function ( $number, $order ) {
	return 'yes' === $order->get_meta( '_anotter_demo' ) ? 'DEMO-' . $number : $number;
}, 10, 2 );

// mail-capture.php suppresses every outgoing message; HTTP/webhooks remain untouched.
