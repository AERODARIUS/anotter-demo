<?php
/** Explicit local administration: set the existing administrator password from .env. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
$administrator = get_user_by( 'login', getenv( 'WP_ADMIN_USER' ) );
$password      = getenv( 'WP_ADMIN_PASSWORD' );
if ( ! $administrator || ! user_can( $administrator, 'manage_options' ) || strlen( $password ) < 24 ) {
	WP_CLI::error( 'A valid administrator and strong private environment password are required.' );
}
wp_set_password( $password, $administrator->ID );
WP_CLI::success( 'Administrator password rotated from the private environment.' );
