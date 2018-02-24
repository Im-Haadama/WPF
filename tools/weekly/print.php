<?php
require_once '../r-shop_manager.php';
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/11/16
 * Time: 23:00
 */

// print all needed for daily functions:
// 1) Orders with colleting sheet
// 2) ride info
// 3) pickup sheets

print header_text( false );
require_once '../delivery/delivery.php';
require_once '../orders/orders-common.php';
require_once '../supplies/supplies.php';
require_once( "../maps/build-path.php" );

if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
} else {
	$operation = "menu";
}

switch ( $operation ) {
	case "menu":
		show_menu();
		break;
	case "mission":
		print_mission( $_GET["mission_id"] );
		break;
	case "supplies":
		print_the_supplies();
		break;
}
exit();

function show_menu() {
	$sql = 'SELECT posts.id as id'
	       . ' FROM `wp_posts` posts'
	       . " WHERE post_status LIKE '%wc-processing%' order by 1";

	$result = sql_query( $sql );

	$missions = array();
	while ( $row = mysqli_fetch_assoc( $result ) ) {
		$id         = $row["id"];
		$mission_id = order_get_mission_id( $id );
		if ( ! in_array( $mission_id, $missions ) ) {
			array_push( $missions, $mission_id );
		}
	}
	foreach ( $missions as $mission ) {
		print gui_hyperlink( get_mission_name( $mission ), "print.php?operation=mission&mission_id=" . $mission );
		print "<br/>";
	}
	print gui_hyperlink( "אספקות", "print.php?operation=supplies" );
}

// print $sql;
function print_mission( $mission_id_filter = null ) {
	$sql = 'SELECT posts.id as id'
	       . ' FROM `wp_posts` posts'
	       . " WHERE post_status LIKE '%wc-processing%' order by 1";

	$result = sql_query( $sql );
	print "<style>";
	print "@media print {";
	print "h1 {page-break-before: always;}";
	print "}";
	print "</style>";

	$orders = array();

	while ( $row = mysqli_fetch_assoc( $result ) ) {
		$id         = $row["id"];
		$mission_id = order_get_mission_id( $id );
		if ( isset( $mission_id_filter ) and $mission_id != $mission_id_filter ) {
			continue;
		}
		array_push( $orders, $id );
//	print $id . "<br/>";
	}
	$path_orders = array();
	find_route_1( 1, $orders, $path_orders );
	foreach ( $path_orders as $id ) {
		print_order_info( $id, true );
		$D = Delivery::CreateFromOrder( $id );
		$D->print_delivery( ImDocumentType::delivery, ImDocumentOperation::collect );
	}

}

function print_the_supplies() {
	global $conn;
	$sql = 'SELECT s.id
 FROM im_supplies s
   JOIN im_suppliers r
   WHERE status < 5
AND s.supplier = r.id AND
  r.print = 1
ORDER BY 1';

// print $sql;
	$result   = $conn->query( $sql );
	$supplies = Array();
	while ( $row = mysqli_fetch_assoc( $result ) ) {
		array_push( $supplies, $row["id"] );
	}
	print_supplies_table( $supplies, true );
}
// require_once( '../delivery/get-driver-multi.php' );