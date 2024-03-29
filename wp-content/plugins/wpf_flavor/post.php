<?php
/* Created: Dec 28 2019
*/

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( $_SERVER["SCRIPT_FILENAME"])))). '/');
}

if (isset($_GET["prob"])) {
	require_once(ABSPATH . 'wp-db.php');
	$key = $_GET["operation"];
	$conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	if (isset($_GET["clean_progress"])) {
		$result = mysqli_query( $conn, "update im_info set info_data = '' where info_key = '" . $key . "_progress'");
		return;
	}
	$result = mysqli_query( $conn, "select info_data from im_info where info_key = '" . $key . "_progress'");

	$arr = mysqli_fetch_row( $result );
	print $arr[0];
	return;
}
require_once(ABSPATH . 'wp-config.php');

$operation = GetParam('operation', true);
$flavor = WPF_Flavor::instance();

if (! strstr($operation, "anonymous")) {
	$user           = GetParam( 'AUTH_USER', false, null );
	$password       = GetParam( 'AUTH_PW', false, null );
	$password_check = Core_Fund::check_password( $user, $password );
	$nonce_checked = ( $nonce = GetParam( "nonce", false, null ) ) and wp_verify_nonce( $nonce );
	if ( ! get_user_id() and ! $password_check and ! $nonce_checked ) {
		if ( ! get_user_id( true ) ) {
			die( 'Not connected' );
		}
	}
}

Core_Hook_Handler::instance()->run();
$rc = $flavor->handle_operation($operation);
if ($rc === false) { print "failed"; return; } // Something went wrong. The processing would print something.
if ($rc === true) { return; } // 2020-11-24 removed the approval of success.
print $rc;
