<?php

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

$operation = GetParam('operation', true);
$cap = Capabilites::instance();


if ( ! get_user_id(true) ) die('Not connected');
$rc = $cap->handle_operation($operation);

if ($rc === false) { print "failed"; return; } // Something went wrong. The processing would print something.
if ($rc === true) { print "done"; return; }

print "done.$rc";
