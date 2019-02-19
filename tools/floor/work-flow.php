<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/12/18
 * Time: 08:16
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
// require_once 'orders-common.php';
require_once '../delivery/delivery.php';

print header_text( true, true, true );

// Check if logged in
check_user();

// Greeting
print gui_header( 1, "בוקר טוב" );

// Display the overview. POS manager will open new window to handle.
// When finished will come back to here.

// Display orders waiting to be approved.
$sql = "select count(id) from wp_posts where post_status in ('wc-pending', 'wc-on-hold')";
$c   = sql_query_single_scalar( $sql );
// print orders_table('wc-pending');
if ( $c ) {
	print gui_header( 2, $c . " הזמנות ממתינות לאישור" );
	print gui_hyperlink( "לטיפול", "../orders/orders-get.php" );
}

// Check if there are orders in the mail.

// Handle supplies.
$needed = array();
Order::NeedToOrder( $needed );
//var_dump($needed);
$c = count( $needed );
if ( $c ) {
	print gui_header( 2, $c . " סוגי פריטים ממתינים להזמנה " );
	print gui_hyperlink( "לטיפול", "../orders/get-total-orders.php" );
}

// Pack.
// Accounting.

//print orders_supply_table();
//
//
//function orders_supply_table() {
//	$sql =
//}


function check_user() {
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

}