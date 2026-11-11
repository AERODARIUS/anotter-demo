<?php
/**
 * Title: Carrito vacío
 * Slug: anotter-demo/empty-cart
 * Categories: anotter-demo
 * Inserter: false
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"aot-empty-cart","layout":{"type":"constrained"}} -->
<div class="wp-block-group aot-empty-cart">
<!-- wp:paragraph {"className":"aot-eyebrow"} --><p class="aot-eyebrow">Una mesa llena empieza por acá</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Tu próxima ronda te está esperando.</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Elegí un juego, sumalo al carrito y armá tu pedido de demostración.</p><!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Explorar juegos &rarr;</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
</div><!-- /wp:group -->
