<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/08/16
 * Time: 15:06
 */
require_once( '../tools_wp_login.php' );
require_once( '../../wp-content/plugins/woocommerce/includes/wc-order-item-functions.php' );
require_once( '../header.php' );
require_once( 'orders-common.php' );

$order_id = $_GET["order_id"];
if ( $order_id > 0 ) {
	print "מחליף פריטים בסל " . $order_id . "<br/>";
	replace_basket_with_products( $order_id );
} else {
	print "Usage: replace-basket.php?order_id=";
}

// remove_dislike_from_order(3972);
function replace_basket_with_products( $order_id ) {
	print "replace start. order_id = " . $order_id . "<br/>";

	$order  = new WC_Order( $order_id );
	$status = $order->get_status();
	print "status is " . $status . "<br/>";
	$order->update_status( 'pending', 'replace_basket_with_products' );

	$sql = 'select '
	       . ' woi.order_item_name, woim.meta_value, woim.order_item_id'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
	       . ' where order_id = ' . $order_id
	       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
	       . ' group by woi.order_item_name order by 1'
	       . ' ';

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	while ( $row = mysql_fetch_row( $export ) ) {
		$order_item_id = $row[2];
		$product_id    = get_prod_id( $order_item_id );
		print "prod_id = " . $product_id;

		if ( is_basket( $product_id ) ) {
			print " is basket";
			print "adding " . $product_id . "<br/>";
			order_add_product( $order, $product_id, quantity_in_order( $row[2] ), true );
			// $order = wc_get_order($order_id);
			print "deleting " . $row[2] . "<br/>";
			wc_delete_order_item( $order_item_id );
		}
		print "done<br/>";
	}
	print "updating status<br/>";
	$order->calculate_totals();
	$order->update_status( $status );

}

?>