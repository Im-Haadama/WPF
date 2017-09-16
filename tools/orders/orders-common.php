<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/10/15
 * Time: 08:00
 */

// require_once( "../im_tools.php" );
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

function order_info_data( $order_id, $edit = false, $operation = null ) {
	global $logo_url;
	$header = "";
	if ( $operation ) {
		$header .= $operation;
	}
	$header .= "הזמנה מספר " . $order_id;

	$data = gui_header( 1, $header, true );
	$d_id = get_delivery_id( $order_id );
	if ( $d_id > 0 )
		$data .= gui_header( 2, "משלוח מספר " . $d_id);
	$data      .= "<table><tr><td rowspan='4'>";
	$data      .= '<table>';
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
	$data .= "</td>";
	$data .= '<tr><td><img src=' . $logo_url . ' height="100"></td></tr>';
	$data .= "<td height='16'>" . gui_header( 2, "הערות לקוח להזמנה" ) . "</td></tr>";
	$data .= "<tr><td valign='top'>" . nl2br( order_get_excerpt( $order_id ) ) . "</td></tr>";
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
	foreach ( $ids as $id ) {
		$order = new WC_Order( $id );
		$order->update_status( $status );
	}
}
function order_add_product( $order, $product_id, $quantity, $replace = false, $client_id = - 1, $unit = null ) {
	// If it's a new order we need to get the client_id. Otherwise get it from the order.
	if ( $client_id == - 1 ) {
		$client_id = get_post_meta( $order->get_id(), '_customer_user', true );
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
			order_add_product( $order, $prod_id, $q * $quantity, true, $client_id );
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
			$oid = $order->add_product( wc_get_product( $product_id ), $q );

			if ( $has_units ) {
				set_order_itemmeta( $oid, 'unit', array( $unit, $quantity ) );
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


function print_order_info( $order_id, $comments, $operation = null ) {
	print order_info_data( $order_id, $comments, $operation );
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

	// var_dump($needed_products);
	foreach ( $needed_products as $prod_or_var => $v1 ) {
		foreach ( $needed_products[ $prod_or_var ] as $unit => $q ) {
			$total += $q;
			$variety ++;
		}
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

		$order       = new WC_Order( $id );
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			$prod_or_var = $item['product_id'];

			$variation = null;
			if ( isset( $item["variation_id"] ) && $item["variation_id"] > 0 ) {

				$prod_or_var = $item["variation_id"];
			}
			$qty  = $item['qty'];
			$unit = $item['unit'];
			if ( $unit ) {
				$unit_array = explode( ",", $unit );
				$unit_t     = $unit_array[0];
				$key        = array( $prod_or_var, $unit_t );
			} else {
				$key = array( $prod_or_var, '');
			}


//			$sql1 = "select order_item_id from wp_woocommerce_order_items where order_id = " . $id .
//			       " and order_item_name = '" . $item["name"] . "'";
//
//			$oid = sql_query_single_scalar($sql1);
//
//			$unit = get_order_itemmeta($oid, "unit");
//			if (strlen($unit)){
//				$unit_array = explode(",", $unit);
//			} else {
//				$unit_array = null;
//			}

			add_products( $key, $qty, $needed_products);
			//   print $item['product_id'] . " " . $item['qty'] . "<br/>";
		}
	}
}

function add_products( $prod_key, $qty, &$needed_products ) {
	// var_dump($prod_key); print "<br/>";
	// Prod key is array(prod_id or var_id, unit)

	// Handle baskets recursively
	$prod_or_var = $prod_key[0];
	if ( is_basket( $prod_or_var ) ) {
//                print $prod_id . " is basket ";
		foreach (
			get_basket_content_array( $prod_or_var ) as $basket_prod =>
			$basket_q
		) {
			add_products( array( $basket_prod, '' ), $qty * $basket_q, $needed_products);
		}
	} else {
		// Handle single product:
		$unit_str = $prod_key[1];

		switch ( $unit_str ) {
			case '':
				$unit_key = 0;
				break;
			case 'יח':
				$unit_key = 1;
				break;
			default:
				print "error: new unit ignored - " . $unit_str;
		}
//		if (strlen($unit)){
//			if (is_null($needed_products[$prod_key])) $needed_products[$prod_or_var] = array();
//		}
		// print "prod_or_var: " . $prod_or_var . " unit_key: " . $unit_key . "<br/>";

		$needed_products[ $prod_or_var ][ $unit_key ] += $qty;
		//if ($key == 354) { print "array:"; var_dump($needed_products[$prod_or_var]); print "<br/>";}
	}

}

function create_order( $user_id, $prods, $quantities, $comments, $units = null ) {
//	print "user: " . $user_id;
//	var_dump($prods);

//	$last_order = get_last_order( $user_id );
//	print "last order: " .$last_order . "<br/>";

	var_dump( $units);
	$order    = wc_create_order();
	// for DEBUG:
	// $order = new WC_Order(1897);
	$order_id = trim( str_replace( '#', '', $order->get_order_number() ) );
	print "new order: " . $order_id . "<br/>";
	// print "count: " . count($prods) . "<br/>";
	$extra_comments = "";

	for ( $i = 0; $i < count( $prods ); $i ++ ) {
		$prod_name = urldecode( $prods[ $i ] );
		// print "prod_name: " . $prod_name . "<br/>";
		$prod_id = get_product_id_by_name( $prod_name );
		// print "prod(" . $prod_name . "): " . $prod_id ."<br/>";
		if ( $prod_id > 0 ) {
			order_add_product( $order, $prod_id, $quantities[ $i ], false, $user_id, $units[ $i ] );

			// if (strlen($units[$i]) > 1) $extra_comments .= $prod_name . " " . $quantities[$i] . " " . $units[$i] . "\n";
		} else {
			print "פריט לא נמצא " . $prods[ $i ] . "<br/>";
			my_log( "can't prod id for " . $prods[ $i ] );
		}
	}

	$comments .= "\n" . $extra_comments;

//	$order->add_shipping( 25 );
//	$user = get_userdata( $user_id );
	$order->calculate_totals();
//	$order->customer_message = $comments;
	update_post_meta( $order_id, '_customer_user', $user_id );
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

	order_set_excerpt( $order_id, $comments );

	print "הזמנה נקלטה בהצלחה.";
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

	$sql = "update wp_woocommerce_order_itemmeta " .
	       " set meta_value = '" . $value . "'" .
	       " where order_item_id = " . $order_item_id .
	       " and meta_key = '" . $meta_key . "'";

	sql_query( $sql );

	if ( mysqli_affected_rows( $conn ) < 1 ) {
		$sql = "INSERT INTO wp_woocommerce_order_itemmeta " .
		       " (order_item_id, meta_key, meta_value) " .
		       " VALUES (" . $order_item_id . ", '" . $meta_key . "', '" . $value . "')";

		sql_query( $sql );
	}
}

function orders_table( $statuses ) {
	global $conn;
//	LIKE 'wc-processing%' or post_status LIKE 'wc-on-hold%' order by 1";

	$status_names = wc_get_order_statuses();
	// var_dump($status_names);
	$all_tables = "";
	if ( ! is_array( $statuses ) ) {
		$statuses = array( $statuses );
	}
	foreach ( $statuses as $status ) {
		// print $status . "<br/>";

		$data = gui_header( 2, $status_names[ $status ] );

		$sql = 'SELECT posts.id'
		       . ' FROM `wp_posts` posts'
		       . " WHERE post_status = '" . $status . "'" .
		       " order by 1";

		// print $sql;
		$result = mysqli_query( $conn, $sql );

		$data                  .= "<table id='" . $status . "'>";
		$data                  .= "<tr>";
		$data                  .= gui_cell( gui_checkbox( "chk_all", "", "",
			array( "onchange=select_orders('" . $status . "')" ) ) );
		$data                  .= gui_cell( gui_bold( "אזור" ) );
		$data                  .= "<td><h3>מספר </br> הזמנה</h3></td>";
		$data                  .= "<td><h3>שם המזמין</h3></td>";
		$data                  .= "<td><h3>עבור</h3></td>";
		$data                  .= "<td><h3>סכום</h3></td>";
		$data                  .= "</tr>";
		$count                 = 0;
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
			$order_id    = $row[0];
			$row_text    = gui_cell( gui_checkbox( "chk_" . $order_id, "select_order" ) );
			$customer_id = get_postmeta_field( $order_id, '_customer_user' );

			// display order_id with link to display it.
			$count ++;
			// 1) order ID with link to the order
			$zone = order_get_zone( $order_id );
			// print $order_id. " ". $zone . "<br/>";
			$row_text .= gui_cell( zone_get_name( $zone ) );
			$row_text .= "<td><a href=\"get-order.php?order_id=" . $order_id . "\">" . $order_id . "</a></td>";

			// 2) Customer name with link to his deliveries
			$row_text .= "<td><a href=\"../account/get-customer-account.php?customer_id=" . $customer_id . "\">" .

			             get_customer_by_order_id( $order_id ) . "</a></td>";

			$row_text .= "<td>" . get_postmeta_field( $order_id, '_shipping_first_name' ) . ' ' .
			             get_postmeta_field( $order_id, '_shipping_last_name' ) . "</td>";

			// 3) Order total
			$order_total       = get_postmeta_field( $order_id, '_order_total' );
			$row_text          .= "<td>" . $order_total . '</td>';
			$total_order_total += $order_total;

			// 4) Delivery note
			$delivery_id = get_delivery_id( $order_id );
//    print "order: " . $order_id . "<br/>" . " del: " . $delivery_id . "<br/>";
			if ( $delivery_id > 0 ) {
				$delivery = new Delivery( $delivery_id );
				$row_text .= "<td><a href=\"..\delivery\get-delivery.php?id=" . $delivery_id . "\"</a>" . $delivery_id . "</td>";
//        $total_amount = get_delivery_total($delivery_id[0]);
//        $row_text .= "<td>" . $total_amount . "</td>";
				if ( $delivery_id > 0 ) {
					$row_text .= "<td>" . $delivery->Price() . "</td>";
					$row_text .= "<td>" . $delivery->DeliveryFee() . "</td>";
					$percent  = "";
					if ( ( $order_total - $delivery->DeliveryFee() ) > 0 ) {
						$percent = round( 100 * ( $delivery->Price() - $delivery->DeliveryFee() ) / ( $order_total - $delivery->DeliveryFee() ), 0 ) . "%";
					}
					$row_text              .= "<td>" . $percent . "%</td>";
					$total_delivery_total  += $delivery->Price();
					$total_delivery_fee    += $delivery->DeliveryFee();
					$total_order_delivered += $order_total;
				}
			} else {
				$row_text .= "<td></td><td></td><td></td>";
			}
			$line = $row_text;

			array_push( $lines, array( $zone, $line ) );
		}
		//   $data .= "<tr> " . trim($line) . "</tr>";
		sort( $lines );

		foreach ( $lines as $line ) {
			$data .= "<tr>" . $line[1] . "</tr>";
		}
		$rate = 0;
		if ( $total_order_delivered > 0 ) {
			$rate = round( 100 * ( $total_delivery_total - $total_delivery_fee ) / $total_order_delivered );
		}
//		$data .= "<tr><td></td><td></td><td></td><td>" . $total_order_total . "</td><td></td>" .
//		         "<td>" . $total_delivery_total . "</td>" .
//		         "<td>" . $total_delivery_fee . "</td>" .
//		         "<td>" . $rate . "%</td></tr>";
		$data = str_replace( "\r", "", $data );

		$data .= "</table>";

		if ( $count > 0 ) {
			$all_tables .= $data;
		}
	}

	return $all_tables;
}