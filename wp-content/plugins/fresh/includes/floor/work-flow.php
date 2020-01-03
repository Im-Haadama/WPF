<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/12/18
 * Time: 08:16
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

// require_once 'orders-common.php';
require_once '../delivery/delivery.php';

print header_text( true, true, true );

// Check if logged in
check_user();

// Greeting
print Core_Html::gui_header( 1, "בוקר טוב" );

// Display the overview. POS manager will open new window to handle.
// When finished will come back to here.

// Display orders waiting to be approved.
$sql = "select count(id) from wp_posts where post_status in ('wc-pending', 'wc-on-hold')";
$c   = sql_query_single_scalar( $sql );
// print orders_table('wc-pending');
if ( $c ) {
	print Core_Html::gui_header( 2, $c . " הזמנות ממתינות לאישור" );
	print Core_Html::GuiHyperlink( "לטיפול", "../orders/orders-get.php" );
}

// Check if there are orders in the mail.

// Handle supplies.
$needed = array();
Order::NeedToOrder( $needed );
//var_dump($needed);
$c = count( $needed );
if ( $c ) {
	print Core_Html::gui_header( 2, $c . " סוגי פריטים ממתינים להזמנה " );
	print Core_Html::GuiHyperlink( "לטיפול", "../orders/get-total-orders.php" );
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
		force_login();
		exit();
	}

}