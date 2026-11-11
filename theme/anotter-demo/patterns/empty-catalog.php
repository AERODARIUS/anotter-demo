<?php
/**
 * Title: Empty catalog state
 * Slug: anotter-demo/empty-catalog
 * Categories: text
 * Inserter: yes
 * @package AnotterDemo
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"aot-empty-state","layout":{"type":"default"}} -->
<div class="wp-block-group aot-empty-state">
<!-- wp:heading {"level":3,"className":""} -->
<h3 class="wp-block-heading "><?php echo esc_html__( 'Todavía no hay juegos en esta ronda.', 'anotter-demo' ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Probá otra categoría o volvé al catálogo para seguir explorando.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"aot-primary-button"} -->
<div class="wp-block-button aot-primary-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( anotter_demo_url( 'shop' ) ); ?>"><?php echo esc_html__( 'Ver todo el catálogo', 'anotter-demo' ); ?> <span aria-hidden="true">→</span></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
