<?php
if ( ! isset( $multisite ) ) {
	$multisite = false;
}

require_once( "im_tools.php" );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}


require_once( ROOT_DIR . "/wp-load.php" );
require_once( ROOT_DIR . "/wp-includes/pluggable.php" );
require_once(ROOT_DIR . '/init.php');


if ( isset( $_GET["key"] ) ) {
	$key = $_GET["key"];
	if ( $key == "lasdhflajsdhflasjdhflaksj" ) {
		$multisite = true;
	}
}

if ( $multisite or $_SERVER['REMOTE_ADDR'] == "160.153.129.234" or // Aglamaz.com
     $_SERVER['REMOTE_ADDR'] == "192.64.80.133" or // Tabula
     $_SERVER['REMOTE_ADDR'] == "82.80.250.18" or // super-organi: self calling - reading create-delivery-script
     $_SERVER['REMOTE_ADDR'] == "127.0.0.1"
) {
	$multisite = true;
} else {
	$user = wp_get_current_user();
	if ( $user->ID == "0" ) {
		// Force login
		$inclued_files = get_included_files();
		my_log( __FILE__, $inclued_files[ count( $inclued_files ) - 2 ] );
		force_login();
		exit();
	}

	$roles = $user->roles;
	if ( count( array_intersect( array( "shop_manager", "administrator" ), $roles ) ) < 1 ) {
		print greeting();
		print im_translate("No permissions") . "<br/>";

		die ( 1 );
		$inclued_files = get_included_files();
		for ( $i = 0; $i < count( $inclued_files ) - 1; $i ++ ) {
			print  $inclued_files[ $i ] . "<br/>";
		}
		var_dump( $roles );
		die ( 1 );
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
