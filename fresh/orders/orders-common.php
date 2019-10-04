<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/10/15
 * Time: 08:00
 */
//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/niver/fund.php" );
require_once( TOOLS_DIR . "/catalog/bundles.php" );
require_once( TOOLS_DIR . '/maps/build-path.php' );
require_once( TOOLS_DIR . '/account/gui.php' );
require_once( TOOLS_DIR . '/account/account.php' );
require_once( TOOLS_DIR . '/delivery/delivery.php' );
require_once( TOOLS_DIR . '/orders/Order.php' );
require_once( TOOLS_DIR . "/catalog/Basket.php" );
require_once( TOOLS_DIR . '/invoice4u/invoice.php' );
require_once( TOOLS_DIR . '/multi-site/imMulti-site.php' );

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

function user_dislike( $user_id, $prod_id ) {
	if ( ! $user_id ) {
		return false;
	}
	$sql = 'SELECT id FROM im_client_dislike WHERE client_id=' . $user_id .
	       ' AND dislike_prod_id=' . $prod_id;

	$v = sql_query_single_scalar( $sql );

	// print $sql . " " . $v . "<br/>";

	// print $v;
	return $v;
}


// $multiply is the number of ordered baskets or 1 for ordinary item.
function orders_per_item( $prod_id, $multiply, $short = false, $include_basket = false, $include_bundle = false, $just_total = false, $month = null ) {
	// my_log( "prod_id=" . $prod_id, __METHOD__ );

	$sql = 'select woi.order_item_id, order_id'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
	       . ' where order_id in';

	if ( ! $month )
		$sql .= '(select order_id from im_need_orders) ';
	else {
		$year = date( 'Y' );
		if ( $month >= date( 'n' ) ) {
			$year --;
		}
		$sql .= "(SELECT id FROM wp_posts WHERE post_date like '" . $year . "-" . sprintf( "%02s", $month ) . "-%'" .
		        " and post_status = 'wc-completed')";
//		print $sql;
//		die (1);
	}

	$baskets = null;
	if ( $include_basket ) {
		$sql1    = "select basket_id from im_baskets where product_id = $prod_id";
		$baskets = sql_query_array_scalar( $sql1 );
	}
	$bundles = null;
	if ( $include_bundle ) {
		$sql2    = "select bundle_prod_id from im_bundles where prod_id = " . $prod_id;
		$bundles = sql_query_array_scalar( $sql2 );
		// if ($bundles) var_dump($bundles);
	}
	$sql .= ' and woi.order_item_id = woim.order_item_id '
	        . ' and (woim.meta_key = \'_product_id\' or woim.meta_key = \'_variation_id\')
	         and woim.meta_value in (' . $prod_id;
	if ( $baskets ) {
		$sql .= ", " . comma_implode( $baskets );
	}
	if ( $bundles ) {
		$sql .= ", " . comma_implode( $bundles );
	}
	$sql .= ")";

//	print $sql . "<br/>";

	// my_log( $sql, "get-orders-per-item.php" );

	$result = sql_query( $sql);
	$lines = "";
	$total_quantity = 0;

	while ( $row = mysqli_fetch_row( $result ) ) {
		$order_item_id = $row[0];
		$order_id      = $row[1];
		$quantity      = get_order_itemmeta( $order_item_id, '_qty' );
		// consider quantity in the basket or bundle
		$pid = get_order_itemmeta( $order_item_id, '_product_id' );
		if ( is_bundle( $pid ) ) {
			$b        = Bundle::CreateFromBundleProd( $pid );
			$quantity *= $b->GetQuantity();
		} else
		if ( is_basket( $pid ) ) {
			$b        = new Basket( $pid );
			$quantity *= $b->GetQuantity( $prod_id );
		}
		$first_name    = get_postmeta_field( $order_id, '_shipping_first_name' );
		$last_name     = get_postmeta_field( $order_id, '_shipping_last_name' );

		$total_quantity += $quantity;

		if ( $short ) {
//			print "short $first_name<br/>";
			$lines .= $quantity . " " . $last_name . ", ";
		} else {
//			print "long<br/>";
			$line  = "<tr>" . "<td> " . gui_hyperlink( $order_id, "get-order.php?order_id=" . $order_id ) . "</td>";
			$line .= "<td>" . $quantity * $multiply . "</td><td>" . $first_name . "</td><td>" . $last_name . "</td></tr>";
			$lines .= $line;
		}
	}
	if ( $just_total ) {
		return $total_quantity;
	}
	if ( $short and $total_quantity ) {
		$lines = $total_quantity . ": " . rtrim( $lines, ", ");
	}
	return $lines;
}

function orders_create_subs() {
	die( "not implemented" );
//	print "creating orders for subscriptions<br/>";
//	$sql = "SELECT client, basket, weeks, unfold_basket FROM im_subscriptions ";
//
//	$result = sql_query_array( $sql );
//
//	my_log( "creating subscriptions orders", __FILE__ );
//	foreach ( $result as $row ) {
//		$user_id = $row[0];
//
//		print get_user_name( $user_id ) . ": last order on- " . order_get_last( $user_id ) . "<br/>";
//		$diff = date_diff( new DateTime( order_get_last( $user_id ) ), new DateTime() );
//
//		// print get_user_name($user_id) . " " . $diff->days . "<br/>";
//		if ( $diff->days < ( $row[2] * 7 - 3 ) ) {
//			continue;
//		}
//
//		$product_id = $row[1];
//		print "creating $user_id  $product_id, $row[3]<br/>";
//
//		my_log( "create order: product = " . $product_id, __METHOD__ );
//
//		$order = wc_create_order();
//		$order->set_created_via( "מנויים" );
//		order_add_product( $order, $product_id, 1, $row[3], $user_id );
//		$order_id = $order->get_id();
//
//		$postcode = get_user_meta( $user_id, 'shipping_postcode', true );
//		$package  = array( 'destination' => array( 'country' => 'IL', 'postcode' => $postcode ) );
//		$zone     = WC_Shipping_Zones::get_zone_matching_package( $package );
//		$method   = WC_Shipping_Zones::get_shipping_method( $zone->get_id() );
//
////		$shipping = new $method;
//
//		$rate           = new WC_Shipping_Rate();
//		$rate->id       = 'flat_rate';
//		$rate->label    = 'משלוח';
////		$rate->cost     = $shipping->cost;
//		$rate->calc_tax = 'per_order';
//
//		$order->add_shipping( $rate );
//
//		my_log( "add_product" );
//		$order->calculate_totals();
//
//		my_log( "totals" );
//		// assign the order to the current user
//		order_set_customer_id( $order->get_id(), $user_id );
//		// payment_complete
//		$order->payment_complete();
//
//		// billing info
//		print $order_id;
//		foreach (
//			array(
//				'billing_first_name',
//				'billing_last_name',
//				'billing_phone',
//				'billing_address_1',
//				'billing_address_2',
//				'shipping_first_name',
//				'shipping_last_name',
//				'shipping_address_1',
//				'shipping_address_2',
//				'shipping_city',
//				'shipping_postcode'
//			) as $key
//		) {
////	    	print $key . " ";
//			$values = get_user_meta( $user_id, $key );
////		    print $key . " " . $values[0] . "<br/>";
//			update_post_meta( $order_id, "_" . $key, $values[0] );
//		}
//	}
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
		$o       = new Order( $order_id );
		$address = "";
		foreach ( array( '_shipping_address_1', '_shipping_city' ) as $field ) {
			$address .= $o->getOrderInfo( $field ) . " ";
		}
		if ( strlen( $address ) > 4 ) {
			return $address;
		}
		// Take the address from the client;
		$client_id = $o->getCustomerId();
		// print $client_id . " " . $order_id;
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

	if ( $done > 0 or $new > 0 ) {
		return false;
	}

	return true;
}


/// print "התקבלה הזמנה של סל " . get_product_name($basket_id) . " עבור " . get_customer_name($user_id);

function copy_meta_field( $source, $destination, $meta_key ) {
	set_post_meta_field( $destination, $meta_key, get_meta_field( $source, $meta_key ) );
}

function set_order_itemmeta( $order_item_id, $meta_key, $meta_value ) {
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
	debug_time_log( "start" );

	// print "s=" . $statuses;

	global $order_header_fields;
	global $invoice_user;
	global $invoice_password;

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
	debug_time_log( "1" );

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
		$result                = sql_query( $sql );
		$total_delivery_total  = 0;
		$total_order_total     = 0;
		$total_order_delivered = 0;
		$total_delivery_fee    = 0;
		$lines                 = array();

		if ( ! $result ) {
			continue;
		}

		$count = 0;

		debug_time_log( "before loop" );
		while ( $row = mysqli_fetch_row( $result ) ) {
			debug_time_log( "after fetch" );
			$count ++;
			$order_id = $row[0];
			$order    = new Order( $order_id );

			$customer_id = $order->getCustomerId( $order_id );

			$line            = $empty_line;
			$invoice_user_id = get_user_meta( $customer_id, 'invoice_id', 1 );

			if ( $invoice_user_id ) {
				$line [ OrderFields::line_select ] = gui_checkbox( "chk_" . $order_id, "select_order_" . $status );
			} else {
				$line [ OrderFields::line_select ] = gui_hyperlink( "לקוח חדש", "../account/new-customer.php?order_id=" . $order_id );
			}

			debug_time_log( "a1" );
			$line[ OrderFields::type ]         = order_get_shipping( $order_id );

			// display order_id with link to display it.
			// 1) order ID with link to the order
			$mission_id = order_get_mission_id( $order_id );
			// print $order_id. " ". $mission . "<br/>";

			$args = array();
			$args["events"] = "onchange=\"mission_changed(" . $order_id . ")\"";
			$line[ OrderFields::mission ]  = gui_select_mission( "mis_" . $order_id, $mission_id,  $args);
			$line[ OrderFields::order_id ] = gui_hyperlink( $order_id, "/fresh/orders/get-order.php?order_id=" . $order_id );

			// 2) Customer name with link to his deliveries
			$line[ OrderFields::customer ] = gui_hyperlink( get_customer_name( $customer_id ), ImMultiSite::LocalSiteTools() .
			                                                                                   "/account/get-customer-account.php?customer_id=" . $customer_id );


			$line[ OrderFields::recipient ] = get_postmeta_field( $order_id, '_shipping_first_name' ) . ' ' .
			                                  get_postmeta_field( $order_id, '_shipping_last_name' );

			debug_time_log( "middle" );

			$order_total = 0;
			// 3) Order total
			if ( $show_fields[ OrderFields::total_order ] ) {
				$order_total = $order->GetTotal();
				// get_postmeta_field( $order_id, '_order_total' );
				$line[ OrderFields::total_order ] = $order_total;
				$total_order_total                += $order_total;
				debug_time_log( "total" );
			}

			// 4) Delivery note
			$delivery_id = get_delivery_id( $order_id );

			if ( $delivery_id > 0 ) {
				$delivery                           = new Delivery( $delivery_id );
				$line[ OrderFields::delivery_note ] = gui_hyperlink( $delivery_id, "/fresh/delivery/get-delivery.php?id=" . $delivery_id );
				//if ( $delivery_id > 0 ) {
				if ( isset( $orders_total ) ) {
					$line[ OrderFields::total_order ] = $order_total;
				} // $delivery->Price();
					$line[ OrderFields::delivery_fee ] = $delivery->DeliveryFee();
					$percent                           = "";
					if ( ( $order_total - $delivery->DeliveryFee() ) > 0 ) {
						$percent = round( 100 * ( $delivery->Price() - $delivery->DeliveryFee() ) / ( $order_total - $delivery->DeliveryFee() ), 0 ) . "%";
					}
					$line[ OrderFields::percentage ] = $percent;
					$total_delivery_total            += $delivery->Price();
				$total_delivery_fee                  = $delivery->DeliveryFee();
					$total_order_delivered           += $order_total;
				if ( $delivery->isDraft() ) {
					$line [ OrderFields::line_select ] = "טיוטא";
				}
				//	}
			} else {
				$line[ OrderFields::delivery_note ] = gui_hyperlink( "צור", "../delivery/create-delivery.php?order_id=" . $order_id );
				$line[ OrderFields::percentage ]    = gui_hyperlink( "בטל", $_SERVER['PHP_SELF'] . "?operation=cancel_order&id=" . $order_id );
				$total_delivery_fee                 = order_get_shipping_fee( $order_id );
			}
			$line[ OrderFields::city ]         = $order->getOrderInfo( '_shipping_city' );
			$line[ OrderFields::payment_type ] = get_payment_method_name( $customer_id );
			if ( current_user_can( "show_business_info" ) ) {
				$line[ OrderFields::good_costs ] = $order->GetBuyTotal();
				$line[ OrderFields::margin ]     = round( ( $line[ OrderFields::total_order ] - $line[ OrderFields::good_costs ] ), 0 );
			}
			$line[ OrderFields::delivery_fee ] = $total_delivery_fee; //

			array_push( $rows, $line );
			debug_time_log( "loop end" );
		}

		//   $data .= "<tr> " . trim($line) . "</tr>";

		debug_time_log( "before sort" );

		// sort( $lines );

		debug_time_log( "2" );

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
			// gui_table( $rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null, $links = null)
			$data       .= gui_table( $rows, $status, true, true, $sums, null, null, $show_fields );
			$all_tables .= $data;
		}
	}

	debug_time_log( "end" );

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
			ImMultiSite::LocalSiteTools() . "/orders/get-order.php?order_id=" . $order_id ) );

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

	return gui_table_args( $table );
}

//function order_get_zone( $order_id ) {
////	print "order id = " . $order_id . "<br/>";
//	my_log( __METHOD__ . " order_id " . $order_id );
//	$country = get_postmeta_field( $order_id, '_shipping_country' );
//	// print "country = " . $country . "<br/>";
//
//	$postcode = get_postmeta_field( $order_id, '_shipping_postcode' );;
//	my_log( "postcode = " . $postcode );
//	// print "postcode = " . $postcode . "<br/>";
//
//
//	$zone = get_zone_from_postcode( $postcode, $country );
//	if ( zone_get_name( $zone ) != 'N/A' ) {
//		return $zone;
//	}
//
//	$client_id = order_get_customer_id( $order_id );
//
//	$client_shipping_zone = get_user_meta( $client_id, 'shipping_zone', true );
//
//	if ( strlen( $client_shipping_zone ) > 1 ) {
//		return $client_shipping_zone;
//	}
//
//	return 0;
//}

function show_category_all( $sale, $text, $fresh = false, $inv = false, $customer_type = "regular", $month = null, $args = null ) {
//	print "inventory: " . $inventory . "<br/>";
//	print "fresh: " . $fresh . "<br/>";
	$result = "";
	if ( $fresh ) {
		$categs = explode( ",", info_get( "fresh" ) );
//		 var_dump($categs);
	} else {
		$sql    = "SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'product_cat'";
		$categs = sql_query_array_scalar( $sql );
	}
	foreach ( $categs as $categ ) {
//		print get_term($categ)->name . "<br/>";
		$result .= show_category_by_id( $categ, $sale, $text, $customer_type, $inv, $month, $args );
	}

	return $result;
}

function show_category_by_id( $term_id, $sale = false, $text = false, $customer_type = "regular", $inventory = false, $month = null, $args = null )
{
	$just_pricelist = GetArg($args, "just_pricelist", false);

	$result   = "";
	//print "inventory: " . $inventory . "<br/>";

	$the_term = get_term( $term_id );

	$result .= gui_header( 2, $the_term->name );

	$regular = ($customer_type == "regular");
	$header =null;
	$table = array();

	if ( $sale ) {
		$header = array( "", "מוצר", "מחיר מוזל", "מחיר רגיל", "כמות", "סה\"כ" );
	} else {
		$header = array( "", "מוצר" ) ;
		if ( ! $month )
			array_push( $header, "מחיר", gui_hyperlink( "מחיר לכמות", "", "" ), "כמות", "סה\"כ" );
		else
			array_push( $header, "מדד זמינות"  );
	}

	if ( $month == "all" ) {
		$header =
			array(
				"",
				"מוצר",
				"Jan",
				"Feb",
				"Mar",
				"Apr",
				"May",
				"Jun",
				"Jul",
				"Aug",
				"Sep",
				"Oct",
				"Nov",
				"Dec"

		);
	}

	if ( $inventory ) {
		$header = array( "", "שם מוצר", "מחיר עלות", "כמות במלאי", "תאריך עדכון", "דוח תנועות" ) ;
//		array_push( $table[0], "מלאי" );
//		array_push( $table[0], "תאריך עדכון" );
//		array_push( $table[0], "מוזמנים" );
	}

	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 10000,
		'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term_id ) ),
		'orderby'        => 'name',
		'order'          => 'ASC'
	);

	if ( $month ) {
		$args['post_status'] = array( 'draft', 'publish' );
	}

//	print gui_header(1, $term_id) . "<br/>";
	// var_dump($args);
	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) {
		$loop->the_post();
		global $product;
		$prod_id = $loop->post->ID;
//		print $prod_id . " " . get_product_name($prod_id) . "<br/>";
		if ( ! $month and ! $product->get_regular_price() ) {
//			print "skipping " . $prod_id . "<br/>";
			continue;
		}
		$line = product_line( $prod_id, false, $sale, $customer_type, $inventory, $term_id, $month );
		$price = get_price($prod_id);
		array_push( $table, array($price, $line) );
	}

//	var_dump($table);
	sort($table); // Sort by price.
	for ($i = 0; $i < count($table); $i++)
	{
		$table[$i] = $table[$i][1];
	}
	$args = array();
	$args["show_cols"] = array();
	if (! $regular){
		$args["show_cols"][3] = false; // Hide quantity price
	}
	if ($just_pricelist){
		$args["show_cols"][4] = false;
		$args["show_cols"][5] = false;
	}

	if ( $text ) {
		foreach ($table as $line)
		{
			if (is_array($line))
				$result .= $line[1] . " " . $line[2] ."<br/>";
		}
		return $result;
	} else {
		array_unshift($table, $header);
		$result .= gui_table_args( $table, "table_" . $term_id, $args );
	}

	if ( $inventory ) {
		$result .= gui_button( "btn_save_inv" . $term_id, "save_inv(" . $term_id . ")", "שמור מלאי" );
	}

//	print "result = " . $result . "<br/>";
	return $result;
}

function product_line( $prod_id, $text, $sale, $customer_type, $inv, $term_id, $month = null ) {
	$line     = array();
	$img_size = 40;

//	print "ct=" . $customer_type . "<br/>";
	$p = new Product( $prod_id );
	if ( $text ) {
		$line = get_product_name( $prod_id ) . " - " . get_price_by_type( $prod_id, $customer_type ) . "<br/>";
		// print "line = " . $line . "<br/>";
		// $result .= $line;
		return $line;
	}
	if ( has_post_thumbnail( $prod_id ) ) {
		array_push( $line, get_the_post_thumbnail( $prod_id, array( $img_size, $img_size ) ) );
	} else {
		array_push( $line, '<img src="' . wc_placeholder_img_src() . '" alt="Placeholder" width="' . $img_size . 'px" height="'
		                   . $img_size . 'px" />' );
	}
	array_push( $line, get_product_name( $prod_id ) );

	if ( $month ) {
		if ( $month == "all" )
			for ( $i = 1; $i <= 12; $i ++ ) {
				array_push( $line, month_availability( $prod_id, $i ) );
			}
		else {
			$a = month_availability( $prod_id, $month );
			if ( $a == "N/A" ) {
				return "";
			}
			array_push( $line, $a );

			return $line;
		}
	}
	if ( $sale ) {
		array_push( $line, gui_label( "prc_" . $prod_id, $p->getSalePrice() ) );
		array_push( $line, gui_label( "vpr_" . $prod_id, $p->getRegularPrice() ) );
	} else {
		if ( $inv ) {
			array_push( $line, gui_label( "buy_" . $prod_id, $p->getBuyPrice() ) );

		} else {
			if ( ! $month ) {
				array_push( $line, gui_label( "prc_" . $prod_id, $p->getPrice($customer_type) ) );
				$q_price = get_price_by_type( $prod_id, null, 8 );
				//			if ( is_numeric( get_buy_price( $prod_id ) ) ) {
				//				$q_price = min( round( get_buy_price( $prod_id ) * 1.25 ), $product->get_price() );
				//			}
				array_push( $line, gui_label( "vpr_" . $prod_id, $q_price ) );
			}
		}
	}
	if ( ! $inv and ! $month) {
		array_push( $line, gui_input( "qua_" . $prod_id, "0", array( 'onchange="calc_line(this)"' ) ) );
		array_push( $line, gui_label( "tot_" . $prod_id, '' ) );
	}
	if ( $inv ) {
		array_push( $line, gui_input( "term_" . $term_id, $p->getStock(), "", "inv_" . $prod_id ) );
		array_push( $line, gui_label( "term_" . $term_id, $p->getStockDate() ) );
		array_push( $line, gui_hyperlink( "דוח", "../delivery/report.php?prod_id=" . $prod_id ) );
//		array_push( $line, gui_label( "ord_" . $term_id, $p->getOrderedDetails() ) );
	}

	return $line;
}

function get_order_itemmeta( $order_item_id, $meta_key ) {
	if ( is_array( $order_item_id ) ) {
		$sql = "SELECT sum(meta_value) FROM wp_woocommerce_order_itemmeta "
		       . ' WHERE order_item_id IN ( ' . comma_implode( $order_item_id ) . ") "
		       . ' AND meta_key = \'' . escape_string( $meta_key ) . '\'';

		return sql_query_single_scalar( $sql );
	}
	if ( is_numeric( $order_item_id ) ) {
		$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
		        . ' WHERE order_item_id = ' . $order_item_id
		        . ' AND meta_key = \'' . escape_string( $meta_key ) . '\''
		        . ' ';

		return sql_query_single_scalar( $sql2 );
	}

	return - 1;
}

function month_availability( $prod_id, $month ) {
	$year = date( 'Y' );
	if ( $month >= date( 'n' ) ) {
		$year --;
	}

	static $orders_per_month = null;

	if ( ! $orders_per_month ) {
		$orders_per_month = array();
	}

	if ( ! isset( $orders_per_month[ $month ] ) ) {
		$sql                        = "select id " .
		                              " from im_delivery where order_id in (select id from wp_posts " .
		                              " where post_date like '" . $year . "-" . sprintf( "%02s", $month ) . "-%')";
		$orders_per_month[ $month ] = sql_query_array_scalar( $sql );
	}

	// SELECT id, post_date, post_status FROM wp_posts WHERE post_date like '2019-04-%' and post_status = 'wc-completed'


	$result = sql_query( "select sum(quantity), sum(quantity_ordered) " .
	                     " from im_delivery_lines " .
	                     " where prod_id  = " . $prod_id .
	                     " and delivery_id in (" . comma_implode( $orders_per_month[ $month ] ) . ")" );

	if ( ! $result ) {
		die( 1 );
	}
	$row      = sql_fetch_row( $result );
	$supplied = $row[0];
	$ordered  = $row[1];

	if ( ! $ordered ) {
		return "N/A";
	}

	return round( $supplied / $ordered, 1);

}