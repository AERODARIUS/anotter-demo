<?php
/** Real WordPress M02 assertions, including the public form's server-side validator. */
$failures = array();
$check = function ( $condition, $message ) use ( &$failures ) {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};
foreach ( get_option( 'anotter_demo_pages_v2', array() ) as $key => $id ) {
	$check( 'publish' === get_post_status( $id ), $key . ' page must be published' );
}
$check( 5 === count( get_option( 'anotter_demo_pages_v2', array() ) ), 'All five informational/legal demo pages must exist' );
$check( shortcode_exists( 'anotter_demo_contact' ), 'Demo contact form must be registered' );
$valid = array( 'name' => 'Persona ficticia', 'email' => 'demo@example.invalid', 'subject' => 'Consulta de prueba', 'message' => 'Mensaje ficticio para validar.' );
$check( array() === anotter_demo_contact_errors( $valid ), 'Valid demo input must pass validation' );
$invalid = $valid;
$invalid['email'] = 'not-an-email';
$invalid['message'] = str_repeat( 'x', 3001 );
$errors = anotter_demo_contact_errors( $invalid );
$check( isset( $errors['email'], $errors['message'] ), 'Invalid email and oversized message must be rejected' );
$invalid['name'] = array( 'malicious' );
$check( isset( anotter_demo_contact_errors( $invalid )['name'] ), 'Array-shaped user input must be rejected' );
$registry = WP_Block_Type_Registry::get_instance();
foreach ( array( 'woocommerce/product-collection', 'woocommerce/product-image-gallery', 'woocommerce/product-specifications', 'woocommerce/add-to-cart-form', 'woocommerce/mini-cart', 'core/navigation', 'core/details' ) as $name ) {
	$check( $registry->is_registered( $name ), 'Required native block missing: ' . $name );
}
$walk = function ( $blocks ) use ( &$walk, $registry, $check ) {
	foreach ( $blocks as $block ) {
		if ( $block['blockName'] ) {
			$check( $registry->is_registered( $block['blockName'] ), 'Unregistered block: ' . $block['blockName'] );
		}
		$walk( $block['innerBlocks'] );
	}
};
foreach ( glob( get_theme_file_path( '/templates/*.html' ) ) as $file ) {
	$walk( parse_blocks( file_get_contents( $file ) ) );
}
foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
	if ( str_starts_with( $pattern['name'], 'anotter-demo/' ) ) {
		$walk( parse_blocks( $pattern['content'] ) );
	}
}
$check( 'UYU' === get_woocommerce_currency() && '0' === (string) get_option( 'blog_public' ), 'M01 currency and noindex settings must remain intact' );
if ( $failures ) {
	foreach ( $failures as $failure ) {
		WP_CLI::warning( $failure );
	}
	WP_CLI::error( 'M02 live assertions failed.' );
}
WP_CLI::success( 'M02 live page, block, input-validation and preserved-setting assertions passed.' );
