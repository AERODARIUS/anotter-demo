<?php
/** Explicit Quick Tunnel origin. Loaded by project wp-config before WordPress. */
$anotter_public = rtrim( getenv( 'ANOTTER_PUBLIC_URL' ) ?: '', '/' );
$anotter_local = rtrim( getenv( 'ANOTTER_LOCAL_URL' ) ?: 'http://localhost:8080', '/' );
if ( $anotter_public && ! preg_match( '~^https://[a-z0-9]+(?:-[a-z0-9]+)*\.trycloudflare\.com$~D', $anotter_public ) ) {
	throw new RuntimeException( 'Invalid explicitly configured Quick Tunnel origin.' );
}
$anotter_host = strtolower( $_SERVER['HTTP_HOST'] ?? '' );
$anotter_public_host = $anotter_public ? parse_url( $anotter_public, PHP_URL_HOST ) : '';
if ( $anotter_public_host && $anotter_public_host === $anotter_host ) {
	// Port 8080 is loopback-only; cloudflared preserves this exact configured Host.
	// Forwarded-Host/Proto are deliberately ignored, including comma-separated values.
	define( 'WP_HOME', $anotter_public );
	define( 'WP_SITEURL', $anotter_public );
	define( 'ANOTTER_PUBLIC_REQUEST', true );
	$_SERVER['HTTPS'] = 'on';
	$_SERVER['SERVER_PORT'] = '443';
} else {
	define( 'ANOTTER_PUBLIC_REQUEST', false );
	// Refuse unknown web hosts instead of constructing URLs from request headers.
	$anotter_local_host = parse_url( $anotter_local, PHP_URL_HOST );
	$anotter_local_port = parse_url( $anotter_local, PHP_URL_PORT );
	$anotter_allowed_hosts = array( $anotter_local_host . ( $anotter_local_port ? ':' . $anotter_local_port : '' ), '127.0.0.1', 'localhost' );
	if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) && ! in_array( $anotter_host, $anotter_allowed_hosts, true ) ) {
		http_response_code( 421 );
		exit( 'Unconfigured demo hostname.' );
	}
}
unset( $anotter_public, $anotter_local, $anotter_host, $anotter_public_host, $anotter_local_host, $anotter_local_port, $anotter_allowed_hosts );
