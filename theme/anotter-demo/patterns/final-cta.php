<?php
/**
 * Title: Final purchase call to action
 * Slug: anotter-demo/final-cta
 * Categories: featured
 * Inserter: yes
 * @package AnotterDemo
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"aot-final-cta","layout":{"type":"default"}} -->
<div class="wp-block-group aot-final-cta">
<!-- wp:group {"className":"aot-shell aot-cta-inner","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-cta-inner">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( 'QUE NO FALTE UNA BUENA RONDA', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'Tu próxima juntada empieza en la mesa.', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"aot-section-lead"} -->
<p class="aot-section-lead"><?php echo esc_html__( 'Explorá el catálogo ficticio y probá la experiencia de elegir un juego.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"aot-primary-button"} -->
<div class="wp-block-button aot-primary-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( anotter_demo_url( 'shop' ) ); ?>"><?php echo esc_html__( 'Explorar la tienda', 'anotter-demo' ); ?> <span aria-hidden="true">→</span></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
