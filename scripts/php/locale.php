<?php
/** Select a published locale rather than assuming es_UY exists. */
require_once ABSPATH . 'wp-admin/includes/translation-install.php';
$translations = wp_get_available_translations();
foreach ( array( getenv( 'WP_LOCALE' ), getenv( 'WP_FALLBACK_LOCALE' ) ) as $locale ) {
	if ( isset( $translations[ $locale ] ) ) {
		echo $locale; // CLI output, not HTML.
		return;
	}
}
WP_CLI::error( 'Neither requested Spanish locale is available. Check WordPress.org connectivity and locale settings.' );
