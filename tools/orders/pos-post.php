<?php

//ini_set( 'display_errors', 'on' );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/06/18
 * Time: 13:24
 */

require_once( "../im_tools.php" );
require_once( 'orders-common.php' );
// require_once( "../delivery/create-delivery-post.php" );
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

		delivery::CreateDeliveryFromOrder( $order_id, 1 );
		break;

	case "close_order":
		$ids = get_param_array("ids");
		if ($ids){
			foreach ($ids as $id){
				$o = new Order($id);
				$o->ChangeStatus("wc-completed");
			}
			return;
		}
		print "No ids!<br>";

		// order_change_status( explode( ",", $ids ), "wc-completed" );
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

