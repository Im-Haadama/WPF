<?php
if ( ! isset( $multisite ) ) {
	$multisite = false;
}

require_once( "im_tools.php" );

if ( ! defined( "STORE_DIR" ) ) {
	define( 'STORE_DIR', dirname( dirname( __FILE__ ) ) );
}

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', STORE_DIR . "/tools" );
}

// Check that the caller is allowed.

if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="My Realm"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Text to send if user hits Cancel button';
	exit;
}
	$user =  $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];
if (! check_password($user, $password)){
	die("invalid password");
}

function check_password($user, $password)
{
	// For now hardcoded.
	if ($user != "im-haadama" or $password != "Wer95%pl")
		return false;

	return true;
}

require_once( STORE_DIR . "/wp-load.php" );

apply_filters( 'wp_basic_auth_credentials', [$_SERVER['PHP_AUTH_USER'] => $_SERVER['PHP_AUTH_PW']] );
print get_user_id();

//
//require_once( STORE_DIR . "/wp-includes/pluggable.php" );
//
//require_once( TOOLS_DIR . "/options.php" );
//
//$multisite = false;
//// Check if one of two - right api key (for multisite) - compare with DB, or wp login (manual).
//if ( isset( $_GET["api_key"] ) ) {
////	 print "got key";
//	$key = substr( $_GET["api_key"], 0, 36 );
////	print "Y". $key . " " . strlen($key) . "Y<br/>";
//	$db_key = info_get( "api_key" );
////	print "X".$db_key . " " . strlen($db_key) . "X<br/>";
//	if ( strlen( $key ) > 10 and ( $key == $db_key ) ) {
////		print "right key";
//		$multisite = true;
//	} else {
//		print "wrong key<br/>";
//		die( 1 );
//	}
//}
//
//if ( ! $multisite ) {
//	$user = wp_get_current_user();
//	if ( $user->ID == "0" ) {
//		// Force login
//		$inclued_files = get_included_files();
//		my_log( __FILE__, $inclued_files[ count( $inclued_files ) - 2 ] );
//		$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';
//
//		print '<script language="javascript">';
//		print "window.location.href = '" . $url . "'";
//		print '</script>';
//		print $_SERVER['REMOTE_ADDR'] . "<br/>";
//		var_dump( $user );
//		exit();
//	}
//
//	$roles = $user->roles;
//	if ( count( array_intersect( array( "shop_manager", "administrator" ), $roles ) ) < 1 ) {
//		print "Ask for permissions";
//		die ( 1 );
//		my_log( __FILE__ . " " . $user->name );
//		// No relevant role - send to store.
//		// < 1! (in_array("shop_manager", $roles) or in_array("administrator", $roles))) {
////		print '<script language="javascript">';
////		print "window.location.href = 'http://" . $_SERVER['SERVER_NAME'] . "'";
////		print '</script>';
////		print $_SERVER['REMOTE_ADDR'];
////		exit();
//	}
//}

?>
