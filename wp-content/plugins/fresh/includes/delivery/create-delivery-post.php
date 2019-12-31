<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/12/16
 * Time: 18:12
 */

require_once( '../r-shop_manager.php' );
require_once( '../r-shop_manager.php' );
require_once( '../orders/orders-common.php' );
require_once( '../account/account.php' );
require_once( FRESH_INCLUDES . '/org/business/business-post.php' );
require_once( '../delivery/delivery.php' );

// print header_text();
if ( isset( $_GET["operation"] ) ) {
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
		print Fresh_Delivery::CreateDeliveryHeader( $order_id, $total, $vat, $lines, $edit, $fee, $delivery_id, $draft );
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
}
}

function add_delivery_lines( $delivery_id, $lines, $edit ) {
	print header_text();
	if ( $edit ) {
		$d = new Fresh_Delivery( $delivery_id );
		$d->DeleteLines();
	}

	for ( $pos = 0; $pos < count( $lines ); $pos += 8 ) {
		$prod_id          = $lines[ $pos ];
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
		$part_of_basket = $lines[$pos + 7];

//        $product_name = get_product_name($prod_id);
//        my_log("product_id = " . $product_id . ", supplier_id=" . $supplier_id . ", product_name=" . $product_name);
		print "<div style=\"direction: ltr;\"> id: " . $prod_id . ", name: " . $product_name . " delivery_id: " . $delivery_id . " quantity: " . $quantity . " quantity_ordred: " . $quantity_ordered .
		      "units: " . $unit_ordered . " vat: " . $vat . " price: " . $price . " line_price: " . $line_price . "</div>";
		Fresh_Delivery::AddDeliveryLine( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id, $part_of_basket );
	}
}


?>

