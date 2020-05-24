<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/05/17
 * Time: 14:19
 */
require_once( '../pricelist/pricelist.php' );
require_once( '../r-multisite.php' );
require_once( "../orders/orders-common.php" );

require_once( FRESH_INCLUDES . "/org/business/business.php" );
require_once( FRESH_INCLUDES . "/core/fund.php" );
require_once( "../supplies/Supply.php" );
require_once( FRESH_INCLUDES . '/org/business/business_info.php' );

require_once( FRESH_INCLUDES . "/init.php" );
//require_once('catalog.php');

// print header_text(false);
// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$debug = GetParam( "debug" );

$operation = $_GET["operation"];
switch ( $operation ) {
	case "add_header":
		$order_id    = $_GET["order_id"];
		$total       = $_GET["total"];
		$vat         = $_GET["vat"];
		$lines       = $_GET["lines"];
		$edit        = isset( $_GET["edit"] );
		$fee         = $_GET["fee"];
		$draft       = isset( $_GET["draft"] );
		$delivery_id = null;
		if ( $edit ) {
			$delivery_id = $_GET["delivery_id"];
		}
		$reason = GetParam( "reason" );
		print Fresh_Delivery::CreateDeliveryHeader( $order_id, $total, $vat, $lines, $edit, $fee, $delivery_id, $draft, $reason );
		// create_delivery_header( $order_id, $total, $vat, $lines, $edit, $fee );
		break;

	case "add_lines":
//		print "add lines<br/>";
		$edit        = isset( $_GET["edit"] );
		$lines       = $_GET["lines"];
		$delivery_id = $_GET["delivery_id"];
		$_lines      = explode( ',', $lines );
//		print "del id = " . $delivery_id . " " . sizeof($_lines) . "<br/>";
		add_delivery_lines( $delivery_id, $_lines, $edit );
		break;

	case "get_price":
		if ( isset( $_GET["id"] ) ) {
			$id = $_GET["id"];
			// print "id = " . $id . "<br/>";
		} else {
			$name = $_GET["name"];
			$sql  = "SELECT id FROM im_products WHERE post_title = '" . urldecode( $name ) . "'";
			$id   = SqlQuerySingleScalar( $sql );
		}
		operation_get_price( $id );
		break;

	case "delete_delivery":
		$id = $_GET["delivery_id"];
		if ( ! ( $id > 0 ) ) {
			die ( "send delivery_id" );
		}
		$d = new Fresh_Delivery( $id );
		$d->Delete();

		business_delete_transaction( $id );
		break;

	case "check_delivery":
		$order_id = $_GET["order_id"];
		$id       = SqlQuerySingleScalar( "SELECT id FROM im_delivery WHERE order_id = " . $order_id );
		if ( ! $id ) {
			print "none";
		}
		print $id;
		break;
//		var url = "delivery-post.php?site_id=" + site + "&type=" + type +
//		          "&id=" + id + "&operation=delivered";

}


function operation_get_price( $id ) {
	$q = 1;
	if ( isset( $_GET["quantity"] ) ) {
		$q = $_GET["quantity"];
	}
	$type = isset( $_GET["type"] ) ? $_GET["type"] : null;
//	 print $id . " " . $type . "<br/>";
	print get_price_by_type( $id, $type, $q );

}
function clear_legacy() {
	$sql    = "UPDATE im_delivery_legacy SET status = 2 WHERE status = 1";
	$result = SqlQuery( $sql );
}

