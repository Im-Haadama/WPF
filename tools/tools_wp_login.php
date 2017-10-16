<?php
if ( ! isset( $multisite ) ) {
//	print "false";
	$multisite = false;
}

require_once( "im_tools.php" );

if ( ! defined( STORE_DIR ) ) {
	define( 'STORE_DIR', dirname( dirname( __FILE__ ) ) );
}
// print $_SERVER['REMOTE_ADDR'];
require_once( STORE_DIR . "/wp-load.php" );
require_once( STORE_DIR . "/wp-includes/pluggable.php" );

if ( $multisite or $_SERVER['REMOTE_ADDR'] == "160.153.153.166" or // Aglamaz.com
     $_SERVER['REMOTE_ADDR'] == "192.64.80.133" or // Tabula
     $_SERVER['REMOTE_ADDR'] == "82.80.250.18" or // super-organi: self calling - reading create-delivery-script
     $_SERVER['REMOTE_ADDR'] == "82.81.243.189" or // Aglamaz home
     $_SERVER['REMOTE_ADDR'] == "127.0.0.1"
) {
	$multisite = true;
} else {
//	foreach (get_included_files() as $file)
//		print $file . "<br/>";

	$user = wp_get_current_user();

	if ( strlen( $user->user_login ) > 2 ) {
		// print "ברוך הבא " . $user->user_login . "<br/>";
	} else {
		//    print "need to login<br/>";
		//    if (!defined("BASE_PATH")) define('BASE_PATH', isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : substr($_SERVER['PATH_TRANSLATED'],0, -1*strlen($_SERVER['SCRIPT_NAME'])));
		//    print "root: " . __ROOT__ . "<br/>";
		//    print "dir: " . __DIR__ . "<br/>";
		//    print "abspath: " . ABSPATH . "<br/>";
		//    print "docroot: " . $_SERVER["DOCUMENT_ROOT"] .  "<br/>";
		//    print "base_path: " . BASE_PATH  . "<br/>";
		$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';
		// print $url;

//		print '<script language="javascript">';
//		print "window.location.href = '" . $url . "'";
//
//		print '</script>';
		print $_SERVER['REMOTE_ADDR'];
		exit();
	}
}

function get_customer_name( $customer_id ) {
	$user = get_user_by( "id", $customer_id );

	return $user->user_firstname . " " . $user->user_lastname;
}

function get_customer_email( $customer_id ) {
	$user = get_user_by( "id", $customer_id );

	return $user->user_email;
}

function get_customer_phone( $user_id ) {
	return get_meta_field( get_last_order( $user_id ), '_billing_phone' );
}

function get_current_user_name() {
	return get_customer_name( wp_get_current_user()->ID );
}

function get_user_id() {
	$current_user = wp_get_current_user();

	return $current_user->ID;
}

function week_day( $idx ) {
	switch ( $idx ) {
		case 1:
			return "ראשון";
			break;
		case 2:
			return "שני";
			break;
		case 3:
			return "שלישי";
			break;
		case 4:
			return "רביעי";
			break;
		case 5:
			return "חמישי";
			break;
		case 6:
			return "שישי";
			break;
	}
}
?>
