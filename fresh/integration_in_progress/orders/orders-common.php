<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/10/15
 * Time: 08:00
 */



if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) );
}

require_once( FRESH_INCLUDES . "/core/fund.php" );
require_once( FRESH_INCLUDES . "/catalog/bundles.php" );
require_once( FRESH_INCLUDES . '/routes/maps/build-path.php' );
require_once( FRESH_INCLUDES . '/account/gui.php' );
require_once( FRESH_INCLUDES . '/account/account.php' );
require_once( FRESH_INCLUDES . '/delivery/delivery.php' );
require_once( FRESH_INCLUDES . '/orders/Order.php' );
require_once( FRESH_INCLUDES . '/catalog/Basket.php' );
require_once( FRESH_INCLUDES . '/invoice4u/invoice.php' );
require_once( FRESH_INCLUDES . '/multi-site/imMulti-site.php' );
require_once( FRESH_INCLUDES . '/core/web.php' );
require_once( FRESH_INCLUDES . '/routes/gui.php');


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

/// print "התקבלה הזמנה של סל " . get_product_name($basket_id) . " עבור " . get_customer_name($user_id);

function copy_meta_field( $source, $destination, $meta_key ) {
	set_post_meta_field( $destination, $meta_key, GetMetaField( $source, $meta_key ) );
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
		array_push( $order_clients, Core_Html::GuiHyperlink( get_postmeta_field( $order_id, '_shipping_first_name' ),
			Core_Db_MultiSite::LocalSiteTools() . "/fresh/orders/get-order.php?order_id=" . $order_id ) );

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


/**
 * @param $sale
 * @param $text
 * @param bool $fresh
 * @param bool $inv
 * @param string $customer_type
 * @param null $month
 * @param null $args
 * @deprecated use ShowCategoryAll
 *
 * @return string
 */
function show_category_all($sale, $text, $fresh = false, $inv = false, $customer_type = "regular", $month = null, $args = null )
{
	$args["sale"] = $sale;
	$args["text"] = $text;
	$args["fresh"] = $fresh;
	$args["inv"] = $inv;
	$args["customer_type"] = $customer_type;
	$args["month"] = $month;
	return ShowCategoryAll( $args );
}

function ShowCategoryAll( $args )
{
	$sale = GetArg($args, "sale", false);
	$text = GetArg($args, "text", false);
	$fresh = GetArg($args, "fresh", false);
	$inv = GetArg($args, "inventory", false);
	$customer_type = GetArg($args, "customer_type", "regular");
	$month = GetArg($args, "month", null);

	$result = "";
	if ( $fresh ) {
		$categs = explode( ",", info_get( "fresh" ) );
	} else {
		$sql    = "SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'product_cat'";
		$categs = sql_query_array_scalar( $sql );
	}
	foreach ( $categs as $categ ) {
		$result .= show_category_by_id( $categ, $sale, $text, $customer_type, $inv, $month, $args );
	}

	return $result;
}

function show_category_by_id( $term_id, $sale = false, $text = false, $customer_type = "regular", $inventory = false, $month = null, $args = null )
{
	$not_available = GetArg($args, "not_available", false);
	$just_pricelist = GetArg($args, "just_pricelist", false);

	$the_term = get_term( $term_id );

	$result   = "";
	$result .= Core_Html::gui_header( 2, $the_term->name );

	$regular = ($customer_type == "regular");
	$header =null;
	$table = array();

	if ( $sale ) {
		$header = array( "", "מוצר", "מחיר מוזל", "מחיר רגיל", "כמות", "סה\"כ" );
	} else {
		$header = array( "", "מוצר" ) ;
		if ( ! $month )
			array_push( $header, "מחיר", Core_Html::GuiHyperlink( "מחיר לכמות", "", "" ), "כמות", "סה\"כ" );
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
	}

	$query_args = array(
		'post_type'      => 'product',
		'posts_per_page' => 10000,
		'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term_id ) ),
		'orderby'        => 'name',
		'order'          => 'ASC'
	);

	if ( $month ) {
		$args['post_status'] = array( 'draft', 'publish' );
	}

//	print Core_Html::gui_header(1, $term_id) . "<br/>";
	// var_dump($args);
	$loop = new WP_Query( $query_args );
	while ( $loop->have_posts() ) {
		$loop->the_post();
		global $product;
		$prod_id = $loop->post->ID;
//		print $prod_id . " " . get_product_name($prod_id) . "<br/>";
		if ( ! $month and ! $product->get_regular_price() ) {
//			print "skipping " . $prod_id . "<br/>";
			continue;
		}
		if ($not_available and count(alternatives($prod_id))) continue;

		$line = product_line( $prod_id, false, $sale, $customer_type, $inventory, $term_id, $month );
		$table[$prod_id] = $line;
	}
	if (! count($table)) return null;

//	sort($table); // Sort by price.
//	for ($i = 0; $i < count($table); $i++)
//	{
//		$table[$i] = $table[$i][1];
//	}
//	$args["show_cols"] = array();
	$args["prepare"] = false;
	if (! $regular){
		$args["show_cols"][3] = false; // Hide quantity price
	}
//	if ($just_pricelist){
//		$args["show_cols"][4] = false;
//		$args["show_cols"][5] = false;
//	}

	if ( $text ) {
		foreach ($table as $line)
		{
			if (is_array($line))
				$result .= $line[1] . " " . $line[2] ."<br/>";
		}
		return $result;
	} else {
		$args["checkbox_class"] = "not_avail_" . $term_id;
		$result .= gui_table_args( $table, "table_" . $term_id, $args );
	}

	if ( $inventory ) $result .= Core_Html::GuiButton( "btn_save_inv" . $term_id, "save_inv(term_" . $term_id . ")", "שמור מלאי" );
	if ($not_available) $result .= Core_Html::GuiButton( "btn_draft" . $term_id, "draft_products(" . QuoteText($args["checkbox_class"]) . ")", "draft products" );

	return $result;
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
	                     " and delivery_id in (" . CommaImplode( $orders_per_month[ $month ] ) . ")" );

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
