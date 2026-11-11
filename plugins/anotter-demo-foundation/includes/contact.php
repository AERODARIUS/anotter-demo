<?php
/** Public demo form: validate only, never send or retain messages. @package AnotterDemoFoundation */
defined( 'ABSPATH' ) || exit;

/**
 * Validate bounded public input. No privileged operation is exposed.
 *
 * @param array $input Unslashed input.
 * @return array Field errors.
 */
function anotter_demo_contact_errors( $input ) {
	$errors = array();
	$limits = array( 'name' => 100, 'email' => 254, 'subject' => 150, 'message' => 3000 );
	$labels = array( 'name' => __( 'Nombre', 'anotter-demo-foundation' ), 'email' => __( 'Correo electrónico', 'anotter-demo-foundation' ), 'subject' => __( 'Asunto', 'anotter-demo-foundation' ), 'message' => __( 'Mensaje', 'anotter-demo-foundation' ) );
	foreach ( $limits as $field => $limit ) {
		if ( ! isset( $input[ $field ] ) || ! is_string( $input[ $field ] ) || '' === trim( $input[ $field ] ) || mb_strlen( trim( $input[ $field ] ) ) > $limit ) {
			$errors[ $field ] = sprintf( __( '%1$s: completá el campo con un máximo de %2$d caracteres.', 'anotter-demo-foundation' ), $labels[ $field ], $limit );
		}
	}
	if ( empty( $errors['email'] ) && ! is_email( trim( $input['email'] ) ) ) {
		$errors['email'] = __( 'Correo electrónico: ingresá una dirección válida.', 'anotter-demo-foundation' );
	}
	return $errors;
}

/** Render an accessible, server-validated demonstration form. */
function anotter_demo_contact_form() {
	$values = array_fill_keys( array( 'name', 'email', 'subject', 'message' ), '' );
	$errors = array();
	$notice = '';
	$valid  = false;
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['anotter_contact_submit'] ) ) {
		$input = wp_unslash( $_POST );
		// Prefix POST fields: WordPress treats an unprefixed "name" as a public query variable.
		foreach ( array_keys( $values ) as $field ) {
			$input[ $field ] = $input[ 'anotter_' . $field ] ?? '';
		}
		$nonce = isset( $input['anotter_contact_nonce'] ) && is_string( $input['anotter_contact_nonce'] ) ? $input['anotter_contact_nonce'] : '';
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key   = 'aot_contact_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
		$count = (int) get_transient( $key );
		$display_limits = array( 'name' => 100, 'email' => 254, 'subject' => 150, 'message' => 3000 );
		foreach ( $values as $field => &$value ) {
			$raw = isset( $input[ $field ] ) && is_string( $input[ $field ] ) ? $input[ $field ] : '';
			// Validate original input below; bound sanitization and reflected markup even on failure.
			$raw = mb_substr( $raw, 0, $display_limits[ $field ] );
			$value = 'message' === $field ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		}
		unset( $value );
		if ( ! wp_verify_nonce( $nonce, 'anotter_demo_contact' ) ) {
			$notice = __( 'La sesión del formulario venció. Recargá la página e intentá de nuevo.', 'anotter-demo-foundation' );
		} elseif ( $count >= 8 || ! empty( $input['website'] ) ) {
			$notice = __( 'No pudimos validar esta solicitud. Esperá un minuto antes de volver a intentar.', 'anotter-demo-foundation' );
		} else {
			set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
			$errors = anotter_demo_contact_errors( $input );
			$valid  = empty( $errors );
			$notice = $valid
				? __( 'Validación de prueba completada. Tu mensaje no se envió ni se guardó.', 'anotter-demo-foundation' )
				: __( 'Revisá los campos indicados para completar la prueba.', 'anotter-demo-foundation' );
		}
	}
	ob_start();
	?>
	<div class="aot-contact-form">
		<p class="aot-form-disclosure" id="aot-form-disclosure"><?php esc_html_e( 'Formulario de demostración: solo valida datos. No envía correos ni guarda mensajes. Usá datos ficticios.', 'anotter-demo-foundation' ); ?></p>
		<?php if ( $notice ) : ?>
			<div id="aot-form-feedback" class="aot-feedback <?php echo $valid ? 'is-success' : 'is-error'; ?>" role="<?php echo $valid ? 'status' : 'alert'; ?>" tabindex="-1">
				<p><?php echo esc_html( $notice ); ?></p>
				<?php if ( $errors ) : ?>
					<ul><?php foreach ( $errors as $field => $error ) : ?><li><a href="#aot-contact-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $error ); ?></a></li><?php endforeach; ?></ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( get_permalink() ); ?>#aot-form-feedback" aria-describedby="aot-form-disclosure">
			<?php wp_nonce_field( 'anotter_demo_contact', 'anotter_contact_nonce' ); ?>
			<input type="hidden" name="anotter_contact_submit" value="1" />
			<div class="aot-honeypot" aria-hidden="true"><label for="aot-website">Website</label><input id="aot-website" name="website" tabindex="-1" autocomplete="off" /></div>
			<div class="aot-form-grid">
			<?php
			$fields = array(
				'name'    => array( __( 'Nombre', 'anotter-demo-foundation' ), 'text', 100, 'name' ),
				'email'   => array( __( 'Correo electrónico', 'anotter-demo-foundation' ), 'email', 254, 'email' ),
				'subject' => array( __( 'Asunto', 'anotter-demo-foundation' ), 'text', 150, 'off' ),
				'message' => array( __( 'Mensaje', 'anotter-demo-foundation' ), 'textarea', 3000, 'off' ),
			);
			foreach ( $fields as $field => $spec ) :
				?>
				<div class="aot-field aot-field-<?php echo esc_attr( $field ); ?>">
					<label for="aot-contact-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $spec[0] ); ?> <span aria-hidden="true">*</span></label>
					<?php if ( 'textarea' === $spec[1] ) : ?>
						<textarea id="aot-contact-<?php echo esc_attr( $field ); ?>" name="anotter_<?php echo esc_attr( $field ); ?>" rows="6" maxlength="3000" required aria-invalid="<?php echo isset( $errors[ $field ] ) ? 'true' : 'false'; ?>" <?php if ( isset( $errors[ $field ] ) ) : ?>aria-describedby="aot-error-<?php echo esc_attr( $field ); ?>"<?php endif; ?>><?php echo esc_textarea( $values[ $field ] ); ?></textarea>
					<?php else : ?>
						<input id="aot-contact-<?php echo esc_attr( $field ); ?>" name="anotter_<?php echo esc_attr( $field ); ?>" type="<?php echo esc_attr( $spec[1] ); ?>" autocomplete="<?php echo esc_attr( $spec[3] ); ?>" maxlength="<?php echo esc_attr( $spec[2] ); ?>" value="<?php echo esc_attr( $values[ $field ] ); ?>" required aria-invalid="<?php echo isset( $errors[ $field ] ) ? 'true' : 'false'; ?>" <?php if ( isset( $errors[ $field ] ) ) : ?>aria-describedby="aot-error-<?php echo esc_attr( $field ); ?>"<?php endif; ?> />
					<?php endif; ?>
					<?php if ( isset( $errors[ $field ] ) ) : ?><p class="aot-field-error" id="aot-error-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $errors[ $field ] ); ?></p><?php endif; ?>
				</div>
			<?php endforeach; ?>
			</div>
			<p class="aot-small"><?php esc_html_e( 'Todos los campos son obligatorios. Esta demo no tiene un canal de atención comercial activo.', 'anotter-demo-foundation' ); ?></p>
			<button class="wp-element-button" type="submit"><?php esc_html_e( 'Probar formulario', 'anotter-demo-foundation' ); ?> <span aria-hidden="true">→</span></button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'anotter_demo_contact', 'anotter_demo_contact_form' );
