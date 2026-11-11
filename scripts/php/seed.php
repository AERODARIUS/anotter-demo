<?php
/** Create missing fixtures only. Never reset stock, price or merchant edits. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'WC_Product_Simple' ) ) {
	exit( 1 );
}
require_once ABSPATH . 'wp-admin/includes/image.php';
$fixtures = require __DIR__ . '/products.php';
foreach ( $fixtures as $fixture ) {
	$id = wc_get_product_id_by_sku( $fixture['sku'] );
	if ( $id ) {
		if ( 'yes' !== get_post_meta( $id, '_anotter_demo', true ) ) {
			WP_CLI::error( 'SKU collision with an unmanaged product: ' . $fixture['sku'] );
		}
		WP_CLI::log( 'Preserved ' . $fixture['sku'] );
		continue;
	}
	$category = term_exists( $fixture['category_slug'], 'product_cat' );
	if ( ! $category ) {
		$category = wp_insert_term( $fixture['category'], 'product_cat', array( 'slug' => $fixture['category_slug'] ) );
	}
	if ( is_wp_error( $category ) ) {
		WP_CLI::error( $category->get_error_message() );
	}
	$product = new WC_Product_Simple();
	$product->set_name( $fixture['name'] );
	$product->set_slug( $fixture['slug'] );
	$product->set_status( 'publish' );
	$product->set_description( $fixture['description'] );
	$product->set_short_description( $fixture['short'] );
	$product->set_regular_price( $fixture['price'] );
	$product->set_sku( $fixture['sku'] );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( $fixture['stock'] );
	$product->set_backorders( 'no' );
	$product->set_stock_status( 'instock' );
	$product->set_weight( '0.2' );
	$product->set_category_ids( array( (int) $category['term_id'] ) );
	$product->update_meta_data( '_anotter_demo', 'yes' );

	// Original, deliberately plain PNG. No external asset downloads or SVG upload exceptions.
	$uploads = wp_upload_dir();
	if ( $uploads['error'] ) {
		WP_CLI::error( $uploads['error'] );
	}
	$file = $uploads['path'] . '/' . strtolower( $fixture['sku'] ) . '.png';
	$picture = imagecreatetruecolor( 800, 600 );
	imagefill( $picture, 0, 0, imagecolorallocate( $picture, 235, 238, 240 ) );
	$ink = imagecolorallocate( $picture, 35, 46, 55 );
	imagestring( $picture, 5, 320, 255, 'DEMO / FICTION', $ink );
	imagestring( $picture, 4, 325, 300, $fixture['sku'], $ink );
	if ( ! imagepng( $picture, $file ) ) {
		WP_CLI::error( 'Could not write placeholder image.' );
	}
	imagedestroy( $picture );
	$attachment = wp_insert_attachment( array(
		'post_mime_type' => 'image/png',
		'post_title'     => $fixture['name'] . ' — placeholder',
		'post_status'    => 'inherit',
	), $file, 0, true );
	if ( is_wp_error( $attachment ) ) {
		WP_CLI::error( $attachment->get_error_message() );
	}
	update_post_meta( $attachment, '_wp_attachment_image_alt', 'Imagen de muestra de un producto ficticio: ' . $fixture['name'] );
	wp_update_attachment_metadata( $attachment, wp_generate_attachment_metadata( $attachment, $file ) );
	$product->set_image_id( $attachment );
	$product->save();
	$persisted = wc_get_product( $product->get_id() );
	if ( $fixture['stock'] !== $persisted->get_stock_quantity() || (float) $fixture['price'] !== (float) $persisted->get_price() ) {
		WP_CLI::error( 'Seeded stock or price did not persist for ' . $fixture['sku'] );
	}
	wp_update_post( array( 'ID' => $attachment, 'post_parent' => $product->get_id() ) );
	WP_CLI::success( 'Created ' . $fixture['sku'] );
}
