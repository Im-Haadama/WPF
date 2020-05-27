<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/05/17
 * Time: 14:19
 */

require_once ("../../../../wp-config.php");

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
		$edit        = isset( $_GET["edit"] );
		$lines       = $_GET["lines"];
		$delivery_id = $_GET["delivery_id"];
		$_lines      = explode( ',', $lines );
		try {
			$d = new Fresh_Delivery( $delivery_id );
		} catch (Exception $e)
		{
			print $e->getMessage();
			return false;
		}
		$d->add_delivery_lines( $delivery_id, $_lines, $edit );
		if (! $edit) {
			$d = new Fresh_Delivery($delivery_id);
			$admin_email = get_bloginfo('admin_email');
			if (defined('ADMIN_MAIL')) $admin_email = ADMIN_MAIL;

			$d->send_mail($admin_email);
		}
		break;

	case "get_price_vat":
		if ( isset( $_GET["id"] ) ) $id = $_GET["id"];
		else {
			$name = $_GET["name"];
			$sql  = "SELECT id FROM wp_posts WHERE post_title = '" . urldecode( $name ) . "' and post_status = 'publish'";
			$id   = SqlQuerySingleScalar( $sql );
		}
		$p = new Fresh_Product( $id );
		$user = GetParam("user_id", false, null);
		if ($user)
		{
			$u = new Fresh_Client($user);
			$customer_type = $u->customer_type();
		}
		$price = Fresh_Pricing::get_price_by_type($id, $customer_type);

		print "$price," . $p->getVatPercent();
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

function clear_legacy() {
	$sql    = "UPDATE im_delivery_legacy SET status = 2 WHERE status = 1";
	$result = SqlQuery( $sql );
}

