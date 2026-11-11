<?php
/** Live WordPress security/validation branches; no service or payment mocks. */
require_once __DIR__ . '/fixture-guard.php';
$checks = 0;
$assert = function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$checks;
};
$original_user = get_current_user_id();
$owned_user = 0;
$original_post = $_POST;
$original_server = $_SERVER;
$original_mail = get_option( 'anotter_demo_mail_capture', null );
$key = 'aot_contact_' . substr( hash_hmac( 'sha256', '192.0.2.5', wp_salt( 'nonce' ) ), 0, 32 );
try {
	$input = array( 'name' => 'Persona ficticia', 'email' => 'security@example.invalid', 'subject' => 'Prueba', 'message' => 'Mensaje sintético' );
	$assert( array() === anotter_demo_contact_errors( $input ), 'Valid synthetic input' );
	foreach ( array( 'name' => 100, 'email' => 254, 'subject' => 150, 'message' => 3000 ) as $field => $limit ) {
		foreach ( array( '', array( 'malformed' ), str_repeat( 'á', $limit + 1 ) ) as $value ) {
			$bad = $input;
			$bad[ $field ] = $value;
			$assert( isset( anotter_demo_contact_errors( $bad )[ $field ] ), 'Reject missing/array/overlong field: ' . $field );
		}
		if ( 'email' !== $field ) {
			$bounded = $input;
			$bounded[ $field ] = str_repeat( 'á', $limit );
			$assert( ! isset( anotter_demo_contact_errors( $bounded )[ $field ] ), 'Accept exact Unicode boundary: ' . $field );
		}
	}
	$bad = $input;
	$bad['email'] = 'broken@';
	$assert( isset( anotter_demo_contact_errors( $bad )['email'] ), 'Invalid email rejected' );
	wp_set_current_user( 0 );
	$assert( ! current_user_can( 'manage_woocommerce' ), 'Guest lacks store management' );
	ob_start();
	anotter_demo_mail_screen();
	$assert( '' === ob_get_clean(), 'Guest cannot render mail previews' );
	$login = 'm05-security-' . strtolower( wp_generate_password( 12, false ) );
	$created = wp_insert_user( array( 'user_login' => $login, 'user_pass' => wp_generate_password( 40, true, true ), 'user_email' => $login . '@example.invalid', 'role' => 'shop_manager' ) );
	$assert( ! is_wp_error( $created ), 'Isolated Shop Manager created' );
	$owned_user = (int) $created;
	update_user_meta( $owned_user, '_anotter_m05_security', $login );
	wp_set_current_user( $owned_user );
	$assert( current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ), 'Shop Manager is not an administrator' );
	ob_start();
	anotter_demo_mail_screen();
	$assert( '' === ob_get_clean(), 'Shop Manager cannot read global mail/authentication previews' );
	$admin = get_user_by( 'login', getenv( 'WP_ADMIN_USER' ) );
	if ( ! $admin ) {
		throw new RuntimeException( 'Configured isolated demo administrator is required.' );
	}
	wp_set_current_user( $admin->ID );
	ob_start();
	anotter_demo_mail_screen();
	$assert( str_contains( ob_get_clean(), 'Correos DEMO — captura local' ), 'Administrator retains protected mail preview access' );
	wp_set_current_user( 0 );
	$customer = get_role( 'customer' );
	$assert( ! $customer->has_cap( 'manage_woocommerce' ) && ! $customer->has_cap( 'edit_shop_orders' ) && ! $customer->has_cap( 'upload_files' ), 'Customer role cannot manage orders/mail/uploads' );
	$assert( ! wp_check_filetype( 'payload.php', get_allowed_mime_types() )['ext'], 'PHP upload not allowed' );
	$assert( ! wp_verify_nonce( 'invalid', 'anotter_demo_contact' ), 'Invalid form nonce rejected' );
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_SERVER['REMOTE_ADDR'] = '192.0.2.5';
	delete_transient( $key );
	$_POST = array( 'anotter_contact_submit' => '1', 'anotter_contact_nonce' => wp_create_nonce( 'anotter_demo_contact' ), 'anotter_name' => '"><script>alert(1)</script>', 'anotter_email' => 'bad', 'anotter_subject' => str_repeat( 'x', 20000 ), 'anotter_message' => array( 'malformed' ) );
	$html = anotter_demo_contact_form();
	$assert( ! str_contains( $html, '<script>alert(1)</script>' ), 'Contact output escapes hostile HTML' );
	$assert( strlen( $html ) < 15000, 'Rejected overlong input must not inflate response markup' );
	$assert( str_contains( $html, 'role="alert"' ) && str_contains( $html, 'aria-invalid="true"' ), 'Server validation errors have accessible semantics' );
	set_transient( $key, 8, MINUTE_IN_SECONDS );
	$assert( str_contains( anotter_demo_contact_form(), 'No pudimos validar' ), 'Rate-limit branch' );
	delete_transient( $key );
	$_POST['anotter_contact_nonce'] = 'invalid';
	$assert( str_contains( anotter_demo_contact_form(), 'La sesión del formulario venció' ), 'Expired form branch' );
	$before = get_option( 'anotter_demo_mail_capture', array() );
	wp_mail( 'never-send@example.com', 'Not captured', 'Synthetic privacy test' );
	$assert( $before === get_option( 'anotter_demo_mail_capture', array() ), 'Unreserved recipient body never captured' );
	for ( $i = 0; $i < 12; ++$i ) {
		wp_mail( 'security@example.invalid', 'Synthetic preview', str_repeat( 'a', 100010 ) );
	}
	$captured = get_option( 'anotter_demo_mail_capture', array() );
	$assert( 10 === count( $captured ) && 100000 === strlen( $captured[0]['body'] ), 'Local capture count/body bounds' );
	WP_CLI::success( $checks . ' live custom security/validation assertions passed.' );
} finally {
	$_POST = $original_post;
	$_SERVER = $original_server;
	wp_set_current_user( $original_user );
	if ( $owned_user && get_user_meta( $owned_user, '_anotter_m05_security', true ) === $login ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $owned_user );
	}
	delete_transient( $key );
	if ( null === $original_mail ) {
		delete_option( 'anotter_demo_mail_capture' );
	} else {
		update_option( 'anotter_demo_mail_capture', $original_mail, false );
	}
}
