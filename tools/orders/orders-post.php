<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once( ROOT_DIR . "/tools/im_tools.php" );
require_once( "../multi-site/imMulti-site.php" );
header( ImMultiSite::CORS( $_SERVER['HTTP_ORIGIN'] ) );
// require_once( '../r-multisite.php' );
require_once( 'orders-common.php' );
$order_id = get_param( "order_id" );

//if ( ! $multisite and ! current_user_can( "edit_shop_orders" ) and
//                      order_get_customer_id( $order_id ) != get_current_user_id()
//) {
//	print "No permissions. " . __FILE__ . "<br/>";
//	die( 0 );
//}

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
		$Order    = new Order( $order_id );
		$Order->SetComments( $order_id, $excerpt );
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
		$o = Order::CreateOrder( $user_id, $mission_id, explode( ",", $prods ),
			explode( ",", $quantities ), $comments, explode( ",", $units ), $type );

		print "הזמנה " . $o->GetID() . " נקלטה בהצלחה.";

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
		$o = new Order( $order_id );
		$o->AddProduct( $prod_id, $q, false, - 1, $units );
		break;

	case "delete_lines":
		$order_id = $_GET["order_id"];
		if ( ! is_numeric( $order_id ) ) {
			die ( "no order_id" );
		}
		$lines = get_param_array( "param" );
		$o     = new Order( $order_id );
		foreach ( $lines as $line ) {
			wc_delete_order_item( $line );
		}


		break;

	case "start_handle":
		$ids = get_param_array( "ids" );
		foreach ( $ids as $id ) {
			$o = new Order( $id );
			$o->ChangeStatus( "wc-processing" );
		}
		break;

	case "cancel_orders":
		$ids = get_param_array( "ids" );
		foreach ( $ids as $id ) {
			$o = new Order( $id );
			$o->ChangeStatus( "wc-cancelled" );
		}
		break;

	case "delivered":
		$ids = $_GET["ids"];
		foreach ( explode( ",", $ids ) as $id ) {
			$o = new Order( $id );
			$o->delivered();
		}
		print "delivered";
		break;

	case "mission":
//		print ( "change mission" );
		$mission_id = $_GET["id"];
		$order_id   = $_GET["order_id"];
		my_log( "mission=" . $mission_id . " order_id=" . $order_id );
		$o = new Order( $order_id );
		$o->SetMissionID( $mission_id );
		break;

	case "replace_baskets":
		replace_baskets();
		break;

	case "check_email":
		$email = get_param( "email" );
		if ( ! $email or strlen( $email ) < 5 ) {
			print "u"; // unknown

			return;
		}
		$u       = get_user_by( "email", $email );
		if ( $u ) {
			$user_id = $u->ID;
			print "שלום " . get_customer_name( $user_id ) . "<br/>";
			print customer_delivery_options( $user_id );
		} else {
			print "אין לקוח כתובת מייל זאת";
		}
		break;

	default:
		// die("operation " . $operation . " not handled<br/>");
}

function order_calculate( $order_id ) {
	$o           = new Order( $order_id );
	$lines       = sql_query_array_scalar( "select order_item_id " .
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

function customer_delivery_options( $user_id ) {
	$postcode = get_user_meta( $user_id, 'shipping_postcode', true );
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

	print gui_select( "select_method", "title", $options, "onchange=\"update_shipping()\"", 0 );
}
?>
