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
require_once( '../business/business-post.php' );
require_once( '../delivery/delivery.php' );

// print header_text();
$operation = $_GET["operation"];
switch ( $operation ) {
	case "add_header":
		$order_id = $_GET["order_id"];
		$total    = $_GET["total"];
		$vat      = $_GET["vat"];
		$lines    = $_GET["lines"];
		$edit     = isset( $_GET["edit"] );
		$fee      = $_GET["fee"];
		$date     = $_GET["date"];
		create_delivery_header( $order_id, $total, $vat, $lines, $edit, $fee );
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

function create_delivery_header( $order_id, $total, $vat, $lines, $edit, $fee ) {
	global $conn;
/// Usage: http://store.im-haadama.co.il/tools/delivery/db-add-delivery.php?client_id=1&order_id=1794&total=100&vat=18

// If delivery edited delete old delivery
	if ( $edit ) {
		$sql = "UPDATE im_delivery SET vat = " . $vat . ", " .
		       " total = " . $total . ", " .
		       " dlines = " . $lines . ", " .
		       " fee = " . $fee .
		       " WHERE order_id = " . $order_id;
//        $id = get_delivery_id($order_id);
//        $d = new delivery($id);
//        $d->Delete();
	} else {
		$sql = "INSERT INTO im_delivery (date, order_id, vat, total, dlines, fee) "
		       . "VALUES ( CURRENT_TIMESTAMP, "
		       . $order_id . ", "
		       . $vat . ', '
		       . $total . ', ' . $lines . ', ' . $fee . ')';
	}
	my_log( $sql );

	sql_query( $sql );

	if ( $edit ) {
		$delivery_id = $_GET["delivery_id"];
	} else {
		$delivery_id = mysqli_insert_id( $conn );
	}

	if ( ! ( $delivery_id > 0 ) ) {
		die ( "Error!" );
	}
	$client_id = order_get_customer_id( $order_id );

	if ( $edit ) {
		account_update_transaction( $total, $delivery_id );
		business_update_transaction( $delivery_id, $total, $fee );
	} else { // New!
		$date = date( "Y-m-d" );

		account_add_transaction( $client_id, $date, $total, $delivery_id, "משלוח" );
		business_add_transaction( $client_id, $date, $total, $fee, $delivery_id, 3 );
	}
	$order = new WC_Order( $order_id );
	$order->update_status( 'wc-awaiting-shipment' );
//$sql = "update wp_posts set post_status = 'wc-completed' where id = " . $order_id;
//

// Output the new delivery id!
	print $delivery_id;
}

function add_delivery_lines( $delivery_id, $lines, $edit ) {
	print header_text();
	if ( $edit ) {
		$d = new delivery( $delivery_id );
		$d->DeleteLines();
	}

	for ( $pos = 0; $pos < count( $lines ); $pos += 8 ) {
		$prod_id          = $lines[ $pos ];
		$product_name     = $lines[ $pos + 1 ];
		$quantity         = $lines[ $pos + 2 ];
		$quantity_ordered = $lines[ $pos + 3 ];
		$unit_ordered     = $lines[ $pos + 4 ];
		if ( ! ( strlen( $unit_ordered ) > 0 ) ) {
			$unit_ordered = "NULL";
		} // print $unit_ordered . "<br/>";
		$vat        = $lines[ $pos + 5 ];
		$price      = $lines[ $pos + 6 ];
		$line_price = $lines[ $pos + 7 ];
//        $product_name = get_product_name($prod_id);
//        my_log("product_id = " . $product_id . ", supplier_id=" . $supplier_id . ", product_name=" . $product_name);
		print "<div style=\"direction: ltr;\"> id: " . $prod_id . ", name: " . $product_name . " delivery_id: " . $delivery_id . " quantity: " . $quantity . " quantity_ordred: " . $quantity_ordered .
		      "units: " . $unit_ordered . " vat: " . $vat . " price: " . $price . " line_price: " . $line_price . "</div>";
		add_delivery_line( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id );
	}
}

function add_delivery_line( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id ) {
	global $conn;
	$product_name = preg_replace( '/[\'"%()]/', "", $product_name );
	// print "name: " . $product_name . "<br/>";

	$sql = "INSERT INTO im_delivery_lines (delivery_id, product_name, quantity, quantity_ordered, unit_ordered, vat, price, line_price, prod_id) VALUES ("
	       . $delivery_id . ", "
	       . "'" . urldecode( $product_name ) . "', "
	       . $quantity . ", "
	       . $quantity_ordered . ", "
	       . $unit_ordered . ", "
	       . $vat . ", "
	       . $price . ', '
	       . $line_price . ', '
	       . $prod_id . ' )';

	// print $sql . "<br/>";

	my_log( $sql, "db-add-delivery-line.php" );

	sql_query( $sql);
}

?>

