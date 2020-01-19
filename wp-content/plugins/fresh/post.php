<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

//require_once( FRESH_ABSPATH . '/im-config.php' );
//require_once( FRESH_ABSPATH . '/init.php' );
//require_once( FRESH_ABSPATH . '/org/gui.php' );
//require_once( FRESH_ABSPATH . '/routes/gui.php' );

$operation = GetParam('operation', true);
$fresh = Fresh::instance();
if ( ! get_user_id(true) ) die('Not connected');

$rc = $fresh->handle_operation($operation);

if ($rc === false) { print "failed"; return; }
if ($rc === true) { print "done"; return; }
if (is_numeric($rc)) { print "done.$rc"; return; }
// Something went wrong. The procssing would print something.
print "done" . $rc;