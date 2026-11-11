<?php
/** Improve only untouched pinned native page defaults; preserve edited pages. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
if ( get_option( 'anotter_demo_checkout_pages_v3' ) ) {
	return;
}
$hashes = array(
	'cart' => '2122f473ab3497680baf0341591a19f409bfa52713a7d49404c9bffe5149227e',
	'checkout' => '5beaa3e7a0b206e553928e70dbb82b0d03aea5d0ef911d06e496a106eb4bcf62',
);
foreach ( $hashes as $page => $hash ) {
	$id = wc_get_page_id( $page );
	$content = get_post_field( 'post_content', $id );
	if ( ! hash_equals( $hash, hash( 'sha256', $content ) ) ) {
		WP_CLI::warning( $page . ' has custom content; its blocks were preserved.' );
		continue;
	}
	update_post_meta( $id, '_anotter_demo_original_content_v3', $content );
	if ( 'cart' === $page ) {
		$content = preg_replace( '~<!-- wp:woocommerce/empty-cart-block -->.*?<!-- /wp:woocommerce/empty-cart-block -->~s', '<!-- wp:woocommerce/empty-cart-block --><div class="wp-block-woocommerce-empty-cart-block"><!-- wp:pattern {"slug":"anotter-demo/empty-cart"} /--></div><!-- /wp:woocommerce/empty-cart-block -->', $content );
		$content = str_replace( 'You may be interested in&hellip;', 'Para sumar a la mesa', $content );
	} else {
		$content = str_replace( '<!-- wp:woocommerce/checkout-terms-block -->', '<!-- wp:woocommerce/checkout-terms-block {"checkbox":true} -->', $content );
	}
	$result = wp_update_post( array( 'ID' => $id, 'post_content' => $content ), true );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
}
update_option( 'anotter_demo_checkout_pages_v3', 1 );
WP_CLI::success( 'Untouched native cart/checkout content enhanced; customized pages preserved.' );
