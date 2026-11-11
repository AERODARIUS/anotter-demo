<?php
/** Defense in depth for CLI fixture mutation, including directly invoked WP-CLI. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
require_once '/opt/anotter/scripts/php/demo-library.php';
try {
	anotter_demo_require_local_tools( true );
} catch ( Throwable $error ) {
	WP_CLI::error( $error->getMessage() );
}
