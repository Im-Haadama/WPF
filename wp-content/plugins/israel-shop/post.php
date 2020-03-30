<?php

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

$operation = GetParam('operation', true);
$israel_zones = Israel_Zones::instance();
$anonymous = (strstr($operation, "anonymous") !== false);
if (! $anonymous and ! get_user_id(true) ) die('Not connected');
$rc = $israel_zones->handle_operation($operation);

if ($rc === false) { print "failed"; return; }
if ($rc === true) { print "done"; return; }
if (is_numeric($rc)) { print "done.$rc"; return; }
// Something went wrong. The procssing would print something.
print "done" . $rc;