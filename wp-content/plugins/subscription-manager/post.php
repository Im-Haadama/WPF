<?php

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(ABSPATH . 'wp-config.php');


$operation = GetParam('operation', true, null, true);
$subscription_manager = Subscription_Manager::instance();
$user = GetParam('AUTH_USER', false, null);
$password = GetParam('AUTH_PW', false, null);
// show_errors();
$password_check = Core_Fund::check_password($user, $password);
if (! get_user_id() and ! $password_check) {
	if ( ! get_user_id(true) ) die('Not connected');
}

$rc = $subscription_manager->handle_operation($operation);

if ($rc === false) { print "failed"; return; } // Something went wrong. The processing would print something.
if ($rc === true) { print "done"; return; }
print "done.$rc";
