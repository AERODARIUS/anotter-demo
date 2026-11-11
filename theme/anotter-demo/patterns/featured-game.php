<?php
/**
 * Title: Live featured game
 * Slug: anotter-demo/featured-game
 * Categories: featured
 * Inserter: yes
 * @package AnotterDemo
 */
defined( 'ABSPATH' ) || exit;
?>
<?php
$anotter_primary_id = function_exists( 'wc_get_product_id_by_sku' ) ? wc_get_product_id_by_sku( 'DEMO-CARDS-001' ) : 0;
if ( $anotter_primary_id && wc_get_product( $anotter_primary_id )->is_visible() ) :
?>
<!-- wp:woocommerce/single-product <?php echo wp_json_encode( array( 'productId' => $anotter_primary_id ) ); ?> -->
<div class="wp-block-woocommerce-single-product aot-featured-grid">
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfSingleProductBlock":true} /-->
<!-- wp:group {"className":"aot-featured-copy","layout":{"type":"default"}} -->
<div class="wp-block-group aot-featured-copy">
<!-- wp:post-title {"level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/single-product/product-title"} /-->
<!-- wp:woocommerce/product-summary {"isDescendentOfSingleProductBlock":true} /-->
<!-- wp:woocommerce/product-specifications {"showWeight":false,"showDimensions":false} /-->
<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductBlock":true} /-->
<!-- wp:woocommerce/product-stock-indicator /-->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_permalink( $anotter_primary_id ) ); ?>"><?php esc_html_e( 'Ver detalles del juego', 'anotter-demo' ); ?> <span aria-hidden="true">&rarr;</span></a></div><!-- /wp:button --></div><!-- /wp:buttons -->
</div><!-- /wp:group -->
</div><!-- /wp:woocommerce/single-product -->
<?php endif; ?>
