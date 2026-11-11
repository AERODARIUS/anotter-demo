<?php
/** Demo eligibility only. The official plugin owns payments and notifications. */
defined( 'ABSPATH' ) || exit;

function anotter_demo_test_seller_ready() {
	global $mercadopago;
	return isset( $mercadopago->sellerConfig, $mercadopago->storeConfig )
		&& $mercadopago->sellerConfig->isTestUser()
		&& 'MLU' === $mercadopago->sellerConfig->getSiteId()
		&& ! $mercadopago->storeConfig->isTestMode()
		&& '' !== $mercadopago->sellerConfig->getCredentialsAccessToken();
}

add_filter( 'woocommerce_available_payment_gateways', function ( $gateways ) {
	foreach ( $gateways as $id => $gateway ) {
		if ( str_starts_with( $id, 'woo-mercado-pago-' ) && (
			'woo-mercado-pago-basic' !== $id || ! anotter_demo_test_seller_ready()
			|| ! defined( 'ANOTTER_PUBLIC_REQUEST' ) || ! ANOTTER_PUBLIC_REQUEST
		) ) {
			unset( $gateways[ $id ] );
		}
	}
	return $gateways;
}, 100 );

// Saved editorial blocks can contain absolute local image/link URLs. Rewrite only
// the explicit local origin at render time; never change serialized database data.
add_filter( 'render_block', function ( $content ) {
	if ( defined( 'ANOTTER_PUBLIC_REQUEST' ) && ANOTTER_PUBLIC_REQUEST ) {
		$local = rtrim( getenv( 'ANOTTER_LOCAL_URL' ) ?: '', '/' );
		if ( $local ) {
			$content = str_replace( $local . '/', home_url( '/' ), $content );
		}
	}
	return $content;
}, 20 );

add_filter( 'woocommerce_thankyou_order_received_text', function ( $text, $order ) {
	if ( $order instanceof WC_Order && 'yes' === $order->get_meta( '_anotter_demo' ) && 'woo-mercado-pago-basic' === $order->get_payment_method() ) {
		$text .= '<span class="aot-order-status">' . esc_html( $order->is_paid()
			? __( 'DEMO — Pago de prueba confirmado. Sin cobro ni envío real.', 'anotter-demo-foundation' )
			: __( 'DEMO — Pago aún no confirmado. Esperando verificación de Mercado Pago; revisá el estado del pedido.', 'anotter-demo-foundation' ) ) . '</span>';
	}
	return $text;
}, 20, 2 );
