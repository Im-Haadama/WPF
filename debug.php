<?php
echo __FILE__ . "<br/>";
if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/');
}
echo ABSPATH;

// require_once(ABSPATH . 'wp-config.php');

$_GET=array("operation"=>"print_mission", "id"=>962);
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-includes/functions.php');
require_once(ABSPATH . 'wp-content/plugins/flavor/flavor.php');

// require_once(ABSPATH . 'wp-content/plugins/flavor/includes/core/core-functions.php');

$operation = GetParam('operation', true);

$freight = Freight::instance();
$anonymous = (strstr($operation, "anonymous") !== false);
$user = GetParam('AUTH_USER', false, null);
$password = GetParam('AUTH_PW', false, null);

//print "a";
// $operation != 'print_mission' and
if ( ! ($anonymous or ($user and $password and Core_Fund::check_password($user, $password)))){
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