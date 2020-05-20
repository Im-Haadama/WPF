<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/05/17
 * Time: 14:19
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
//		print "add lines<br/>";
		$edit        = isset( $_GET["edit"] );
		$lines       = $_GET["lines"];
		$delivery_id = $_GET["delivery_id"];
		$_lines      = explode( ',', $lines );
//		print "del id = " . $delivery_id . " " . sizeof($_lines) . "<br/>";
		add_delivery_lines( $delivery_id, $_lines, $edit );
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

function add_delivery_lines( $delivery_id, $lines, $edit ) {
	if ( $edit ) {
		$d = new Fresh_Delivery( $delivery_id );
		$d->DeleteLines();
	}

	for ( $pos = 0; $pos < count( $lines ); $pos += 8 ) {
		$prod_id = $lines[ $pos ];

		$p = new Fresh_Product($prod_id);
//		print "<br/>" . $prod_id;
		if ($prod_id == -1)
			$product_name = "הנחת סל";
		else
			if ( is_numeric( $prod_id ) ) {
	//			print "int";
				$product_name = $p->getName();
			} else {
	//			print "str";
				if ( strstr( $prod_id, ")" ) ) {
					$prod_id      = substr( $prod_id, 0, strstr( $prod_id, ")" ) );
					$product_name = substr( $prod_id, strstr( $prod_id, ")" ) );
				} else {
					$product_name = $prod_id;
					$prod_id      = 0;
				}
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
		$part_of_basket = $lines[$pos + 7];
//        $product_name = get_product_name($prod_id);
//        my_log("product_id = " . $product_id . ", supplier_id=" . $supplier_id . ", product_name=" . $product_name);
		print "<div style=\"direction: ltr;\"> id: " . $prod_id . ", name: " . $product_name . " delivery_id: " . $delivery_id . " quantity: " . $quantity . " quantity_ordred: " . $quantity_ordered .
		      "units: " . $unit_ordered . " vat: " . $vat . " price: " . $price . " line_price: " . $line_price . "</div>";
		Fresh_Delivery::AddDeliveryLine( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id, $part_of_basket );
	}
}
