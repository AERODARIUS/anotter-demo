<?php
/** Bounded local-only email preview. All outbound WordPress mail remains suppressed. */
defined( 'ABSPATH' ) || exit;

add_filter( 'pre_wp_mail', function ( $return, $attributes ) {
	if ( in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
		$recipients = is_array( $attributes['to'] ) ? $attributes['to'] : explode( ',', $attributes['to'] );
		$safe = ! empty( $recipients );
		foreach ( $recipients as $recipient ) {
			// Only reserved .invalid recipients; never retain arbitrary customer mail.
			$safe = $safe && (bool) preg_match( '/@[^<>\s]+\.invalid\s*>?$/i', trim( $recipient ) );
		}
		if ( $safe ) {
			$messages = get_option( 'anotter_demo_mail_capture', array() );
			array_unshift( $messages, array(
				'time' => gmdate( 'c' ), 'to' => implode( ', ', $recipients ),
				'subject' => mb_substr( $attributes['subject'], 0, 300 ),
				'body' => mb_substr( $attributes['message'], 0, 100000 ),
			) );
			update_option( 'anotter_demo_mail_capture', array_slice( $messages, 0, 10 ), false );
		}
	}
	return true;
}, 10, 2 );

add_action( 'admin_menu', function () {
	// The global capture can include core account-recovery mail. Store management
	// alone must not grant access to administrator authentication messages.
	add_submenu_page( 'woocommerce', 'Correos DEMO', 'Correos DEMO', 'manage_options', 'anotter-demo-mail', 'anotter_demo_mail_screen' );
} );

/** Render global previews only for administrators; no public endpoint or real delivery. */
function anotter_demo_mail_screen() {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['anotter_clear_mail'] ) ) {
		check_admin_referer( 'anotter_clear_mail' );
		delete_option( 'anotter_demo_mail_capture' );
	}
	echo '<div class="wrap"><h1>Correos DEMO — captura local</h1><p>Sin envío externo. Solo destinatarios .invalid, máximo 10 mensajes. No es una confirmación de entrega.</p><form method="post">';
	wp_nonce_field( 'anotter_clear_mail' );
	echo '<button class="button" name="anotter_clear_mail" value="1">Vaciar captura local</button></form>';
	foreach ( get_option( 'anotter_demo_mail_capture', array() ) as $message ) {
		echo '<h2>' . esc_html( $message['subject'] ) . '</h2><p>' . esc_html( $message['time'] . ' · ' . $message['to'] ) . '</p>';
		$csp = '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src \'self\' data:">';
		echo '<iframe title="Vista previa de correo DEMO" sandbox="" style="width:100%;height:650px;border:1px solid #70847e;background:white" srcdoc="' . esc_attr( $csp . $message['body'] ) . '"></iframe>';
	}
	echo '</div>';
}
