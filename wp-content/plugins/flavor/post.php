<?php
/* Created: Dec 28 2019
*/

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

$operation = get_param('operation', true);
$flavor = Flavor::instance();

if ( ! get_user_id(true) ) die('Not connected');

$rc = $flavor->handle_operation($operation);
//print "rc=$rc";
if ($rc === true) { print "done"; return; }
if (is_numeric($rc)) { print "done.$rc"; return; }
// Something went wrong. The procssing would print something.

print "failed";