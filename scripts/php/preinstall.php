<?php
/** Refuse to install over any preexisting database tables. Runs without WordPress. */
$database = new mysqli(
	'db', getenv( 'WORDPRESS_DB_USER' ), getenv( 'WORDPRESS_DB_PASSWORD' ),
	getenv( 'WORDPRESS_DB_NAME' )
);
$tables   = $database->query( 'SHOW TABLES' );
if ( $tables->num_rows > 0 ) {
	WP_CLI::error( 'Database is not empty. Setup will not overwrite a partial or unrelated installation.' );
}
$database->close();
