<?php
/* Created: Dec 28 2019
*/


if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

$operation = GetParam('operation', true);
$flavor = Flavor::instance();

$user = GetParam('AUTH_USER', false, null);
$password = GetParam('AUTH_PW', false, null);
$password_check = Core_Fund::check_password($user, $password);
if (! get_user_id() and ! $password_check) {
	if ( ! get_user_id(true) ) die('Not connected');
}

$rc = $flavor->handle_operation($operation);
if ($rc === false) { print "failed"; return; } // Something went wrong. The processing would print something.
if ($rc === true) { print "done"; return; }
print $rc;
