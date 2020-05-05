<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( dirname( __FILE__ ) ) ) ));
}

require_once(FRESH_INCLUDES . '/im-config.php');
//require_once(FRESH_INCLUDES . "/init.php");
require_once( "../multi-site/imMulti-site.php" );
require_once( 'orders-common.php' );

$order_id = GetParam( "order_id" );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
MyLog( "Operation: " . $operation, __FILE__ );

function order_calculate( $order_id ) {
	$o           = new Order( $order_id );
	$lines       = SqlQueryArrayScalar( "select order_item_id " .
	                                    " from wp_woocommerce_order_items where order_id = $order_id" .
	                                    " and order_item_type = 'line_item'" );
	$client_type = $o->getCustomerId();
	$total       = 0;
	foreach ( $lines as $line ) {
		$q       = get_order_itemmeta( $line, '_qty' );
		$prod_id = get_order_itemmeta( $line, '_product_id' );
		if ( ! ( $prod_id > 0 ) ) {
			print $line . " bad prod id <br/>";
			continue;
		}
		$p          = get_price( $prod_id, $client_type, $q );
		$total_line = $p * $q;
		$total      += $total_line;
		set_order_itemmeta( $line, '_line_total', $total_line );
		print $line . " " . get_product_name( $prod_id ) . " " . $q . " " . $p . " " . $q * $p . "<br/>";
	}
	set_post_meta_field( $order_id, '_order_total', $total );
	print $total;
}

function replace_baskets() {
	$sql = 'SELECT posts.id'
	       . ' FROM `wp_posts` posts'
	       . " WHERE post_status LIKE '%wc-processing%' or post_status LIKE '%wc-on-hold%' order by 1";

	$result = SqlQuery( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$order_id = $row[0];

		replace_basket_with_products( $order_id );
	}
}

function remove_dislike_from_order( $order_id ) {
	print "replace start. order_id = " . $order_id . "<br/>";

	$order = new WC_Order( $order_id );
	$order->update_status( 'pending', 'order_note' );

	$sql = 'select '
	       . ' woi.order_item_name, woim.meta_value, woim.order_item_id'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
	       . ' where order_id = ' . $order_id
	       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
	       . ' group by woi.order_item_name order by 1'
	       . ' ';

	$result = SqlQuery( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$order_item_id = $row[2];
		$product_id    = get_prod_id( $order_item_id );
		print "prod_id = " . $product_id;

		if ( user_dislike( $order->get_user_id(), $product_id ) ) {
			print " is dislike";
			wc_delete_order_item( $row[2] );
		}
		print "<br/>";
	}
}

function customer_delivery_options( $user_id ) {
	$postcode = get_user_meta( $user_id, 'shipping_postcode', true );
	if (! $postcode) return "לא נמצא. בדוק עם שירות הלקוחות זמינות משלוח";

// 	print "code= " . $postcode . "<br/>";
	$package = array( 'destination' => array( 'country' => 'IL', 'state' => '', 'postcode' => $postcode ) );
	$zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
	$methods = $zone->get_shipping_methods();

	$options = array();
	foreach ( $methods as $k => $method ) {
		// var_dump ($method);
		$n               = array();
		$n["id"]         = $k;
		$n["title"]      = $method->title;
		$n["data-price"] = $method->cost;
		array_push( $options, $n );
	}

//		var_dump($method);

	return gui_select( "select_method", "title", $options, "onchange=\"update_shipping()\"", 0 );
}
?>
