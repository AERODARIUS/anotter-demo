<?php
/** First-run store defaults; subsequent runs preserve merchant edits and stock. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'WooCommerce' ) ) {
	exit( 1 );
}

if ( ! get_option( 'anotter_demo_settings_v1' ) ) {
	$settings = array(
		'blogname'                              => 'AnOtter Game — Demo',
		'blogdescription'                       => 'Prototipo independiente. Productos y pedidos de demostración.',
		'timezone_string'                       => 'America/Montevideo',
		'blog_public'                           => '0',
		'permalink_structure'                   => '/%postname%/',
		'woocommerce_default_country'           => 'UY',
		'woocommerce_currency'                  => 'UYU',
		'woocommerce_currency_pos'              => 'left_space',
		'woocommerce_price_thousand_sep'         => '.',
		'woocommerce_price_decimal_sep'          => ',',
		'woocommerce_price_num_decimals'         => '2',
		'woocommerce_allowed_countries'         => 'specific',
		'woocommerce_specific_allowed_countries' => array( 'UY' ),
		'woocommerce_ship_to_countries'         => 'specific',
		'woocommerce_specific_ship_to_countries' => array( 'UY' ),
		'woocommerce_default_customer_address'  => 'base',
		'woocommerce_manage_stock'              => 'yes',
		'woocommerce_enable_guest_checkout'     => 'yes',
		'woocommerce_calc_taxes'                 => 'no',
		'woocommerce_demo_store'                 => 'yes',
		'woocommerce_coming_soon'                => 'no',
		'woocommerce_demo_store_notice'          => 'DEMO INDEPENDIENTE — Productos ficticios y pedidos de prueba. Sin cobros reales.',
		'woocommerce_allow_tracking'             => 'no',
		'woocommerce_enable_marketplace_suggestions' => 'no',
	);
	foreach ( $settings as $key => $value ) {
		update_option( $key, $value );
	}
	// Only a built-in offline gateway can be enabled by this initializer.
	update_option( 'woocommerce_cod_settings', array(
		'enabled'            => 'yes' === getenv( 'DEMO_ENABLE_COD' ) ? 'yes' : 'no',
		'title'              => 'DEMO — Pago ficticio al retirar / recibir',
		'description'        => 'Solo pruebas locales. No se realizará ningún cobro ni envío real.',
		'instructions'       => 'Pedido de demostración, sin valor comercial.',
		'enable_for_virtual' => 'yes',
	) );
	update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'no' ) );
	update_option( 'woocommerce_cheque_settings', array( 'enabled' => 'no' ) );
	update_option( 'anotter_demo_settings_v1', 1 );
}

// Official WooCommerce installer repairs absent pages without replacing existing content.
WC_Install::create_pages();

if ( ! get_option( 'anotter_demo_home_v1' ) ) {
	$home = get_page_by_path( 'inicio-demo' );
	if ( ! $home ) {
		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => 'inicio-demo',
			'post_title'   => 'Inicio — Demo',
			'post_content' => '<!-- wp:pattern {"slug":"anotter-demo/home-intro"} /-->',
		), true );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( $id->get_error_message() );
		}
	} else {
		$id = $home->ID;
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $id );
	update_option( 'anotter_demo_home_v1', $id );
}

// Persist the zone ID immediately so an interrupted setup cannot duplicate zones.
$zone_id = (int) get_option( 'anotter_demo_shipping_zone_id' );
if ( ! $zone_id ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'Uruguay — DEMO' );
	$zone->set_zone_order( 0 );
	$zone->add_location( 'UY', 'country' );
	$zone_id = $zone->save();
	update_option( 'anotter_demo_shipping_zone_id', $zone_id );
}
$zone = new WC_Shipping_Zone( $zone_id );
if ( ! $zone->get_id() ) {
	WP_CLI::error( 'The managed shipping zone was deleted. Review it manually; setup will not replace your shipping configuration.' );
}
if ( ! get_option( 'anotter_demo_shipping_v1' ) ) {
	$existing = array();
	foreach ( $zone->get_shipping_methods() as $method ) {
		$existing[ $method->id ] = true;
	}
	foreach ( array( 'flat_rate', 'local_pickup' ) as $method_id ) {
		if ( isset( $existing[ $method_id ] ) ) {
			continue;
		}
		$instance = $zone->add_shipping_method( $method_id );
		if ( ! $instance ) {
			WP_CLI::error( 'Could not create the demo shipping method.' );
		}
		$cost = 'flat_rate' === $method_id ? getenv( 'DEMO_FLAT_RATE_COST' ) : '0';
		if ( ! is_numeric( $cost ) || (float) $cost < 0 ) {
			WP_CLI::error( 'DEMO_FLAT_RATE_COST must be a nonnegative number.' );
		}
		update_option( 'woocommerce_' . $method_id . '_' . $instance . '_settings', array(
			'title'      => 'flat_rate' === $method_id ? 'DEMO — Envío de prueba' : 'DEMO — Retiro local',
			'tax_status' => 'none',
			'cost'       => wc_format_decimal( $cost ),
		) );
	}
	update_option( 'anotter_demo_shipping_v1', 1 );
}
WP_CLI::success( 'Store defaults initialized; existing edits are preserved.' );
