<?php

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

$operation = GetParam('operation', true);
$freight = Freight::instance();
$anonymous = (strstr($operation, "anonymous") !== false);
$user = GetParam('AUTH_USER', false, null);
$password = GetParam('AUTH_PW', false, null);

//print "a";
if (! ($anonymous or ($user and $password and Core_Fund::check_password($user, $password)))){
//	print "b";

	if ( ! get_user_id(true) ) die('Not connected');
}

//	if (! $anonymous and ! get_user_id(true) ) die('Not connected');
//if (! get_user_id() and ! Core_Fund::check_password($user, $password)) {
//
//}

$rc = $freight->handle_operation($operation);

if ($rc === false or null === $rc) { print "failed"; return; }
if ($rc === true) { print "done"; return; }
//if (is_numeric($rc)) { print AJAX_PREFIX . ".$rc"; return; }
// Something went wrong. The procssing would print something.
print $rc;