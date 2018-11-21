<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/05/17
 * Time: 14:19
 */
//require_once('catalog.php');
// error_reporting( E_ALL );
// ini_set( 'display_errors', 'on' );
require_once( "../im_tools.php" );
require_once( '../pricelist/pricelist.php' );
require_once( '../r-shop_manager.php' );
require_once( "../orders/orders-common.php" );
require_once( "../business/business.php" );

// print header_text(false);
// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];

// print "operation = " . $operation . "<br/>";

switch ( $operation ) {
	case "get_price_vat":
		if ( isset( $_GET["id"] ) ) {
			$id = $_GET["id"];
			// print "id = " . $id . "<br/>";
		} else {
			$name = $_GET["name"];
			$sql  = "SELECT id FROM im_products WHERE post_title = '" . urldecode( $name ) . "'";
			$id   = sql_query_single_scalar( $sql );
			// print "id: " . $id;
		}
		$p = new Product( $id );
		operation_get_price( $id );
		print ',';
		print $p->GetVatPercent();
		break;
	case "get_price":
		if ( isset( $_GET["id"] ) ) {
			$id = $_GET["id"];
			// print "id = " . $id . "<br/>";
		} else {
			$name = $_GET["name"];
			$sql  = "SELECT id FROM im_products WHERE post_title = '" . urldecode( $name ) . "'";
			$id   = sql_query_single_scalar( $sql );
		}
		operation_get_price( $id );
		break;

	case "delete_delivery":
		$id = $_GET["delivery_id"];
		if ( ! ( $id > 0 ) ) {
			die ( "send delivery_id" );
		}
		$d = new delivery( $id );
		$d->Delete();

		business_delete_transaction( $id );
		break;

}


function operation_get_price( $id ) {
	$q = 1;
	if ( isset( $_GET["quantity"] ) ) {
		$q = $_GET["quantity"];
	}
	$type = isset( $_GET["type"] ) ? $_GET["type"] : null;
	// print $id . "<br/>";
	print get_price_by_type( $id, $type, $q );

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