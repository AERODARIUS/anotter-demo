<?php
/**
 * Title: Contact page with honest demo form
 * Slug: anotter-demo/contact
 * Categories: text
 * Inserter: yes
 * @package AnotterDemo
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"aot-contact-grid","layout":{"type":"default"}} -->
<div class="wp-block-group aot-contact-grid">
<!-- wp:group {"className":"aot-contact-intro","layout":{"type":"default"}} -->
<div class="wp-block-group aot-contact-intro">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( 'CONTACTO · DEMOSTRACIÓN', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'Una conversación también puede empezar con un juego.', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Este es un ejemplo de página de contacto. Probá el formulario con datos ficticios; no hay un canal comercial activo.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:group {"className":"aot-info-card","layout":{"type":"default"}} -->
<div class="wp-block-group aot-info-card">
<!-- wp:heading {"level":3,"className":""} -->
<h3 class="wp-block-heading "><?php echo esc_html__( 'Información por confirmar', 'anotter-demo' ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Correo comercial: pendiente de autorización.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Redes sociales: enlaces pendientes.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Ubicación y horarios: no informados para esta demo.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-contact-panel","layout":{"type":"default"}} -->
<div class="wp-block-group aot-contact-panel">
<!-- wp:shortcode -->
[anotter_demo_contact]
<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
