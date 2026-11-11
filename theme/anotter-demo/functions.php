<?php
/** Theme presentation only. Store and order behavior belongs in plugins. @package AnotterDemo */
defined( 'ABSPATH' ) || exit;

/** Stable page links, including translated native WooCommerce page slugs. */
function anotter_demo_url( $key ) {
	if ( 'shop' === $key && function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}
	$pages = get_option( 'anotter_demo_pages_v2', array() );
	$slugs = array( 'how' => 'como-se-juega', 'faq' => 'preguntas-frecuentes', 'contact' => 'contacto', 'privacy' => 'privacidad-demo', 'terms' => 'terminos-demo' );
	return isset( $pages[ $key ] ) ? get_permalink( $pages[ $key ] ) : home_url( '/' . ( $slugs[ $key ] ?? '' ) . '/' );
}

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'anotter-demo', get_template_directory() . '/languages' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	// Current target browsers render emoji natively; avoid the extra detection/download path.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'anotter-demo', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_script( 'anotter-demo-form-focus', get_theme_file_uri( 'assets/form-focus.js' ), array(), wp_get_theme()->get( 'Version' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
		wp_enqueue_script( 'anotter-demo-checkout-accessibility', get_theme_file_uri( 'assets/checkout-accessibility.js' ), array(), wp_get_theme()->get( 'Version' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
} );

// The published core es_UY pack lacks newer navigation strings. Fill only those gaps.
add_filter( 'gettext', function ( $translation, $text, $domain ) {
	if ( is_admin() || 'default' !== $domain || $translation !== $text ) {
		return $translation;
	}
	switch ( $text ) {
		case 'Open menu':
			return __( 'Abrir menú', 'anotter-demo' );
		case 'Close menu':
			return __( 'Cerrar menú', 'anotter-demo' );
		case 'Skip to content':
			return __( 'Saltar al contenido', 'anotter-demo' );
	}
	return $translation;
}, 10, 3 );

add_filter( 'render_block_woocommerce/product-price', function ( $content ) {
	return $content ? $content . '<span class="aot-currency-label">' . esc_html__( 'Pesos uruguayos · UYU', 'anotter-demo' ) . '</span>' : $content;
} );

add_action( 'wp_head', function () {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>', esc_url( get_theme_file_uri( 'assets/fonts/manrope-latin.woff2' ) ) );
} );
