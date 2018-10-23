<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/10/15
 * Time: 08:00
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . "/catalog/bundles.php" );
require_once( TOOLS_DIR . '/maps/build-path.php' );
require_once( TOOLS_DIR . '/account/gui.php' );
require_once( TOOLS_DIR . '/account/account.php' );
require_once( TOOLS_DIR . '/delivery/delivery.php' );
require_once( TOOLS_DIR . '/orders/Order.php' );

// BAD: print header_text();
function orders_item_count( $item_id ) {
	$sql = ' select sum(woim.meta_value) '
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim, '
	       . ' wp_woocommerce_order_itemmeta woim1 '
	       . ' where order_id in '
	       . ' (SELECT id FROM `wp_posts` '
	       . ' WHERE `post_status` LIKE \'%wc-processing%\') '
	       . 'and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_qty\''
	       . 'and woi.order_item_id = woim1.order_item_id and woim1.`meta_key` = \'_product_id\''
	       . 'and woim1.meta_value = ' . $item_id;

	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

	return $row[0];
}

function order_get_field( $order_id, $field_name ) {
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $order_id
	       . " AND meta_key = '" . $field_name . "'";
	// print $sql . "<br>";
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

//	print $row[0] + "<br>";
	return $row[0];
}

function get_max_supplier() {
	$sql = 'SELECT max(id) FROM im_suppliers';

	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

	return $row[0];
}

function order_info( $order_id, $field_name ) {
	global $conn;

	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	         . ' WHERE pm.post_id = ' . $order_id
	         . ' AND `meta_key` = \'' . $field_name . '\'';

	return sql_query_single_scalar( $sql );
}

function order_info_data( $order_id, $edit_order = false, $operation = null ) {
	global $logo_url;
	$header = "";
	if ( $operation ) {
		$header .= $operation;
	}
	if ( is_array( $order_id ) ) {
		$header   .= "הזמנה מספר " . comma_implode( $order_id );
		$order_id = $order_id[0];
	} else {
		$header .= "הזמנה מספר " . $order_id;
	}
	$order = new WC_Order( $order_id );
	$data = gui_header( 1, $header, true );
	$data  .= gui_header( 2, $order->order_date, true);

	$d_id = get_delivery_id( $order_id );
	if ( $d_id > 0 )
		$data .= gui_header( 2, "משלוח מספר " . $d_id);
	$data      .= "<table><tr><td rowspan='4'>";
	$data      .= '<table>';
	$client_id = order_get_customer_id( $order_id );
	// Client info
	$user_edit = "../";
	$row_text  = '<tr><td>לקוח:</td><td>' . gui_hyperlink( order_info( $order_id, '_billing_first_name' ) . ' '
	                                                       . order_info( $order_id, '_billing_last_name' ), $user_edit ) . '</td><tr>';
	$data      .= $row_text;
	$row_text  = '<tr><td>טלפון:</td><td>' . order_info( $order_id, '_billing_phone' ) . '</td><tr>';
	$data      .= $row_text;

	// Shipping info
	$row_text = '<tr><td>משלוח:</td><td>' . order_info( $order_id, '_shipping_first_name' ) . ' '
	            . order_info( $order_id, '_shipping_last_name' ) . '</td><tr>';
	$data     .= $row_text;
//	$row_text = '<tr><td>כתובת:</td><td>' . order_info( $order_id, '_shipping_address_1' ) . ' '
//	            . order_info( $order_id, '_shipping_address_2' ) . '</td><tr>';
//	$data     .= $row_text;
	$row_text = '<tr><td>כתובת:</td><td>' . order_info( $order_id, '_shipping_city' ) . '</td><tr>';
	$data     .= $row_text;

	$preference = "";
	$wp_pref    = get_user_meta( $client_id, "preference" );
	if ( $wp_pref )
		foreach ( $wp_pref as $pref ) {
		$preference .= $pref;
	}

//	$data .= gui_row(array("משימה:", order_get_mission_name($order_id)));
	$data .= gui_row( array( "העדפות לקוח:", $preference ) );

	$data .= gui_row( array( "איזור משלוח ברירת מחדל:", get_user_meta( $client_id, 'shipping_zone', true ) ) );

	$zone = order_get_zone( $order_id );
//    $data .= $zone;

	// Todo: check if it's the catch all zone
	if ( $zone == 0 ) {
		$postcode  = get_postmeta_field( $order_id, '_shipping_postcode' );
		$zone_name = "אנא הוסף מיקוד " . $postcode . " לאזור המתאים ";
	} else {
		$zone_name = zone_get_name( $zone );
	}

//	$data    .= gui_row( array(
//		"איזור משלוח:",
//		$zone_name,
//		"ימים: ",
//		sql_query_single_scalar( "SELECT delivery_days FROM wp_woocommerce_shipping_zones WHERE zone_id =" . $zone )
//	) );
	$mission = order_get_mission_id( $order_id );
	$data    .= gui_row( array( gui_select_mission( "mission_select", $mission, "onchange=\"save_mission()\"" ) ) );

	$data .= '</table>';
	$data .= "</td>";
	$data .= '<tr><td><img src=' . $logo_url . ' height="100"></td></tr>';
	$data .= "<td height='16'>" . gui_header( 2, "הערות לקוח להזמנה" ) . "</td></tr>";
	$excerpt = order_get_excerpt( $order_id );
// TODO: find why save excerpt cause window reload
	if ( $edit_order ) {
		$data .= gui_cell( gui_textarea( "order_excerpt", htmlspecialchars( $excerpt ) ) );
		$data .= gui_cell( gui_button( "btn_save_excerpt", "save_excerpt(" . $order_id . ")", "שמור הערה" ) );
	} else {
		$data .= "<tr><td valign='top'>" . nl2br( $excerpt ) . "</td></tr>";

	}
	if ( true or get_delivery_id( $order_id ) > 0 ) { // Done
		$data .= "<tr></tr>";
		$data .= "<tr></tr>";
	} else {
		$days = get_postmeta_field( $order_id, "pack_day" );
		if ( strlen( $days ) > 1 ) {
			$data .= "<tr><td>" . gui_header( 2, "יום ביצוע" . $days ) . "</td></tr>";
		} else {
			$options = array( array( "id" => 1, "name" => 'א' ), array( "id" => 2, "name" => 'ב' ) );
			$select  = gui_select( "day", "name", $options, "onchange=save_day()", null );
			$data    .= "<tr><td>" . $select . "</td></tr>";
		}
	}

	$data .= "</table>";

	return $data;
}


function get_order_itemmeta( $order_item_id, $meta_key ) {
	global $conn;
	if ( is_array( $order_item_id ) ) {
		$sql = "SELECT sum(meta_value) FROM wp_woocommerce_order_itemmeta "
		       . ' WHERE order_item_id IN ( ' . comma_implode( $order_item_id ) . ") "
		       . ' AND meta_key = \'' . mysqli_real_escape_string( $conn, $meta_key ) . '\'';

		return sql_query_single_scalar( $sql );
	}
	if ( is_numeric( $order_item_id ) ) {
		$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
	        . ' WHERE order_item_id = ' . $order_item_id
		        . ' AND meta_key = \'' . mysqli_real_escape_string( $conn, $meta_key ) . '\''
	        . ' ';

		return sql_query_single_scalar( $sql2 );
	}

	return - 1;
}

function quantity_in_order( $order_item_id ) {
// Get and display item quantity
	if ( is_numeric( $order_item_id ) ) {
		$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
		        . ' WHERE order_item_id = ' . $order_item_id
		        . ' AND `meta_key` = \'_qty\''
		        . ' ';

		return sql_query_single_scalar( $sql2 );
	}

	return 0;
}

function order_delete_lines( $lines ) {
	print "order_delete_lines<br/>";
	foreach ( $lines as $line ) {
		print $line;
		print wc_delete_order_item( $line );
	}

}

function order_change_status( $ids, $status ) {
	if ( is_array( $ids ) ) {
		$args = $ids;
	} else {
		$args = array( $ids );
	}
	foreach ( $args as $id ) {
		$order = new WC_Order( $id );
		// var_dump($order);
		$order->update_status( $status );
		// var_dump($order);
	}
}

function order_add_product( $order, $product_id, $quantity, $replace = false, $client_id = - 1, $unit = null, $type = null ) {
	$total = 0;
//	print "type: ". $type . "<br/>";
	if ( ! ( $product_id > 0 ) ) {
		die( "no product id given." );
	}
	// If it's a new order we need to get the client_id. Otherwise get it from the order.
	if ( $client_id == - 1 ) {
		$client_id = order_get_customer_id( $order->get_id() );
	}
	if ( $type ) {
		$customer_type = $type;
	} else {
		$customer_type = customer_type( $client_id );
	}

	my_log( __METHOD__, __FILE__ );
	my_log( "product = " . $product_id, __METHOD__ );
	if ( $replace and ( is_basket( $product_id ) ) ) {
		my_log( "Add basket products " . $product_id );
		$sql = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $product_id;

		$result = sql_query( $sql );
		while ( $row = mysqli_fetch_row( $result ) ) {
			$prod_id = $row[0];
			$q       = $row[1];
			$total   += order_add_product( $order, $prod_id, $q * $quantity, true, $client_id, $customer_type );
		}
	} else {
		my_log( __METHOD__ . ": adding product " . $product_id, __FILE__ );
		if ( ! user_dislike( $client_id, $product_id ) ) {
			$has_units = false;
			if ( $unit and strlen( $unit ) > 0 ) {
				$has_units = true;
				$q         = 1;
			} else {
				$q = $quantity;
			}
			// print  "<br/>" . get_product_name( $product_id ) . " " . $q . " ";
			$product = wc_get_product( $product_id );
			if ( $product ) {
				// print "type: " . $customer_type . "<br/>";
				$price = get_price_by_type( $product_id, $customer_type, $quantity );
				// print "price: " . $price . "<br/>";
				$product->set_price( $price );
				$oid = $order->add_product( $product, $q );

				// print $oid . "<br/>";

				if ( $has_units ) {
					set_order_itemmeta( $oid, 'unit', array( $unit, $quantity ) );

					return $price; // Assume about 1 kg
				}

				return $price * $quantity;
			} else {
				print $product_id . " not found <br/>";
			}
		} else {
			print "client dislike " . get_product_name( $product_id ) . "<br/>";
		}
	}
}

//        }
//    }
// else {
//        if (is_order($product_id)) {
//            $sql = 'select '
//                . ' woi.order_item_name, woim.meta_value, woim.order_item_id'
//                . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
//                . ' where order_id = ' . $product_id
//                . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
//                . ' group by woi.order_item_name order by 1'
//                . ' ';
//            my_log($sql, __METHOD__);
//
//            $export = mysql_query ( $sql ) or die ( "Sql error : " . mysql_error( ) );
//
//            while( $row = mysqli_fetch_row( $result ) ) {
//                $order_item_id = $row[2];
//                $prod_id = $row[1];
//                order_add_product($order, $prod_id, quantity_in_order($order_item_id));
//            }
//        } else {


function user_dislike( $user_id, $prod_id ) {
	$sql = 'SELECT id FROM im_client_dislike WHERE client_id=' . $user_id .
	       ' AND dislike_prod_id=' . $prod_id;

	$v = sql_query_single_scalar( $sql );

	// print $sql . " " . $v . "<br/>";

	// print $v;
	return $v;
}

//function print_order_info( $order_id, $comments, $operation = null ) {
//	print order_info_data( $order_id, $comments, $operation );
//}

// $multiply is the number of ordered baskets or 1 for ordinary item.
function orders_per_item( $prod_id, $multiply, $short = false, $include_basket = false ) {
	my_log( "prod_id=" . $prod_id, __METHOD__ );

	$sql = 'select woi.order_item_id, order_id'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
	       . ' where order_id in'
	       . '(select order_id from im_need_orders) ';

	$baskets = null;
	if ( $include_basket ) {
		$sql1    = "select basket_id from im_baskets where product_id = $prod_id";
		$baskets = sql_query_array_scalar( $sql1 );
	}
	$sql .= ' and woi.order_item_id = woim.order_item_id '
	        . ' and (woim.meta_key = \'_product_id\' or woim.meta_key = \'_variation_id\')
	         and woim.meta_value in (' . $prod_id;
	if ( $baskets ) {
		$sql .= ", " . comma_implode( $baskets );
	}
	$sql .= ")";

	my_log( $sql, "get-orders-per-item.php" );

	$result = sql_query( $sql);
	$lines = "";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$order_item_id = $row[0];
		$order_id      = $row[1];
		$quantity      = get_order_itemmeta( $order_item_id, '_qty' );
		$first_name    = get_postmeta_field( $order_id, '_shipping_first_name' );
		$last_name     = get_postmeta_field( $order_id, '_shipping_last_name' );

		if ( $short ) {
			$lines .= $quantity . " " . $last_name . ", ";
		} else {
			$line  = "<tr>" . "<td> " . gui_hyperlink( $order_id, "get-order.php?order_id=" . $order_id ) . "</td><td>" . $quantity * $multiply . "</td><td>" . $first_name . "</td><td>" . $last_name . "</td></tr>";
			$lines .= $line;
		}
	}

	return $lines;
}

function orders_create_subs() {
	print "creating orders for subscriptions<br/>";
	$sql = "SELECT client, basket, weeks, unfold_basket FROM im_subscriptions ";

	$result = sql_query_array( $sql );

	my_log( "creating subscriptions orders", __FILE__ );
	foreach ( $result as $row ) {
		$user_id = $row[0];

		print get_user_name( $user_id ) . ": last order on- " . order_get_last( $user_id ) . "<br/>";
		$diff = date_diff( new DateTime( order_get_last( $user_id ) ), new DateTime() );

		// print get_user_name($user_id) . " " . $diff->days . "<br/>";
		if ( $diff->days < ( $row[2] * 7 - 3 ) ) {
			continue;
		}

		$product_id = $row[1];
		print "creating $user_id  $product_id, $row[3]<br/>";

		my_log( "create order: product = " . $product_id, __METHOD__ );

		$order = wc_create_order();
		$order->set_created_via( "מנויים" );
		order_add_product( $order, $product_id, 1, $row[3], $user_id );
		$order_id = $order->get_id();

		$postcode = get_user_meta( $user_id, 'shipping_postcode', true );
		$package  = array( 'destination' => array( 'country' => 'IL', 'postcode' => $postcode ) );
		$zone     = WC_Shipping_Zones::get_zone_matching_package( $package );
		$method   = WC_Shipping_Zones::get_shipping_method( $zone->get_id() );

//		$shipping = new $method;

		$rate           = new WC_Shipping_Rate();
		$rate->id       = 'flat_rate';
		$rate->label    = 'משלוח';
//		$rate->cost     = $shipping->cost;
		$rate->calc_tax = 'per_order';

		$order->add_shipping( $rate );

		my_log( "add_product" );
		$order->calculate_totals();

		my_log( "totals" );
		// assign the order to the current user
		order_set_customer_id( $order->get_id(), $user_id );
		// payment_complete
		$order->payment_complete();

		// billing info
		print $order_id;
		foreach (
			array(
				'billing_first_name',
				'billing_last_name',
				'billing_phone',
				'billing_address_1',
				'billing_address_2',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_postcode'
			) as $key
		) {
//	    	print $key . " ";
			$values = get_user_meta( $user_id, $key );
//		    print $key . " " . $values[0] . "<br/>";
			update_post_meta( $order_id, "_" . $key, $values[0] );
		}
	}
}

// Add new shipping
//	    $postcode = get_user_meta($user_id, 'shipping_postcode', true);
//	    $package = array('destination' => array('country' => 'IL', 'postcode' => $postcode));
//	    $zone = WC_Shipping_Zones::get_zone_matching_package($package);
//	    $method = WC_Shipping_Zones::get_shipping_method( $zone->get_id() );
//	    // var_dump($method);
//	    $item = new WC_Order_Item_Shipping();
//	    $item->set_name("משלוח");
//	    $rate = new WC_Shipping_Flat_Rate(1);
//	    $item->set_shipping_rate($rate);

//$order->add_shipping($method);
//	    var_dump($method); print "<br/>";
// var_dump($zone); print "<br/>";

//	    die(1);
//	    $item->set_shipping_rate( new WC_Shipping_Rate('','משלוח', ) );
//	    $item->set_order_id( $order_id );
//	    $item_id = $item->save();
//	    $order->add_shipping(array())


function order_get_last( $user_id ) {
	return sql_query_single_scalar( "	select max(post_date)
		from wp_posts posts, wp_postmeta meta
		where meta.post_id = posts.id
		 	and meta.meta_key = '_customer_user'
            and meta.meta_value = '$user_id'
            and post_status in ('wc-completed', 'wc-processing', 'wc-awaiting-shipment')" );
}

function order_get_address( $order_id ) {
	if ( $order_id > 0 ) {
		$address = "";
		foreach ( array( '_shipping_address_1', '_shipping_city' ) as $field ) {
			$address .= get_meta_field( $order_id, $field ) . " ";
		}
		if ( strlen( $address ) > 4 ) {
			return $address;
		}
		// Take the address from the client;
		$client_id = order_get_customer_id( $order_id );
		print $client_id . " " . $order_id;
		$address .= get_user_address( $client_id );

		return $address;
	}

	return "Error";
}

function calculate_total_products() {
	$needed_products = array();

	calculate_needed( $needed_products );

	$total   = 0;
	$variety = 0;

	// var_dump($needed_products);
	foreach ( $needed_products as $prod_or_var => $v1 ) {
		foreach ( $needed_products[ $prod_or_var ] as $unit => $q ) {
			$total += $q;
			$variety ++;
		}
	}

	return $variety . " סוגי מוצרים" . "<br/>" . $total . " פריטים ";
}

function check_cache_validity() {
//	$sql = "SELECT count(p.id)
//	FROM wp_posts p
//	 LEFT JOIN im_need_orders o
//	  ON p.id = o.order_id
//	  WHERE p.id IS NULL OR o.order_id IS NULL AND post_status LIKE '%wc-processing%'";
	$sql = "SELECT count(id)
	FROM wp_posts p
  	where post_status like '%wc-processing%'
  	and id not in (select order_id from im_need_orders)";
	$new = sql_query_single_scalar( $sql );
//	print "new: " . $new . "<br/>";

	$sql  = "SELECT count(id)
	  FROM im_need_orders
	  WHERE order_id NOT IN (SELECT id FROM wp_posts WHERE post_status LIKE '%wc-processing%')";
	$done = sql_query_single_scalar( $sql );
//	print "done: " . $done . "<br/>";

	if ( $new + $done > 0 ) {
		return false;
	}

	return true;

}


function create_order( $user_id, $mission_id, $prods, $quantities, $comments, $units = null, $type = null ) {
//	print "user: " . $user_id;
//	var_dump($prods);

//	$last_order = get_last_order( $user_id );
//	print "last order: " .$last_order . "<br/>";

//	print "type: " . $type . "<br/>";

	$debug = false;
	if ( $debug ) {
		$order_id = 1992;
		$order    = new WC_Order( $order_id );
	} else {
		$order    = wc_create_order();
		// var_dump($order);
		$order_id = trim( str_replace( '#', '', $order->get_order_number() ) );
		// print "new order: " . $order_id . "<br/>";
	}
	// print "count: " . count($prods) . "<br/>";
	$extra_comments = "";

	order_set_mission_id( $order_id, $mission_id );

	$total = 0;

	for ( $i = 0; $i < count( $prods ); $i ++ ) {
		// $prod_name = urldecode( $prods[ $i ] );

		// print "prod_name: " . $prod_name . "<br/>";
		$prod_id = $prods[ $i ];
		// print "prod(" . $prod_name . "): " . $prod_id ."<br/>";
		if ( $prod_id > 0 ) {
			$total += order_add_product( $order, $prod_id, $quantities[ $i ], false, $user_id, $units[ $i ], $type );

			// if (strlen($units[$i]) > 1) $extra_comments .= $prod_name . " " . $quantities[$i] . " " . $units[$i] . "\n";
		} else {
			print "פריט לא נמצא " . $prods[ $i ] . "<br/>";
			my_log( "can't prod id for " . $prods[ $i ] );
		}
	}

//	if ($total < 80){
//		print "הזמנה קטנה מדי ולא נקלטה";
//		return 0;
//	}
	$comments .= "\n" . $extra_comments;

//	$order->add_shipping( 25 );
//	$user = get_userdata( $user_id );
	$order->calculate_totals();
//	$order->customer_message = $comments;
	order_set_customer_id( $order_id, $user_id);
	// print "after update user " . $user_id . "<br/>";
	foreach (
		array(
			'billing_first_name',
			'billing_last_name',
			'billing_phone',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_postcode'
		)
		as $key
	) {
//		    print $key . "<br/>";
		$value = get_user_meta( $user_id, $key, true );
		update_post_meta( $order_id, '_' . $key, $value );
	}

// 	print "comments: " . $comments . "<br/>";
	$order->save();
	$order = null;
	order_set_excerpt( $order_id, $comments );

	print "הזמנה " . $order_id . " נקלטה בהצלחה.";
	//print $comments;
	return $order_id;
}

function order_set_excerpt( $post_id, $excerpt ) {
	$sql = "UPDATE wp_posts SET post_excerpt = '" . $excerpt . "' WHERE id=" . $post_id;
	sql_query( $sql );
}

function order_get_excerpt( $post_id ) {
	$sql = "SELECT post_excerpt FROM wp_posts WHERE id=" . $post_id;

	return sql_query_single_scalar( $sql );
}

/// print "התקבלה הזמנה של סל " . get_product_name($basket_id) . " עבור " . get_customer_name($user_id);

function copy_meta_field( $source, $destination, $meta_key ) {
	set_post_meta_field( $destination, $meta_key, get_meta_field( $source, $meta_key ) );
}

function set_order_itemmeta( $order_item_id, $meta_key, $meta_value ) {
	global $conn;
	$value = $meta_value;

	if ( is_array( $meta_value ) ) {
		$value = implode( ",", $meta_value );
	}

	if ( sql_query_single_scalar( "SELECT count(*) FROM wp_woocommerce_order_itemmeta " .
	                              " WHERE order_item_id = " . $order_item_id .
	                              " AND meta_key = '" . $meta_key . "'" ) >= 1
	) {
		$sql = "update wp_woocommerce_order_itemmeta " .
		       " set meta_value = '" . $value . "'" .
		       " where order_item_id = " . $order_item_id .
		       " and meta_key = '" . $meta_key . "'";
	} else {
		$sql = "INSERT INTO wp_woocommerce_order_itemmeta " .
		       " (order_item_id, meta_key, meta_value) " .
		       " VALUES (" . $order_item_id . ", '" . $meta_key . "', '" . $value . "')";
	}
	sql_query( $sql );
}

class OrderFields {
	const
		/// User interface
		line_select = 0,
		type = 1,
		mission = 2,
		order_id = 3,
		customer = 4,
		recipient = 5,
		total_order = 6,
		good_costs = 7,
		margin = 8,
		delivery_fee = 9,
		city = 10,
		payment_type = 11,
		delivery_note = 12,
		percentage = 13,
		field_count = 14;
}

;

$order_header_fields = array(
	"בחר", // replaced in loop
	"סוג משלוח",
	"משימה",
	"מספר הזמנה",
	"שם המזמין",
	"עבור",
	"סכום הזמנה",
	"עלות מוצרים",
	"מרווח",
	"דמי משלוח",
	"ישוב",
	"אמצעי תשלום",
	"תעודת משלוח",
	"אחוז סופק"
);


function orders_table( $statuses, $build_path = true, $user_id = 0, $week = null ) {
	global $order_header_fields;
	global $conn;

	$show_fields = array();
	$empty_line  = array();
	for ( $i = 0; $i < OrderFields::field_count; $i ++ ) {
		$empty_line[ $i ]  = "";
		$show_fields[ $i ] = true;
	}
	if ( ! current_user_can( "show_business_info" ) ) {
		$show_fields[ OrderFields::total_order ] = false; // current_user_can("show_business_info");
		$show_fields[ OrderFields::margin ]      = false;
		$show_fields[ OrderFields::good_costs ]  = false;
	}

	$status_names = wc_get_order_statuses();
	$all_tables = "";
	if ( ! is_array( $statuses ) ) {
		$statuses = array( $statuses );
	}
	foreach ( $statuses as $status ) {
		// print $status . "<br/>";

		$order_header_fields[0] = gui_checkbox( "select_all_" . $status, "table", 0, "onclick=\"select_all_toggle('select_all_" . $status . "', 'select_order_" . $status . "')\"" );
		$rows                   = array( $order_header_fields );
		$sql                    = 'SELECT posts.id'
		        . ' FROM `wp_posts` posts'
		        . " WHERE post_status = '" . $status . "'";

		if ( $week ) {
			$sql = "select order_id from im_delivery where FIRST_DAY_OF_WEEK(date) = '" . $week . "'";
		}

		if ( $user_id ) {
			$sql .= " and order_user(id) = " . $user_id;
		}
		$sql .= " order by 1";

		// print $sql;
		// Build path
		$order_ids = sql_query_array_scalar( $sql );

		// If no orders in this status, move on.
		if ( sizeof( $order_ids ) < 1 ) {
			continue;
		}

		$i = count( $order_ids ) - 1;
//		if ( $build_path ) {
//			while ( $i >= 0 ) {
//				// print "<br/>handle " . $order_ids[$i] . ":";
//				print map_get_order_address($order_ids[$i]) . " " . get_distance( 1, $order_ids[ $i ] ) . "<br/>";
//				if ( get_distance( 1, $order_ids[ $i ] ) < 0 ) {
//					print "משלוח " . $order_ids[ $i ] . " לא נכלל במסלול" . "<br/>";
//////					//			    print "removing..";
//////					// var_dump($order_ids); print "<br/>";
//////					unset( $order_ids[ $i ] );
////					$order_ids = array_values( $order_ids );
//
//					// var_dump($order_ids);
//					// die (1);
//				}
//				$i --;
//			}
//		}
		$path = array();
//		if ( $build_path ) {
//			find_route_1( 1, $order_ids, $path, false );
//		}
		$result                = mysqli_query( $conn, $sql );
		$total_delivery_total  = 0;
		$total_order_total     = 0;
		$total_order_delivered = 0;
		$total_delivery_fee    = 0;
		$lines                 = array();

		if ( ! $result ) {
			continue;
		}

		$count = 0;

		while ( $row = mysqli_fetch_row( $result ) ) {
			// debug_time1("after fetch");
			$count ++;
			$order_id = $row[0];
			$order    = new Order( $order_id );

			$customer_id = order_get_customer_id( $order_id );

			$line                              = $empty_line;
			$line [ OrderFields::line_select ] = gui_checkbox( "chk_" . $order_id, "select_order_" . $status );
			$line[ OrderFields::type ]         = order_get_shipping( $order_id );

			// display order_id with link to display it.
			// 1) order ID with link to the order
			$mission_id = order_get_mission_id( $order_id );
			// print $order_id. " ". $mission . "<br/>";

			$line[ OrderFields::mission ]  = gui_select_mission( "mis_" . $order_id, $mission_id, "onchange=\"mission_changed(" . $order_id . ")\"" );
			$line[ OrderFields::order_id ] = gui_hyperlink( $order_id, MultiSite::LocalSiteTools() . "/orders/get-order.php?order_id=" . $order_id );

			// 2) Customer name with link to his deliveries
			$line[ OrderFields::customer ] = gui_hyperlink( get_customer_name( $customer_id ), MultiSite::LocalSiteTools() .
			                                                                                   "/account/get-customer-account.php?customer_id=" . $customer_id );

			$line[ OrderFields::recipient ] = get_postmeta_field( $order_id, '_shipping_first_name' ) . ' ' .
			                                  get_postmeta_field( $order_id, '_shipping_last_name' );

			// 3) Order total
			$order_total = $order->GetTotal();
			// get_postmeta_field( $order_id, '_order_total' );
			$line[ OrderFields::total_order ] = $order_total;
			$total_order_total                += $order_total;

			// 4) Delivery note
			$delivery_id = get_delivery_id( $order_id );

			if ( $delivery_id > 0 ) {
				$delivery                           = new Delivery( $delivery_id );
				$line[ OrderFields::delivery_note ] = gui_hyperlink( $delivery_id,
					MultiSite::LocalSiteTools() . "/delivery/get-delivery.php?id=" . $delivery_id );
				//if ( $delivery_id > 0 ) {
					$line[ OrderFields::total_order ]  = $order_total; // $delivery->Price();
					$line[ OrderFields::delivery_fee ] = $delivery->DeliveryFee();
					$percent                           = "";
					if ( ( $order_total - $delivery->DeliveryFee() ) > 0 ) {
						$percent = round( 100 * ( $delivery->Price() - $delivery->DeliveryFee() ) / ( $order_total - $delivery->DeliveryFee() ), 0 ) . "%";
					}
					$line[ OrderFields::percentage ] = $percent;
					$total_delivery_total            += $delivery->Price();
				$total_delivery_fee                  = $delivery->DeliveryFee();
					$total_order_delivered           += $order_total;
				//	}
			} else {
				$line[ OrderFields::city ]          = order_info( $order_id, '_shipping_city' );
				$line[ OrderFields::delivery_note ] = gui_hyperlink( "צור", "../delivery/create-delivery.php?order_id=" . $order_id );
				$total_delivery_fee                 = order_get_shipping_fee( $order_id );
			}
			$line[ OrderFields::payment_type ] = get_payment_method_name( $customer_id );
			$line[ OrderFields::good_costs ]   = $order->GetBuyTotal();
			$line[ OrderFields::margin ]       = round( ( $line[ OrderFields::total_order ] - $line[ OrderFields::good_costs ] ), 0 );
			$line[ OrderFields::delivery_fee ] = $total_delivery_fee; //

			array_push( $lines, array( array_search( $customer_id, $path ), $line ) );
		}
		//   $data .= "<tr> " . trim($line) . "</tr>";
		sort( $lines );

		foreach ( $lines as $line ) {
			array_push( $rows, $line[1] );
		}
//		$data .= gui_row( array( "", "", 'סה"כ', "", "", "", $total_order_total, "", "", "", "" ) );
		//$data = str_replace( "\r", "", $data );

		// $data .= "</table>";

		if ( $count > 0 ) {
			$sums = null;

			if ( current_user_can( "show_business_info" ) ) {
				$sums = array(
					"סה\"כ",
					'',
					'',
					'',
					'',
					'',
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					0
				);
			}
			$data       = gui_header( 2, $status_names[ $status ] );
			$data       .= gui_table( $rows, $status, true, true, $sums, null, null, $show_fields, $sums );
			$all_tables .= $data;
		}
	}

	return $all_tables;
}

//function order_good_costs( $order_id ) {
//	$order = new WC_Order( $order_id );
//	$total = 0;
//	foreach ( $order->get_items() as $item ) {
//		// if ($order_id == 2230) print $item->get_name() . "<br/>";
//		$q = $item->get_quantity();
//		$p = get_buy_price( $item->get_product_id() );
//		if (is_numeric($q) and is_numeric($p)) $total +=  $p * $q;
//	}
//
//	return $total;
//}

function total_order( $user_id ) {

	$sql = "SELECT id FROM wp_posts " .
	       " WHERE post_status in ('wc-processing', 'wc-awaiting-shipment') " .
	       " AND order_user(id) = " . $user_id;

	$result = sql_query( $sql );

	$items         = array();
	$order_clients = array();
	$order_ids     = array();
	$totals        = array();
	$grand_total   = 0;

	while ( $row = sql_fetch_row( $result ) ) {
		$order_id = $row[0];
		array_push( $order_ids, $order_id );
		array_push( $order_clients, gui_hyperlink( get_postmeta_field( $order_id, '_shipping_first_name' ),
			MultiSite::LocalSiteTools() . "/orders/get-order.php?order_id=" . $order_id ) );

		$totals[ $order_id ] = 0;

		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			$id = $item['product_id'];
			if ( ! array_key_exists( $id, $items ) ) {
				$items[ $id ] = array();
			}
			$items[ $id ][ $order_id ] = $item['qty'];
		}
	}

	$table = array();
	array_unshift( $order_clients, 'מחיר' );
	array_unshift( $order_clients, 'מוצר' );
	array_push( $order_clients, 'סה"כ כמות' );
	array_push( $order_clients, 'סה"כ מחיר' );
	array_push( $table, $order_clients );
	foreach ( $items as $prod_id => $item ) {
		$total_q = array_sum( $items[ $prod_id ] );
		$price   = get_price( $prod_id, 0, $total_q );
		$line    = array( get_product_name( $prod_id ), $price );
		foreach ( $order_ids as $order_id ) {
			$q = $items[ $prod_id ][ $order_id ];
			array_push( $line, $q );
			$totals[ $order_id ] += $price * $q;
		}

		array_push( $line, $total_q );
		array_push( $line, $total_q * $price );
		array_push( $table, $line );
	}
	array_unshift( $totals, '' );
	array_unshift( $totals, 'סה"כ לתשלום לפני הנחה' );
	array_push( $totals, "" );
	array_push( $totals, array_sum( $totals ) );
	array_push( $table, $totals);

	return gui_table( $table );
}

function order_get_zone( $order_id ) {
//	print "order id = " . $order_id . "<br/>";
	my_log( __METHOD__ . " order_id " . $order_id );
	$country = get_postmeta_field( $order_id, '_shipping_country' );
	// print "country = " . $country . "<br/>";

	$postcode = get_postmeta_field( $order_id, '_shipping_postcode' );;
	my_log( "postcode = " . $postcode );
	// print "postcode = " . $postcode . "<br/>";


	$zone = get_zone_from_postcode( $postcode, $country );
	if ( zone_get_name( $zone ) != 'N/A' ) {
		return $zone;
	}

	$client_id = order_get_customer_id( $order_id );

	$client_shipping_zone = get_user_meta( $client_id, 'shipping_zone', true );

	if ( strlen( $client_shipping_zone ) > 1 ) {
		return $client_shipping_zone;
	}

	return 0;
}

function order_set_customer_id( $order_id, $customer_id ) {
	update_post_meta( $order_id, '_customer_user', $customer_id );
}