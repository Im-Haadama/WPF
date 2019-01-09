<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/05/17
 * Time: 14:19
 */
//require_once('catalog.php');
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );
require_once( "../im_tools.php" );
require_once( '../pricelist/pricelist.php' );
require_once( '../r-multisite.php' );
require_once( "../orders/orders-common.php" );
require_once( "../business/business.php" );
require_once( ROOT_DIR . "/agla/fund.php" );
require_once( "../supplies/supplies.php" );

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

	case "check_delivery":
		$order_id = $_GET["order_id"];
		$id       = sql_query_single_scalar( "SELECT id FROM im_delivery WHERE order_id = " . $order_id );
		if ( ! $id ) {
			print "none";
		}
		print $id;
		break;
//		var url = "delivery-post.php?site_id=" + site + "&type=" + type +
//		          "&id=" + id + "&operation=delivered";

	case "delivered":
//		print "start";
		$site_id = get_param( "site_id" );
		$type    = get_param( "type" );
		$id      = get_param( "id" );
		if ( $site_id != ImMultiSite::LocalSiteID() ) {
//			print "remote";
			print ImMultiSite::sExecute( "delivery/delivery-post.php?site_id=" . $site_id .
			                             "&type=" . $type . "&id=" . $id . "&operation=delivered", $site_id );

			return;
		}
		// Running local. Let's do it.
		// print "type=" . $type . "<br/>";
		switch ( $type ) {
			case "orders":
				$o = new Order( $id );
				$r = $o->delivered();
				if ( $r == true ) {
					print "delivered";
				} else {
					print $r;
				}
				break;
			case "tasklist":
				$t = new Tasklist( $id );
				$t->delivered();
				print "delivered";
				break;
			case "supplies":
				$s = new Supply( $id );
				$s->picked();
				print "delivered";
				break;
		}
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