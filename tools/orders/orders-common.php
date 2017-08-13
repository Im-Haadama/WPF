<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/10/15
 * Time: 08:00
 */

require_once( "../tools.php" );
// BAD: print header_text();
// require_once("../tools_wp_login.php");
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

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );
	$row = mysql_fetch_row( $export );

	return $row[0];
}

function order_get_field( $order_id, $field_name ) {
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $order_id
	       . " AND meta_key = '" . $field_name . "'";
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );

//	print $row[0] + "<br>";
	return $row[0];
}

function get_max_supplier() {
	$sql = 'SELECT max(id) FROM im_suppliers';

	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );

	return $row[0];
}

function order_info( $order_id, $field_name ) {
	$sql_i = 'SELECT meta_value FROM `wp_postmeta` pm'
	         . ' WHERE pm.post_id = ' . $order_id
	         . ' AND `meta_key` = \'' . $field_name . '\'';
	$export_i = mysql_query( $sql_i ) or die ( __FILE__ . ":" . __LINE__ . ". Sql=" . $sql_i . ". Error: " . mysql_error() );
	$row_i = mysql_fetch_row( $export_i );

	return $row_i[0];
}

function order_info_data( $order_id ) {
	print gui_header( 1, "הזמנה מספר " . $order_id );
	$data      = '<table>';
	$client_id = get_customer_id_by_order_id( $order_id );
	// Client info
	$row_text = '<tr><td>לקוח:</td><td>' . order_info( $order_id, '_billing_first_name' ) . ' '
	            . order_info( $order_id, '_billing_last_name' ) . '</td><tr>';
	$data     .= $row_text;
	$row_text = '<tr><td>טלפון:</td><td>' . order_info( $order_id, '_billing_phone' ) . '</td><tr>';
	$data     .= $row_text;

	// Shipping info
	$row_text = '<tr><td>משלוח:</td><td>' . order_info( $order_id, '_shipping_first_name' ) . ' '
	            . order_info( $order_id, '_shipping_last_name' ) . '</td><tr>';
	$data     .= $row_text;
	$row_text = '<tr><td>כתובת:</td><td>' . order_info( $order_id, '_shipping_address_1' ) . ' '
	            . order_info( $order_id, '_shipping_address_2' ) . '</td><tr>';
	$data     .= $row_text;
	$row_text = '<tr><td>כתובת:</td><td>' . order_info( $order_id, '_shipping_city' ) . '</td><tr>';
	$data     .= $row_text;

	$preference = "";
	foreach ( get_user_meta( $client_id, "preference" ) as $pref ) {
		$preference .= $pref;
	}

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

	$data .= gui_row( array(
		"איזור משלוח:",
		$zone_name,
		"ימים: ",
		sql_query_single_scalar( "SELECT delivery_days FROM wp_woocommerce_shipping_zones WHERE zone_id =" . $zone )
	) );

	$data .= '</table>';

	return $data;
}


function get_order_itemmeta( $order_item_id, $meta_key ) {
	$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
	        . ' WHERE order_item_id = ' . $order_item_id
	        . ' AND meta_key = \'' . $meta_key . '\''
	        . ' ';

//    my_log("get_order_itemmeta", $sql2);

	$export2 = mysql_query( $sql2 ) or die ( "Sql error : " . mysql_error() );

	$fields2 = mysql_num_fields( $export2 );

	$row2   = mysql_fetch_row( $export2 );
	$result = $row2[0];

	return $result;
}

function quantity_in_order( $order_item_id ) {
	// Get and display item quantity
	$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
	        . ' WHERE order_item_id = ' . $order_item_id
	        . ' AND `meta_key` = \'_qty\''
	        . ' ';

	$export2 = mysql_query( $sql2 ) or die ( "Sql error : " . mysql_error() );

	$row2 = mysql_fetch_row( $export2 );

	return $row2[0];
}

function order_delete_lines( $lines ) {
	print "order_delete_lines<br/>";
	foreach ( $lines as $line ) {
		print $line;
		print wc_delete_order_item( $line );
	}

}

function order_add_product( $order, $product_id, $quantity, $replace = false, $client_id = - 1 ) {
	// If it's a new order we need to get the client_id. Otherwise get it from the order.
	if ( $client_id == - 1 ) {
		$client_id = get_post_meta( $order->get_id(), '_customer_user', true );
	}

	my_log( __METHOD__, __FILE__ );
	my_log( "product = " . $product_id, __METHOD__ );
	if ( $replace and ( is_basket( $product_id ) ) ) {
		my_log( "Add basket products " . $product_id );
		$sql = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $product_id;

		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );
		while ( $row = mysql_fetch_row( $export ) ) {
			$prod_id = $row[0];
			$q       = $row[1];
			order_add_product( $order, $prod_id, $q * $quantity, true, $client_id );
		}
	} else {
		my_log( __METHOD__ . ": adding product " . $product_id, __FILE__ );
		if ( ! user_dislike( $client_id, $product_id ) ) {
			$product = wc_get_product( $product_id );
			$order->add_product( $product, $quantity );
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
//            while( $row = mysql_fetch_row( $export ) ) {
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


function print_order_info( $order_id ) {
	print order_info_data( $order_id );
//    if (is_numeric($order_id)) {
//    	$client_id = get_customer_id_by_order_id($order_id);
//        $order = new WC_Order($order_id);
//        $order_date = $order->order_date;
//
//        $data = "<h1>הזמנת לקוח מספר" . $order_id . "</h1>";
//        $data .= "<p>" . "מתאריך " . $order_date . "</p>";
//
//        $data .= '<table>';
//        $row_text = '<tr><td>לקוח:</td><td>' . addslashes(order_info($order_id, '_shipping_first_name')) . ' '
//            . order_info($order_id, '_shipping_last_name') . '</td><tr>';
//        $data .= $row_text;
//        $row_text = '<tr><td>כתובת:</td><td>' . order_info($order_id, '_shipping_address_1') . ' '
//            . order_info($order_id, '_shipping_address_2') . '</td><tr>';
//        $data .= $row_text;
//        $row_text = '<tr><td>כתובת:</td><td>' . order_info($order_id, '_shipping_city') . '</td><tr>';
//        $row_text .= '<tr><td>טלפון:</td><td>' . order_info($order_id, '_billing_phone') . '</td><tr>';
//        $data .= $row_text;
//
//		$preference = "";
//	    foreach (get_user_meta($client_id, "preference") as $pref)
//		    $preference .= $pref;
//
//	    $data .= gui_row(array("העדפות לקוח:", $preference));
//
//	    $data .= '</table>';
//
//        print $data;
//    } else {
//        die ("Error: no order_id");
//    }
}

// $multiply is the number of ordered baskets or 1 for ordinary item.
function orders_per_item( $prod_id, $multiply, $short = false ) {
	my_log( "prod_id=" . $prod_id, __METHOD__ );

	$sql = 'select woi.order_item_id, order_id'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
	       . ' where order_id in'
	       . ' (SELECT id FROM `wp_posts` '
	       . ' WHERE `post_status` LIKE \'%wc';

	global $history;

	if ( ! $history ) {
		$sql .= '-processing';
	}

	$sql .= '%\')'
	        . ' and woi.order_item_id = woim.order_item_id '
	        . ' and (woim.meta_key = \'_product_id\' or
                 woim.meta_key = \'_variation_id\') and woim.meta_value = ' . $prod_id;

	my_log( $sql, "get-orders-per-item.php" );

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );
	$lines = "";

	while ( $row = mysql_fetch_row( $export ) ) {
		$order_item_id = $row[0];
		$order_id      = $row[1];
		$quantity      = get_order_itemmeta( $order_item_id, '_qty' );
		$first_name    = get_postmeta_field( $order_id, '_shipping_first_name' );
		$last_name     = get_postmeta_field( $order_id, '_shipping_last_name' );

		if ( $short ) {
			$lines .= $first_name . ", ";
		} else {
			$line  = "<tr>" . "<td> " . $order_id . "</td><td>" . $quantity * $multiply . "</td><td>" . $first_name . "</td><td>" . $last_name . "</td></tr>";
			$lines .= $line;
		}
	}

	return $lines;
}

function orders_create_subs() {
	global $conn;

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

		$shipping = new $method;

		$rate           = new WC_Shipping_Rate();
		$rate->id       = 'flat_rate';
		$rate->label    = 'משלוח';
		$rate->cost     = $shipping->cost;
		$rate->calc_tax = 'per_order';

		$order->add_shipping( $rate );

		my_log( "add_product" );
		$order->calculate_totals();

		my_log( "totals" );
		// assign the order to the current user
		update_post_meta( $order->get_id(), '_customer_user', $user_id );
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

function calculate_total_products() {
	$needed_products = array();

	calculate_needed( $needed_products );

	$total   = 0;
	$variety = 0;

	foreach ( $needed_products as $q ) {
		$total += $q;
		$variety ++;
	}

	return $variety . " סוגי מוצרים" . "<br/>" . $total . " פריטים ";
}

function calculate_needed( &$needed_products ) {
	global $conn;
	$sql = "SELECT id FROM wp_posts " .
	       " WHERE post_status LIKE '%wc-processing%'";

	$result = mysqli_query( $conn, $sql );

	while ( $row = mysqli_fetch_assoc( $result ) ) {
		$id = $row["id"];
//        print "processing " . $id . "<br/>";

		$order       = new WC_Order( $id );
		$order_items = $order->get_items();
		foreach ( $order_items as $item ) {
			$variation = null;
			$qty       = $item['qty'];
			// var_dump($item);
			$key = $item['product_id'];
			// if ($prod_id == 5016) var_dump($item);
			if ( isset( $item["variation_id"] ) ) {
				$variation = $item["variation_id"];
			}
			add_products( $key, $qty, $needed_products, $variation );
			//   print $item['product_id'] . " " . $item['qty'] . "<br/>";
		}
	}
}

function add_products( $prod_id, $qty, &$needed_products, $variation ) {
	if ( is_basket( $prod_id ) ) {
//                print $prod_id . " is basket ";
		foreach (
			get_basket_content_array( $prod_id ) as $basket_prod =>
			$basket_q
		) {
			add_products( $basket_prod, $qty * $basket_q, $needed_products, $variation );
		}
	} else {
		if ( $variation ) {
			$needed_products[ $variation ] += $qty;
		} else {
			$needed_products[ $prod_id ] += $qty;
		}
	}

}

function create_order( $user_id, $ids, $quantities, $comments ) {
	$last_order = get_last_order( $user_id );

	$order    = wc_create_order();
	$order_id = trim( str_replace( '#', '', $order->get_order_number() ) );

	for ( $i = 0; $i < count( $ids ); $i ++ ) {
		$order->add_product( wc_get_product( $ids[ $i ], $quantities[ $i ] ) );
	}

	$order->add_shipping( 25 );
	$user = get_userdata( $user_id );
	$order->calculate_totals();
	$order->customer_message = $comments;
	update_post_meta( $order_id, '_customer_user', $user_id );
	foreach (
		array(
			'_billing_first_name',
			'_billing_last_name',
			'_customer_user',
			'_billing_phone',
			'_shipping_first_name',
			'_shipping_address_1',
			'_shipping_address_2',
			'_shipping_city'
		)
		as $key
	) {
		//    print $key . "<br/>";
		copy_meta_field( $last_order, $order_id, $key );
	}

//	print "הזמנה נקלטה בהצלחה.";
	//print $comments;
	return $order_id;
}

/// print "התקבלה הזמנה של סל " . get_product_name($basket_id) . " עבור " . get_customer_name($user_id);

function copy_meta_field( $source, $destination, $meta_key ) {
	set_post_meta_field( $destination, $meta_key, get_meta_field( $source, $meta_key ) );
}

