<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/05/17
 * Time: 14:19
 */
//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
require_once( '../pricelist/pricelist.php' );
require_once( '../r-multisite.php' );
require_once( "../orders/orders-common.php" );

require_once( "../business/business.php" );
require_once( ROOT_DIR . "/niver/fund.php" );
require_once( "../supplies/Supply.php" );
require_once( ROOT_DIR . '/tools/business/business_info.php' );
//require_once('catalog.php');

// print header_text(false);
// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$debug = get_param( "debug" );

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
		$reason = get_param( "reason" );
		print delivery::CreateDeliveryHeader( $order_id, $total, $vat, $lines, $edit, $fee, $delivery_id, $draft, $reason );
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
		if ( $debug ) {
			print "start<br/>";
		}
		$site_id = get_param( "site_id" );
		$type    = get_param( "type" );
		$id      = get_param( "id" );
		if ( $site_id != ImMultiSite::LocalSiteID() ) {
			if ( $debug ) {
				print "remote.. ";
			}
			$request = "delivery/delivery-post.php?site_id=" . $site_id .
			           "&type=" . $type . "&id=" . $id . "&operation=delivered";
			if ( $debug ) {
				$request .= "&debug=1";
				print $request;
			}
			print ImMultiSite::sExecute( $request, $site_id, $debug );

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
				$t->Ended();
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

function add_delivery_lines( $delivery_id, $lines, $edit ) {
	print header_text();
	if ( $edit ) {
		$d = new delivery( $delivery_id );
		$d->DeleteLines();
	}

	for ( $pos = 0; $pos < count( $lines ); $pos += 7 ) {
		$prod_id = $lines[ $pos ];
		if ( $prod_id > 0 ) {
			$product_name = get_product_name( $prod_id );
		} else {
			$product_name = $prod_id;
			$prod_id      = 0;
		}
		$quantity         = $lines[ $pos + 1 ];
		$quantity_ordered = $lines[ $pos + 2 ];
		$unit_ordered     = $lines[ $pos + 3 ];
		if ( ! ( strlen( $unit_ordered ) > 0 ) ) {
			$unit_ordered = "NULL";
		} // print $unit_ordered . "<br/>";
		$vat        = $lines[ $pos + 4 ];
		$price      = $lines[ $pos + 5 ];
		$line_price = $lines[ $pos + 6 ];
//        $product_name = get_product_name($prod_id);
//        my_log("product_id = " . $product_id . ", supplier_id=" . $supplier_id . ", product_name=" . $product_name);
		print "<div style=\"direction: ltr;\"> id: " . $prod_id . ", name: " . $product_name . " delivery_id: " . $delivery_id . " quantity: " . $quantity . " quantity_ordred: " . $quantity_ordered .
		      "units: " . $unit_ordered . " vat: " . $vat . " price: " . $price . " line_price: " . $line_price . "</div>";
		delivery::AddDeliveryLine( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id );
	}
}
