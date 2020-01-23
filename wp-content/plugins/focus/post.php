<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

$operation = GetParam('operation', true);
$focus = Focus::instance();

if ( ! get_user_id(true) ) die('Not connected');

$rc = $focus->handle_operation($operation);
//print "rc=$rc";
if ($rc === false) { print "failed"; return; } // Something went wrong. The processing would print something.
if ($rc === true) { print "done"; return; }
print "done.$rc";
