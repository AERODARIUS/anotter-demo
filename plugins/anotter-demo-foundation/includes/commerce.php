<?php
/** M03 presentation and Uruguay labels; native WooCommerce owns all commerce logic. */
defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_get_country_locale', function ( $locale ) {
	$locale['UY']['city']['label'] = __( 'Ciudad / localidad', 'anotter-demo-foundation' );
	$locale['UY']['state']['label'] = __( 'Departamento', 'anotter-demo-foundation' );
	$locale['UY']['address_1']['label'] = __( 'Calle y número', 'anotter-demo-foundation' );
	$locale['UY']['postcode']['label'] = __( 'Código postal', 'anotter-demo-foundation' );
	return $locale;
} );

add_filter( 'woocommerce_thankyou_order_received_text', function ( $text, $order ) {
	if ( ! $order instanceof WC_Order || 'yes' !== $order->get_meta( '_anotter_demo' ) ) {
		return $text;
	}
	$text .= '<span class="aot-order-status">' . esc_html( sprintf(
		/* translators: %s: native order status. */
		__( 'Estado del pedido: %s.', 'anotter-demo-foundation' ), wc_get_order_status_name( $order->get_status() )
	) ) . '</span>';
	if ( $order->get_shipping_method() ) {
		// The pinned native totals block suppresses its "shipped via" suffix.
		$text .= '<span class="aot-order-shipping">' . esc_html( sprintf(
			/* translators: %s: persisted native shipping method name. */
			__( 'Envío: %s.', 'anotter-demo-foundation' ), $order->get_shipping_method()
		) ) . '</span>';
	}
	if ( 'bacs' === $order->get_payment_method() ) {
		$text .= '<span class="aot-order-status">' . esc_html__( 'DEMO — Pago no verificado. No transfieras dinero. Sin cobro ni envío real.', 'anotter-demo-foundation' ) . '</span>';
	}
	return $text;
}, 10, 2 );
