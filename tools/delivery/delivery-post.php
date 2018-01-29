<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/05/17
 * Time: 14:19
 */
//require_once('catalog.php');
require_once( "../im_tools.php" );
require_once( '../pricelist/pricelist.php' );
require_once( '../r-shop_manager.php' );

// print header_text(false);
// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
// print $operation . "<br/>";

// print "opreation = " . $operation . "<br/>";

switch ( $operation ) {
	case "save_legacy":
		print "saving legacy deliveries<br/>";
		$ids_ = $_GET["ids"];
		$ids  = explode( ',', $ids_ );
//		var_dump($ids);
		save_legacy( $ids );
		break;

	case "clear_legacy":
		clear_legacy();
		break;

	case "get_price":
		$name = $_GET["name"];
		$sql  = "SELECT id FROM im_products WHERE post_title = '" . urldecode( $name ) . "'";
		$id   = sql_query_single_scalar( $sql );
		// print $id . "<br/>";
		print get_price( $id );
		break;
}


function save_legacy( $ids ) {
	global $conn;
	$sql    = "UPDATE im_delivery_legacy SET status = 2 WHERE status = 1";
	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		print mysqli_error( $conn ) . " " . $sql;
		die ( 1 );
	}
	$mission_id = sql_query_single_scalar( "SELECT max(id) FROM im_missions WHERE date <= curdate() AND name = \"סבב מרכז\"" );
	foreach ( $ids as $id ) {
		$sql = "INSERT INTO im_delivery_legacy (client_id, date, status, mission_id) " .
		       " VALUES (" . $id . ", CURRENT_TIMESTAMP(), 1, $mission_id)";

		$result = mysqli_query( $conn, $sql );
		if ( ! $result ) {
			print mysqli_error( $conn ) . " " . $sql;
			die ( 1 );
		}
	}
}

function clear_legacy() {
	global $conn;
	$sql    = "UPDATE im_delivery_legacy SET status = 2 WHERE status = 1";
	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		print mysqli_error( $conn ) . " " . $sql;
		die ( 1 );
	}
}