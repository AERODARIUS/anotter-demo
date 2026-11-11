<?php
/** Refuse to reconfigure unrelated installs or silently change canonical URLs. */
if ( 'm01' !== get_option( 'anotter_demo_environment' ) ) {
	WP_CLI::error( 'This is not an M01-managed demo. Use a new Compose project and empty volumes.' );
}
if ( untrailingslashit( getenv( 'SITE_URL' ) ) !== untrailingslashit( get_option( 'home' ) ) ) {
	WP_CLI::error( 'SITE_URL differs from the installed URL. Follow the URL migration steps in README.md.' );
}
