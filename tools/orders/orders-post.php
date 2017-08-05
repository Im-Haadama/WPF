<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */
require_once( '../tools.php' );
require_once( 'orders-common.php' );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
my_log( "Operation: " . $operation, __FILE__ );
switch ( $operation ) {
	case "replace_baskets";
		// Disable for now. replace_baskets();
		break;

	case "add_item":
		$name = $_GET["name"];
		if ( ! strlen( $name ) > 2 ) {
			die ( "no product name" );
		}
		$q = $_GET["quantity"];
		if ( ! is_numeric( $q ) ) {
			die ( "no quantity" );
		}
		$order_id = $_GET["order_id"];
		if ( ! is_numeric( $order_id ) ) {
			die ( "no order_id" );
		}
		$prod_id = get_product_id_by_name( $name );
		print "name = " . $name . ", prod_id = " . $prod_id . "<br/>";

		if ( ! is_numeric( $prod_id ) ) {
			die ( "no prod_id for " . $name . "<br/>" );
		}
		$order = new WC_Order( $order_id );
		order_add_product( $order, $prod_id, $q );
		break;

	case "delete_lines":
		$order_id = $_GET["order_id"];
		if ( ! is_numeric( $order_id ) ) {
			die ( "no order_id" );
		}
		$params = explode( ',', $_GET["params"] );
//        $order = new WC_Order($order_id);
		order_delete_lines( $params );
		break;

	default:
		// die("operation " . $operation . " not handled<br/>");

}

function replace_baskets() {
	$sql = 'SELECT posts.id'
	       . ' FROM `wp_posts` posts'
	       . " WHERE post_status LIKE '%wc-processing%' or post_status LIKE '%wc-on-hold%' order by 1";

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	while ( $row = mysql_fetch_row( $export ) ) {
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

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	while ( $row = mysql_fetch_row( $export ) ) {
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


switch ( $operation ) {
	case "replace_baskets":
		replace_baskets();
		break;
}

?>

