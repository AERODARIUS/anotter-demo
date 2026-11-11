<?php
/**
 * Title: Complete demo homepage
 * Slug: anotter-demo/home-intro
 * Categories: featured
 * Inserter: yes
 * @package AnotterDemo
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"aot-hero","layout":{"type":"default"}} -->
<div class="wp-block-group aot-hero">
<!-- wp:group {"className":"aot-shell aot-hero-grid","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-hero-grid">
<!-- wp:group {"className":"aot-hero-copy","layout":{"type":"default"}} -->
<div class="wp-block-group aot-hero-copy">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( 'JUEGOS DE CARTAS · CONCEPTO DE DEMOSTRACIÓN', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"aot-display"} -->
<h1 class="wp-block-heading aot-display"><?php echo esc_html__( 'La próxima ronda empieza acá.', 'anotter-demo' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"aot-hero-lead"} -->
<p class="aot-hero-lead"><?php echo esc_html__( 'Un mazo, una mesa y ganas de compartir. Descubrí un universo de juegos ficticios pensado para esta demo.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"aot-primary-button"} -->
<div class="wp-block-button aot-primary-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( anotter_demo_url( 'shop' ) ); ?>"><?php echo esc_html__( 'Comprar ahora', 'anotter-demo' ); ?> <span aria-hidden="true">→</span></a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( anotter_demo_url( 'how' ) ); ?>"><?php echo esc_html__( 'Cómo se juega', 'anotter-demo' ); ?> <span aria-hidden="true">→</span></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:paragraph {"className":"aot-caption"} -->
<p class="aot-caption"><?php echo esc_html__( 'Identidad y arte originales de muestra. No son productos oficiales.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-hero-visual","layout":{"type":"default"}} -->
<div class="wp-block-group aot-hero-visual">
<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"aot-art"} -->
<figure class="wp-block-image size-full aot-art"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/ronda-demo-1440.webp' ) ); ?>" srcset="<?php echo esc_url( get_theme_file_uri( 'assets/images/ronda-demo-720.webp' ) ); ?> 720w, <?php echo esc_url( get_theme_file_uri( 'assets/images/ronda-demo-1440.webp' ) ); ?> 1440w" sizes="(max-width: 781px) calc(100vw - 32px), 580px" width="1440" height="960" alt="<?php echo esc_html__( 'Arte conceptual de un producto ficticio de demostración; no es mercadería oficial.', 'anotter-demo' ); ?>" loading="eager" decoding="async" fetchpriority="high"/></figure>
<!-- /wp:image -->
<!-- wp:paragraph {"className":"aot-art-caption"} -->
<p class="aot-art-caption"><?php echo esc_html__( 'RONDA / ARTE CONCEPTUAL', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-shell aot-section","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-section">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( 'EL JUEGO DE ESTA DEMO', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'Una excusa para volver a juntarse.', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"aot-section-lead"} -->
<p class="aot-section-lead"><?php echo esc_html__( 'Un juego ficticio de cartas y desafíos que invita a compartir la mesa. Arte y especificaciones de muestra, creados para esta demostración.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:pattern {"slug":"anotter-demo/featured-game"} /-->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-soft-section","layout":{"type":"default"}} -->
<div class="wp-block-group aot-soft-section">
<!-- wp:group {"className":"aot-shell aot-section","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-section">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( 'ASÍ IMAGINAMOS LA PARTIDA', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'Tres pasos. Muchas posibilidades.', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"aot-section-lead"} -->
<p class="aot-section-lead"><?php echo esc_html__( 'Guía de ejemplo para un juego ficticio, no un reglamento oficial.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:group {"className":"aot-grid aot-three","layout":{"type":"default"}} -->
<div class="wp-block-group aot-grid aot-three">
<!-- wp:group {"className":"aot-info-card aot-step","layout":{"type":"default"}} -->
<div class="wp-block-group aot-info-card aot-step">
<!-- wp:paragraph {"className":"aot-step-number"} -->
<p class="aot-step-number"><?php echo esc_html__( '01', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<span class="aot-step-art aot-step-01" aria-hidden="true"><i></i><i></i><i></i></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3,"className":""} -->
<h3 class="wp-block-heading "><?php echo esc_html__( 'Prepará la mesa', 'anotter-demo' ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'En este ejemplo ficticio, reuní al grupo y repartí las cartas de desafío.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-info-card aot-step","layout":{"type":"default"}} -->
<div class="wp-block-group aot-info-card aot-step">
<!-- wp:paragraph {"className":"aot-step-number"} -->
<p class="aot-step-number"><?php echo esc_html__( '02', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<span class="aot-step-art aot-step-02" aria-hidden="true"><i></i><i></i><i></i></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3,"className":""} -->
<h3 class="wp-block-heading "><?php echo esc_html__( 'Dale una vuelta', 'anotter-demo' ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Por turnos, elegí una carta e imaginá una respuesta. Las reglas son solo de muestra.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-info-card aot-step","layout":{"type":"default"}} -->
<div class="wp-block-group aot-info-card aot-step">
<!-- wp:paragraph {"className":"aot-step-number"} -->
<p class="aot-step-number"><?php echo esc_html__( '03', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<span class="aot-step-art aot-step-03" aria-hidden="true"><i></i><i></i><i></i></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3,"className":""} -->
<h3 class="wp-block-heading "><?php echo esc_html__( 'Compartí la ronda', 'anotter-demo' ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'La idea de esta demo: conversar, improvisar y pasar un buen rato en grupo.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( anotter_demo_url( 'how' ) ); ?>"><?php echo esc_html__( 'Ver cómo se juega', 'anotter-demo' ); ?> <span aria-hidden="true">→</span></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-shell aot-section","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-section">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( 'DISEÑADO PARA COMPARTIR', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'La mejor parte es con quién jugás.', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"aot-section-lead"} -->
<p class="aot-section-lead"><?php echo esc_html__( 'Beneficios conceptuales de esta demostración; no son afirmaciones sobre un juego oficial.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:group {"className":"aot-grid aot-three","layout":{"type":"default"}} -->
<div class="wp-block-group aot-grid aot-three">
<!-- wp:group {"className":"aot-benefit","layout":{"type":"default"}} -->
<div class="wp-block-group aot-benefit">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( '01', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":""} -->
<h3 class="wp-block-heading "><?php echo esc_html__( 'Menos pantallas, más mesa', 'anotter-demo' ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Una idea de entretenimiento social para este catálogo ficticio.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-benefit","layout":{"type":"default"}} -->
<div class="wp-block-group aot-benefit">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( '02', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":""} -->
<h3 class="wp-block-heading "><?php echo esc_html__( 'Una ronda sin vueltas', 'anotter-demo' ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Un concepto de juego simple: las reglas mostradas son solo ejemplos.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-benefit","layout":{"type":"default"}} -->
<div class="wp-block-group aot-benefit">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( '03', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":""} -->
<h3 class="wp-block-heading "><?php echo esc_html__( 'La juntada va con vos', 'anotter-demo' ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Formato compacto imaginario, para mostrar cómo se presenta un producto portable.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-shell aot-section aot-showcase","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-section aot-showcase">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( 'EL CATÁLOGO', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'Elegí tu próxima ronda.', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"aot-section-lead"} -->
<p class="aot-section-lead"><?php echo esc_html__( 'Juegos, expansiones y accesorios ficticios. Precios en pesos uruguayos.', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:pattern {"slug":"anotter-demo/product-showcase"} /-->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( anotter_demo_url( 'shop' ) ); ?>"><?php echo esc_html__( 'Ver toda la tienda', 'anotter-demo' ); ?> <span aria-hidden="true">→</span></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-shell aot-section aot-faq-preview","layout":{"type":"default"}} -->
<div class="wp-block-group aot-shell aot-section aot-faq-preview">
<!-- wp:group {"className":"aot-faq-intro","layout":{"type":"default"}} -->
<div class="wp-block-group aot-faq-intro">
<!-- wp:paragraph {"className":"aot-eyebrow"} -->
<p class="aot-eyebrow"><?php echo esc_html__( 'ANTES DE EMPEZAR', 'anotter-demo' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":""} -->
<h2 class="wp-block-heading "><?php echo esc_html__( 'Las dudas también se comparten.', 'anotter-demo' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( anotter_demo_url( 'faq' ) ); ?>"><?php echo esc_html__( 'Todas las preguntas', 'anotter-demo' ); ?> <span aria-hidden="true">→</span></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"aot-faq-list","layout":{"type":"default"}} -->
<div class="wp-block-group aot-faq-list">
<!-- wp:pattern {"slug":"anotter-demo/faq-preview"} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:pattern {"slug":"anotter-demo/final-cta"} /-->
