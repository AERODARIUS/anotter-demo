<?php
/**
 * Title: Storefront header
 * Slug: anotter-demo/header
 * Categories: header
 * Inserter: no
 * @package AnotterDemo
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"aot-demo-bar","layout":{"type":"default"}} -->
<div class="wp-block-group aot-demo-bar">
<!-- wp:paragraph {"className":"aot-demo-line"} -->
<p class="aot-demo-line"><?php echo esc_html__( 'DEMO INDEPENDIENTE · Productos ficticios. Sin cobros ni envíos reales.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-shell aot-header-row","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-header-row">
<!-- wp:html -->
<a class="aot-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="aot-brand-mark" aria-hidden="true"><i></i><i></i></span><span>anotter<span class="aot-brand-demo">game / demo</span></span><span class="screen-reader-text"> — <?php esc_html_e( 'Inicio', 'anotter-demo' ); ?></span></a>
<!-- /wp:html -->
<!-- wp:navigation {"overlayMenu":"mobile","className":"aot-main-navigation","ariaLabel":"Navegación principal","layout":{"type":"flex","justifyContent":"right"}} -->
<!-- wp:navigation-link <?php echo wp_json_encode( array( 'label' => __( 'Tienda', 'anotter-demo' ), 'url' => anotter_demo_url( 'shop' ) ) ); ?> /-->
<!-- wp:navigation-link <?php echo wp_json_encode( array( 'label' => __( 'Cómo se juega', 'anotter-demo' ), 'url' => anotter_demo_url( 'how' ) ) ); ?> /-->
<!-- wp:navigation-link <?php echo wp_json_encode( array( 'label' => __( 'FAQ', 'anotter-demo' ), 'url' => anotter_demo_url( 'faq' ) ) ); ?> /-->
<!-- wp:navigation-link <?php echo wp_json_encode( array( 'label' => __( 'Contacto', 'anotter-demo' ), 'url' => anotter_demo_url( 'contact' ) ) ); ?> /-->

<!-- /wp:navigation -->
<!-- wp:woocommerce/mini-cart {"productCountVisibility":"always","cartAndCheckoutRenderStyle":"link","className":"aot-mini-cart"} /-->
<!-- wp:group {"className":"aot-header-cta","layout":{"type":"default"}} -->
<div class="wp-block-group aot-header-cta">
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"aot-primary-button"} -->
<div class="wp-block-button aot-primary-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( anotter_demo_url( 'shop' ) ); ?>"><?php echo esc_html__( 'Elegí tu juego', 'anotter-demo' ); ?> <span aria-hidden="true">→</span></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
