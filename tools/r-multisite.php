<?php
if ( ! isset( $multisite ) ) {
	$multisite = false;
}

require_once( "im_tools.php" );

if ( ! defined( "STORE_DIR" ) ) {
	define( 'STORE_DIR', dirname( dirname( __FILE__ ) ) );
}

if ( ! defined( "TOOLS_DIR" ) ) {
	define( TOOLS_DIR, STORE_DIR . "/tools" );
}

require_once( STORE_DIR . "/wp-load.php" );
require_once( STORE_DIR . "/wp-includes/pluggable.php" );

require_once( TOOLS_DIR . "/options.php" );

$multisite = false;
// Check if one of two - right api key (for multisite) - compare with DB, or wp login (manual).
if ( isset( $_GET["api_key"] ) ) {
//	 print "got key";
	$key = substr( $_GET["api_key"], 0, 36 ); // Don't know why and where extra _ was added.
//	print "Y". $key . " " . strlen($key) . "Y<br/>";
	$db_key = info_get( "api_key" );
//	print "X".$db_key . " " . strlen($db_key) . "X<br/>";
	if ( strlen( $key ) > 10 and ( $key == $db_key ) ) {
//		print "right key";
		$multisite = true;
	}
}

if ( ! $multisite ) {
	die( 1 );

	$user = wp_get_current_user();
	if ( $user->ID == "0" ) {
		// Force login
		$inclued_files = get_included_files();
		my_log( __FILE__, $inclued_files[ count( $inclued_files ) - 2 ] );
		$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';

		print '<script language="javascript">';
		print "window.location.href = '" . $url . "'";
		print '</script>';
		print $_SERVER['REMOTE_ADDR'] . "<br/>";
		var_dump( $user );
		exit();
	}

	$roles = $user->roles;
	if ( count( array_intersect( array( "shop_manager", "administrator" ), $roles ) ) < 1 ) {
		my_log( __FILE__ . " " . $user->name );
		// No relevant role - send to store.
		// < 1! (in_array("shop_manager", $roles) or in_array("administrator", $roles))) {
		print '<script language="javascript">';
		print "window.location.href = 'http://" . $_SERVER['SERVER_NAME'] . "'";
		print '</script>';
		print $_SERVER['REMOTE_ADDR'];
		exit();
	}
}

?>
