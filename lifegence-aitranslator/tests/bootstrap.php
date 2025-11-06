<?php
/**
 * PHPUnit Bootstrap
 *
 * @package LG_AITranslator
 */

// WordPress test suite directory
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Check if WordPress test suite exists
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "WordPress test suite not found at: $_tests_dir\n";
	echo "Please install WordPress tests or set WP_TESTS_DIR environment variable.\n";
	echo "\nTo install WordPress tests:\n";
	echo "bash bin/install-wp-tests.sh wordpress_test root '' localhost latest\n";
	exit( 1 );
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/lifegence-aitranslator.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';
