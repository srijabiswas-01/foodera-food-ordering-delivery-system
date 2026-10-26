<?php
/*
 * Database settings for hosting.
 *
 * For InfinityFree/cPanel, replace these values with the MySQL hostname,
 * username, password, and database name shown in your hosting control panel.
 */
define('DB_HOST', getenv('FOODERA_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('FOODERA_DB_USER') ?: 'root');
define('DB_PASS', getenv('FOODERA_DB_PASS') ?: 'root');
define('DB_NAME', getenv('FOODERA_DB_NAME') ?: 'foodera');
define('DB_PORT', (int) (getenv('FOODERA_DB_PORT') ?: 3306));
?>
