<?php

ini_set( 'display_errors', 'on' );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/06/18
 * Time: 13:24
 */

require_once( "../im_tools.php" );
require_once( 'orders-common.php' );
require_once( "../delivery/create-delivery-post.php" );
require_once( "../account/account-post.php" );

$order_id                         = isset( $_GET["order_id"] ) ? $_GET["order_id"] : null;
$operation                        = $_GET["operation"];

//$operation = $_GET["operation"];
my_log( "Operation: " . $operation, __FILE__ );
switch ( $operation ) {
	case "pay_cash":
//		$order_id = $_GET["order_id"];
		$cash = 0;
		if ( isset( $_GET["cash"] ) ) {
			$cash = $_GET["cash"];
		}
		$bank = 0;
		if ( isset( $_GET["bank"] ) ) {
			$bank = $_GET["bank"];
		}
		$check = 0;
		if ( isset( $_GET["check"] ) ) {
			$check = $_GET["check"];
		}
		$credit = 0;
		if ( isset( $_GET["credit"] ) ) {
			$credit = $_GET["credit"];
		}
		$change = 0;
		if ( isset( $_GET["change"] ) ) {
			$change = $_GET["change"];
		}
		$user_id = $_GET["user_id"];
		$del_id  = $_GET["del_id"];

		create_receipt( $cash, $bank, $check, $credit, $change, $user_id, date( 'Y-m-d' ), array( $del_id ) );
		break;

	case "create_delivery":
//		$order_id = $_GET["order_id"];

		create_delivery( $order_id );
		break;

	case "close_order":
		$ids = $_GET["ids"];
		order_change_status( explode( ",", $ids ), "wc-completed" );
		break;

}

//function pos_pay($order_id, $cash, $bank, $check, $credit, $change)
//{
//
//	$delivery_id = create_delivery($order_id);
//
//	$u = order_get_customer_id($order_id);
//	print "user: " . $u . "<br/>";
//	create_receipt($cash, $bank, $check, $credit, $change, $u, date('Y-m-d'), array($delivery_id));
//}

function create_delivery( $order_id ) {
	$prods       = array();
	$order       = new WC_Order( $order_id );
	$order_items = $order->get_items();
	$total       = 0;
	$vat         = 0;
	$lines       = 0;
	foreach ( $order_items as $product ) {
		$lines ++;
		$p = $product['price'];
		// push_array($prods, array($product['qty']));
		// $total += $p * $q;
		// var_dump($product);
		$prod                     = array();
		$prod['product_name']     = $product["name"];
		$prod['quantity']         = $product["quantity"];
		$prod['quantity_ordered'] = 0;
		$prod['vat']              = 0;
		$prod['price']            = $product['total'] / $product["quantity"];
		$prod['line_price']       = $product['total'];
		$total                    += $product['total'];
		$prod['prod_id']          = $product['product_id'];

		// var_dump($prod);
		array_push( $prods, $prod );
	}

	$delivery_id = create_delivery_header( $order_id, $total, $vat, $lines, false, 0 );

	print " מספר " . $delivery_id;

	foreach ( $prods as $prod ) {
		add_delivery_line( $prod['product_name'], $delivery_id, $prod['quantity'], $prod['quantity_ordered'], 0,
			$prod['vat'], $prod['price'], $prod['line_price'], $prod['prod_id'] );
	}

	print " נוצרה <br/>";

//	$order = new WC_Order( $order_id );
//	$order->update_status( 'wc-completed' );

	global $track_email;
	$delivery = new delivery( $delivery_id );
	$delivery->send_mail( $track_email, false );

	return $delivery_id;
}