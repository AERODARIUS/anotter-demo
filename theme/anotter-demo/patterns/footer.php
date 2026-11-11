<?php
/**
 * Title: Storefront footer
 * Slug: anotter-demo/footer
 * Categories: footer
 * Inserter: no
 * @package AnotterDemo
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"aot-shell aot-footer-grid","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-footer-grid">
<!-- wp:group {"className":"aot-footer-brand","layout":{"type":"default"}} -->
<div class="wp-block-group aot-footer-brand">
<!-- wp:html -->
<a class="aot-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="aot-brand-mark" aria-hidden="true"><i></i><i></i></span><span>anotter<span class="aot-brand-demo">game / demo</span></span><span class="screen-reader-text"> — <?php esc_html_e( 'Inicio', 'anotter-demo' ); ?></span></a>
<!-- /wp:html -->
<!-- wp:paragraph {"className":"aot-footer-description"} -->
<p class="aot-footer-description"><?php echo esc_html__( 'Juegos para compartir la mesa. Un concepto visual original para una demostración independiente.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-footer-links","layout":{"type":"default"}} -->
<div class="wp-block-group aot-footer-links">
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'Explorá', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:navigation {"overlayMenu":"never","ariaLabel":"Navegación del pie","layout":{"type":"flex","orientation":"vertical"}} -->
<!-- wp:navigation-link <?php echo wp_json_encode( array( 'label' => __( 'Tienda', 'anotter-demo' ), 'url' => anotter_demo_url( 'shop' ) ) ); ?> /-->
<!-- wp:navigation-link <?php echo wp_json_encode( array( 'label' => __( 'Cómo se juega', 'anotter-demo' ), 'url' => anotter_demo_url( 'how' ) ) ); ?> /-->
<!-- wp:navigation-link <?php echo wp_json_encode( array( 'label' => __( 'FAQ', 'anotter-demo' ), 'url' => anotter_demo_url( 'faq' ) ) ); ?> /-->
<!-- wp:navigation-link <?php echo wp_json_encode( array( 'label' => __( 'Contacto', 'anotter-demo' ), 'url' => anotter_demo_url( 'contact' ) ) ); ?> /-->

<!-- /wp:navigation -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-footer-contact","layout":{"type":"default"}} -->
<div class="wp-block-group aot-footer-contact">
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'Sigamos en contacto', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Correo y redes sociales pendientes de autorización.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Instagram · Próximamente', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'YouTube · Próximamente', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-shell aot-footer-bottom","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-footer-bottom">
<!-- wp:paragraph {"className":"aot-small"} -->
<p class="aot-small">© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html__( 'AnOtter Game — Demo independiente.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="aot-legal-links"><a href="<?php echo esc_url( anotter_demo_url( 'privacy' ) ); ?>"><?php echo esc_html__( 'Privacidad de la demo', 'anotter-demo' ); ?></a><a href="<?php echo esc_url( anotter_demo_url( 'terms' ) ); ?>"><?php echo esc_html__( 'Términos de la demo', 'anotter-demo' ); ?></a></div>
<!-- /wp:html -->
<!-- wp:pattern {"slug":"anotter-demo/demo-notice"} /-->
</div>
<!-- /wp:group -->
