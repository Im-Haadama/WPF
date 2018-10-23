<?php
// error_reporting( E_ALL );
// ini_set( 'display_errors', 'on' );


//header( "Access-Control-Allow-Origin: http://store.im-haadama.co.il" );
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */
// require_once( '../r-shop_manager.php' );
require_once( "../im_tools.php" );
require_once( 'orders-common.php' );
if ( ! current_user_can( "edit_shop_orders" ) ) {
	print "no permissions";
	die( 0 );
}

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
my_log( "Operation: " . $operation, __FILE__ );
switch ( $operation ) {
	case "get_rate":
		$user_id = $_GET["id"];
		print customer_type_name( $user_id );
		break;
	case "get_client_info":
		$user_id = $_GET["id"];
		print customer_type_name( $user_id );
		print "\n";
		print get_user_address( $user_id );
		break;

	case "save_order_excerpt":
		$excerpt  = $_GET["excerpt"];
		$order_id = $_GET["order_id"];
		order_set_excerpt( $order_id, $excerpt );
		break;

	case "create_order":
		$user_id    = $_GET["user_id"];
		$prods      = $_GET["prods"];
		$quantities = $_GET["quantities"];
		$comments   = $_GET["comments"];
		$units      = $_GET["units"];
		$mission_id = $_GET["mission_id"];
		$type       = null;
		if ( isset( $_GET["type"] ) ) {
			$type = $_GET["type"];
		}

		// print header_text();
		// print "creating order for " . get_user_name( $user_id );
//		print "pos: " . $pos . "<br/>";
		create_order( $user_id, $mission_id, explode( ",", $prods ),
			explode( ",", $quantities ), $comments, explode( ",", $units ), $type );
		break;
	case "replace_baskets":
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
		$units = null;
		if ( isset ( $_GET["units"] ) ) {
			$units = $_GET["units"];
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
		order_add_product( $order, $prod_id, $q, false, - 1, $units );
		sql_query( "DELETE FROM im_need_orders WHERE order_id = " . $order_id );
		// order_calculate($order_id);
		break;

	case "delete_lines":
		$order_id = $_GET["order_id"];
		if ( ! is_numeric( $order_id ) ) {
			die ( "no order_id" );
		}
		$params = explode( ',', $_GET["params"] );
//        $order = new WC_Order($order_id);
		order_delete_lines( $params );
		// order_calculate($order_id);
		break;

	case "start_handle":
		$ids = $_GET["ids"];
		order_change_status( explode( ",", $ids ), "wc-processing" );
		break;

	case "cancel_orders":
		$ids = $_GET["ids"];
		order_change_status( explode( ",", $ids ), "wc-cancelled" );
		break;

	case "delivered":
		$ids = $_GET["ids"];
		order_change_status( explode( ",", $ids ), "wc-completed" );
		// print "delivered";
		break;

	case "mission":
		print ( "change mission" );
		$mission_id = $_GET["id"];
		$order_id   = $_GET["order_id"];
		my_log( "mission=" . $mission_id . " order_id=" . $order_id );
		order_set_mission_id( $order_id, $mission_id );

	default:
		// die("operation " . $operation . " not handled<br/>");
}

function order_calculate( $order_id ) {
	$lines       = sql_query_array_scalar( "select order_item_id " .
	                                       " from wp_woocommerce_order_items where order_id = $order_id" .
	                                       " and order_item_type = 'line_item'" );
	$client_type = customer_type( order_get_customer_id( $order_id ) );
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

	$result = sql_query( $sql );

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

	$result = sql_query( $sql );

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

switch ( $operation ) {
	case "replace_baskets":
		replace_baskets();
		break;
}

?>
