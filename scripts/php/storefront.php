<?php
/** M02 content migration. Create missing demo pages; preserve all existing editorial/product data. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$definitions = array(
	'how'     => array( 'como-se-juega', 'Cómo se juega', 'how-to-play' ),
	'faq'     => array( 'preguntas-frecuentes', 'Preguntas frecuentes', 'faq' ),
	'contact' => array( 'contacto', 'Hablemos de la próxima ronda', 'contact' ),
	'privacy' => array( 'privacidad-demo', 'Privacidad de la demostración', 'privacy' ),
	'terms'   => array( 'terminos-demo', 'Términos de la demostración', 'terms' ),
);
$pages = get_option( 'anotter_demo_pages_v2', array() );
// Translate only untouched vendor-generated English titles; preserve slugs and editorial changes.
foreach ( array( 'shop' => array( 'Shop', 'Tienda' ), 'cart' => array( 'Cart', 'Carrito' ), 'checkout' => array( 'Checkout', 'Finalizar compra' ), 'myaccount' => array( 'My account', 'Mi cuenta' ) ) as $page => $titles ) {
	$id = wc_get_page_id( $page );
	if ( $id > 0 && $titles[0] === get_post_field( 'post_title', $id ) ) {
		wp_update_post( array( 'ID' => $id, 'post_title' => $titles[1] ) );
	}
}
foreach ( $definitions as $key => $definition ) {
	if ( isset( $pages[ $key ] ) && get_post( $pages[ $key ] ) ) {
		continue;
	}
	$existing = get_page_by_path( $definition[0] );
	if ( $existing && 'yes' !== get_post_meta( $existing->ID, '_anotter_demo', true ) ) {
		WP_CLI::error( 'Page slug belongs to unmarked content: ' . $definition[0] );
	}
	$id = $existing ? $existing->ID : wp_insert_post( array(
		'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $definition[0],
		'post_title' => $definition[1],
		'post_content' => '<!-- wp:pattern {"slug":"anotter-demo/' . $definition[2] . '"} /-->',
		'meta_input' => array( '_anotter_demo' => 'yes' ),
	), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	$pages[ $key ] = $id;
	update_option( 'anotter_demo_pages_v2', $pages );
}

$artwork = array( 'DEMO-CARDS-001' => 'ronda-demo', 'DEMO-EXP-001' => 'expansion-demo', 'DEMO-ACC-001' => 'accesorio-demo' );
foreach ( $artwork as $sku => $asset ) {
	$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
	if ( ! $product || 'yes' !== $product->get_meta( '_anotter_demo' ) || $product->get_meta( '_anotter_m02_artwork' ) ) {
		continue;
	}
	$old_file = get_attached_file( $product->get_image_id() );
	// Only migrate the known M01-generated placeholder. Preserve custom merchant images.
	if ( $product->get_image_id() && strtolower( $sku ) . '.png' !== basename( $old_file ?: '' ) ) {
		continue;
	}
	$source = get_theme_file_path( '/assets/images/' . $asset . '-1440.webp' );
	$temp = wp_tempnam( $source );
	if ( ! is_file( $source ) || ! copy( $source, $temp ) ) {
		WP_CLI::error( 'Missing or unreadable original demo artwork.' );
	}
	$attachment = media_handle_sideload( array( 'name' => $asset . '.webp', 'tmp_name' => $temp ), $product->get_id(), $product->get_name() . ' — arte conceptual DEMO' );
	if ( is_wp_error( $attachment ) ) {
		WP_CLI::error( $attachment->get_error_message() );
	}
	update_post_meta( $attachment, '_wp_attachment_image_alt', 'Arte conceptual de ' . $product->get_name() . '. Producto ficticio, no oficial.' );
	$product->set_image_id( $attachment );
	$product->update_meta_data( '_anotter_m02_artwork', $attachment );
	// Add a visibly fictional attribute only when no merchant attributes exist.
	if ( 'DEMO-CARDS-001' === $sku && ! $product->get_attributes() ) {
		$attributes = array();
		foreach ( array( 'Personas (ficha ficticia)' => '2–6', 'Duración (ficha ficticia)' => '20 minutos', 'Contenido (ficha ficticia)' => 'Cartas de desafío imaginarias' ) as $name => $value ) {
			$attribute = new WC_Product_Attribute();
			$attribute->set_name( $name );
			$attribute->set_options( array( $value ) );
			$attribute->set_visible( true );
			$attributes[] = $attribute;
		}
		$product->set_attributes( $attributes );
	}
	$product->save();
}

// A shared native tag allows WooCommerce itself to discover related demo products.
$tag = term_exists( 'coleccion-demo', 'product_tag' );
if ( ! $tag ) {
	$tag = wp_insert_term( 'Colección demo', 'product_tag', array( 'slug' => 'coleccion-demo' ) );
}
if ( is_wp_error( $tag ) ) {
	WP_CLI::error( $tag->get_error_message() );
}
foreach ( array_keys( $artwork ) as $sku ) {
	$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
	if ( $product && 'yes' === $product->get_meta( '_anotter_demo' ) && ! $product->get_meta( '_anotter_m02_collection_tag' ) ) {
		$terms = wp_set_object_terms( $product->get_id(), array( (int) $tag['term_id'] ), 'product_tag', true );
		if ( is_wp_error( $terms ) ) {
			WP_CLI::error( $terms->get_error_message() );
		}
		$product->update_meta_data( '_anotter_m02_collection_tag', 'yes' );
		$product->save();
		wc_delete_product_transients( $product->get_id() );
	}
}
WP_CLI::success( 'M02 pages and original demo artwork initialized; stock, prices and edits preserved.' );
