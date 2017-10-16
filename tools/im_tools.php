<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 21:42
 */
if ( ! defined( STORE_DIR ) ) {
	define( 'STORE_DIR', dirname( dirname( __FILE__ ) ) );
}
require( STORE_DIR . "/wp-config.php" );
require( STORE_DIR . "/im-config.php" );
require( STORE_DIR . "/wp-load.php" );
require( "sql.php" );
require( "wp.php" );
require( "vat.php" );

//$link       = mysql_connect( $servername, $username, $password );
//mysql_set_charset( 'utf8', $link );
//mysql_select_db( $dbname );

$conn = new mysqli( DB_HOST, DB_NAME, DB_PASSWORD, DB_NAME );
mysqli_set_charset( $conn, 'utf8' );

// Check connection
if ( $conn->connect_error ) {
	die( "Connection failed: " . $conn->connect_error );
}

// Logging
function my_log( $msg, $title = '' ) {
	$error_file = STORE_DIR . '/logs/php_error.log';
//    print $error_file;
	$date = date( 'd.m.Y h:i:s' );
	$msg  = print_r( $msg, true );
	$log  = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
}

function uptime_log( $msg, $title = '' ) {
	$error_file = STORE_DIR . '/logs/uptime.log';
	$date       = date( 'd.m.Y h:i:s' );
	$msg        = print_r( $msg, true );
	$log        = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
}


function get_supplier_id( $supplier_name ) {
	return sql_query_single_scalar( 'SELECT id FROM im_suppliers WHERE supplier_name = \'' . $supplier_name . '\'' );
}

// IM_Delivery table
function get_order_id( $delivery_id ) {
	if ( is_numeric( intval( $delivery_id ) ) ) {
		$sql_i = 'SELECT order_id FROM im_delivery WHERE id = ' . $delivery_id;

		return sql_query_single_scalar( $sql_i );
	} else {
		print "Must send a number to get_order_id!";
		print $delivery_id;

		return 0;
	}
}

function get_delivery_total( $delivery_id ) {
	if ( is_numeric( intval( $delivery_id ) ) ) {
		return sql_query_single_scalar( 'SELECT total FROM im_delivery WHERE id = ' . $delivery_id );
	} else {
		print "Must send a number to get_order_id!";
		print $delivery_id;

		return 0;
	}
}

function get_delivery_vat( $delivery_id ) {
	if ( is_numeric( intval( $delivery_id ) ) ) {
		$sql_i = 'SELECT vat FROM im_delivery WHERE id = ' . $delivery_id;

//        my_log(__METHOD__ . ": " . $sql_i, __FILE__);

		return sql_query_single_scalar( $sql_i );
	} else {
		print "Must send a number to get_order_id!";
		print $delivery_id;

		return 0;
	}
}

function get_delivery_id( $order_id ) {
//	print "get_delivery_id";
	if ( is_numeric( $order_id ) ) {
		return sql_query_single_scalar( 'SELECT id FROM im_delivery WHERE order_id = ' . $order_id );
	} else {
		print "Must send a number to get_delivery_id!";

		return 0;
	}
}

function get_supplier_name( $supplier_id ) {
	// my_log("sid=" . $supplier_id);
	if ( is_numeric( $supplier_id ) ) {
		return sql_query_single_scalar( 'SELECT supplier_name FROM im_suppliers WHERE id = ' . $supplier_id );
	} else {
		print "Must send a number to get_supplier_name! " . $supplier_id;

		return 0;
	}
}

function get_supply_status_name( $supplier_id ) {
	if ( is_numeric( $supplier_id ) ) {
		$s = sql_query_single_scalar( 'SELECT status FROM im_supplies WHERE id = ' . $supplier_id );

		// Supply status: 1 = new, 3 = sent, 5 = close, 9 = delete
		$status_names = array( "חדש", "", "נשלח", "", "בוצע", "", "", "", "נמחק" );

		return $status_names[ $s - 1 ];
	} else {
		return "לא ידוע";
	}
}

function get_supplier( $prod_id ) {
	return get_postmeta_field( $prod_id, "supplier_name" );
}

function get_customer_id_by_order_id( $order_id ) {
	return get_postmeta_field( $order_id, "_customer_user" );
}

function get_customer_by_order_id( $order_id ) {
	$first_name = get_postmeta_field( $order_id, '_billing_first_name' );
	$last_name  = get_postmeta_field( $order_id, '_billing_last_name' );
	if ( $first_name == "" and $last_name == "" ) {
		$first_name = get_postmeta_field( $order_id, '_shipping_first_name' );
		$last_name  = get_postmeta_field( $order_id, '_shipping_last_name' );
	}

	return $first_name . " " . $last_name;
}


function set_supplier_name( $prod_ids, $supplier_name ) {
	// my_log($debug_string, __FILE__);
	foreach ( $prod_ids as $prod ) {
//        my_log("set supplier " . $prod, __FILE__);
		set_post_meta_field( $prod, "supplier_name", $supplier_name );
	}
}

function get_prod_id( $order_item_id ) {
//    print "get_prod_id: " . date("h:i:sa");

	$sql2 = 'select woim.meta_value'
	        . ' from wp_woocommerce_order_itemmeta woim'
	        . ' where woim.order_item_id = ' . $order_item_id . ' and woim.`meta_key` = \'_product_id\''
	        . ' ';

	//print $sql2;

	return sql_query_single_scalar( $sql2 );
}

function get_product_id_by_name( $product_name ) {
	global $conn;
	$sql    = "SELECT id FROM im_products WHERE post_title = '" . $product_name . "'";
	$result = mysqli_query( $conn, $sql );
	$row    = mysqli_fetch_assoc( $result );

	return $row["ID"];
}

function get_product_name( $product_id ) {
	$sql = 'SELECT post_title FROM wp_posts WHERE id = ' . $product_id;

	return sql_query_single_scalar( $sql );
}

function get_product_parent( $prod_id ) {
	return sql_query_single_scalar( "SELECT post_parent FROM wp_posts WHERE id = " . $prod_id );
}

function client_price( $prod_id ) {
	return get_postmeta_field( $prod_id, '_price' );
}

function siton_price( $prod_id ) {
	// $price = client_price( $prod_id );
	// my_log (__FILE__, "prod id = " . $prod_id . " price = " . $price);

//    $supplier = get_postmeta_field($prod_id, 'supplier_name');
	// my_log ("supplier = " . $supplier);
	$buy_price = get_buy_price( $prod_id );
	// my_log("buy price = " . $buy_price);
	$price = round( $buy_price * 1.15, 1 );

//    switch ($supplier)
//    {
//        case "עם האדמה":
//            $price = round($price / 1.4 * 1.1, 1);
//            break;
//        case "יבולי בר":
//        case "זינגר":
//        case "אמיר בן יהודה":
//        case "משק שש":
//        default:
//            $buy_price = get_buy_price($prod_id);
//            // my_log("buy price = " . $buy_price);
//            $price = round($buy_price * 1.1, 1);
//
//    }
	// print "siton: buy" . $buy_price . " " . get_product_name($prod_id) . " " . $price . "<br/>";
	return min( $price, get_price( $prod_id ) );
}


function is_basket( $basket_id ) {
	// my_log(__METHOD__, __FILE__);
	$sql = 'SELECT count(product_id) FROM im_baskets WHERE basket_id = ' . $basket_id;
	/// print $sql;

	// my_log(__METHOD__, $sql);

	return sql_query_single_scalar( $sql );
}

function is_bundle( $prod_id ) {
	// my_log(__METHOD__, __FILE__);
	$sql = 'SELECT count(bundle_prod_id) FROM im_bundles WHERE bundle_prod_id = ' . $prod_id;

	// my_log(__METHOD__, $sql);

	return sql_query_single_scalar( $sql );
}

function is_order( $id ) {
//    my_log(__METHOD__, __FILE__);
	$sql = 'SELECT post_type FROM wp_posts WHERE id = ' . $id;

//    my_log(__METHOD__, $sql);

	return sql_query_single_scalar( $sql );
}


function get_basket_date( $basket_id ) {
	$sql = 'SELECT max(date) FROM im_baskets WHERE basket_id = ' . $basket_id;

	$row = sql_query_single_scalar( $sql );

	return substr( $row, 0, 10 );
}

//function show_trans( $customer_id, $from_last_zero = false ) {
//	$sql = 'select date, transaction_amount, transaction_method, transaction_ref, id '
//	       . ' from im_client_accounts where client_id = ' . $customer_id . ' order by date desc ';
//
//	$result = sql_query( $sql );
//
//	$data = "<table id='transactions_table' border=\"1\" ><tr><td>בחר</td><td>תאריך</td><td>סכום</td><td>מע\"ם</td><td>יתרה</td><td>פעולה</td>" .
//	        "<td>תעודת משלוח</td><td>מס הזמנה</td></tr>";
//
//	global $total;
//
//	while ( $row = mysqli_fetch_row( $result ) ) {
//		$line   = "<tr class=\"color2\">";
//		$date   = $row[0];
//		$amount = round($row[1], 2);
//		$total  += $amount;
//		$type   = $row[2];
//		$doc_id = $row[3];
//		$vat    = get_delivery_vat( $doc_id );
//
//		// <input id=\"chk" . $doc_id . "\" class=\"trans_checkbox\" type=\"checkbox\">
//		$line    .= "<td>" . gui_checkbox( "chk" . $doc_id, "trans_checkbox", "", "onchange=\"update_sum()\"" ) . "</td>";
//		$line    .= "<td>" . $date . "</td>";
//		$line    .= "<td>" . $amount . "</td>";
//		$line    .= "<td>" . $vat . "</td>";
//		$balance = balance( $date, $customer_id );
//		$line    .= "<td>" . $balance . "</td>";
//		$line    .= "<td>" . $type . "</td>";
//
//		$delivery_id = $doc_id;
//
//		// Display item name
//		if ( $type == "משלוח" ) {
//			$line     .= "<td><a href=\"../delivery/get-delivery.php?id=" . $doc_id . "\">" . $doc_id . '</a></td>';
//			$order_id = get_order_id( $doc_id );
//			$line     .= "<td>" . $order_id . "</td>";
//			if ( is_numeric( $order_id ) ) {
//				$line .= "<td>" . order_info( $order_id, '_shipping_first_name' ) . "</td>";
//			} else {
//				$line .= "<td></td>";
//			}
//		} else {
//			$line .= "<td>" . $doc_id . "</td><td></td><td></td>";
//		}
//		$line .= "</tr>";
//
//		$data .= trim( $line );
//		if ( $from_last_zero and abs( $balance ) < 2 ) {
//			break;
//		}
//	}
//
//	$data = str_replace( "\r", "", $data );
//
//	$data .= "</table>";
//
//	$total = round( $total, 2 );
//
//	return $data;
//}

function get_basket_content( $basket_id ) {
	// t ;

	$sql = 'SELECT DISTINCT product_id, quantity, id FROM im_baskets WHERE basket_id = ' . $basket_id .
	       ' ORDER BY 3';

	$result = sql_query( $sql );

	$basket_content = "";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$prod_id  = $row[0];
		$quantity = $row[1];

		if ( $quantity <> 1 ) {
			$basket_content .= $quantity . " ";
		}
		$basket_content .= get_product_name( $prod_id ) . ", ";
	}

	return chop( $basket_content, ", " ) . ".";
}

function get_basket_content_array( $basket_id ) {
	$result = array();

	$sql = 'SELECT DISTINCT product_id, quantity, id FROM im_baskets WHERE basket_id = ' . $basket_id .
	       ' ORDER BY 3';

	$sql_result = sql_query( $sql );

	while ( $row = mysqli_fetch_row( $sql_result ) ) {
		$prod_id            = $row[0];
		$quantity           = $row[1];
		$result[ $prod_id ] = $quantity;
	}

	return $result;
}


function debug_time( $message, $previous_time ) {
	$diff  = microtime( true ) - $previous_time;
	$sec   = intval( $diff );
	$micro = $diff - $sec;
	print "<p dir=\"ltr\"> " . $message . " " . $sec . " sec " . $micro . "</p>";

	return microtime( true );
}

function get_site_tools_url( $site_id ) {
	global $conn;

	$sql = "SELECT tools_url FROM im_multisite " .
	       " WHERE id = " . $site_id;

	// print $sql;

	$result = mysqli_query( $conn, $sql );

	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$row = mysqli_fetch_row( $result );

	return $row[0];
}


function get_minimum_order() {
	global $woocommerce;
	global $conn;

	$value = 85;

	$country  = $woocommerce->customer->get_shipping_country();
	$state    = $woocommerce->customer->get_shipping_state();
	$postcode = $woocommerce->customer->get_shipping_postcode();
//    my_log("country " . $country);
//    my_log("state " . $state);
//    my_log("post code " . $postcode);
//    $package = WC()->cart->get_shipping_packages();
//    ob_start();
//    var_dump($package);
//    $result = ob_get_clean();
//    my_log ($result);
	$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
		'destination' => array(
			'country'  => $country,
//            'state'    => $state,
			'postcode' => $postcode,
		),
	) );
//    my_log ("zone_id = " . $zone1->get_id());

	$sql = "SELECT min_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone1->get_id();
//    my_log($sql);
	$result = mysqli_query( $conn, $sql );
	if ( $result ) {
		$row = mysqli_fetch_assoc( $result );
		//    my_log($row["min_order"]);

		if ( is_numeric( $row["min_order"] ) ) {
			$value = $row["min_order"];
		}
	}

	return $value;
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

	$client_id = get_customer_id_by_order_id( $order_id );

	$client_shipping_zone = get_user_meta( $client_id, 'shipping_zone', true );

	if ( strlen( $client_shipping_zone ) > 1 ) {
		return $client_shipping_zone;
	}
	return 0;
}

function order_get_mission_id( $order_id ) {
	$mission_id = get_post_meta( $order_id, 'mission_id', true );

	if ( ! is_numeric( $mission_id ) ) {
		return 0;
	}

	return $mission_id;
}

function get_mission_name( $mission_id ) {
	// Todo: find better way to do this
	sql_query( "set lc_time_names = 'he_IL'" );

	$sql = "select concat(name, ' ', DATE_FORMAT(date, \"%a %d/%m\")) from im_missions where id = $mission_id";

	$name = sql_query_single_scalar( $sql );

	if ( strlen( $name ) < 2 ) {
		$sql  = "select name from im_missions where id = $mission_id";
		$name = sql_query_single_scalar( $sql );
	}

	return $name;
}

function order_get_mission_name( $order_id ) {
	get_mission_name( order_get_mission_id( $order_id ) );

}


function get_zone_from_postcode( $postcode, $country = null ) {
	if ( ! $country or strlen( $country ) < 2 ) {
		$country = "IL";
	}

	$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
		'destination' => array(
			'country'  => $country,
//            'state'    => $state,
			'postcode' => $postcode,
		),
	) )->get_id();
	my_log( "zone: " . $zone1 );

//	print $zone1;

	return $zone1;
}

function zone_get_name( $id ) {
	return sql_query_single_scalar( "SELECT zone_name FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $id );
}


function sunday( $date ) {
	$datetime = new DateTime( $date );
	$interval = new DateInterval( "P" . $datetime->format( "w" ) . "D" );
	$datetime->sub( $interval );

	return $datetime;
}

function get_week( $str_date ) {
	$s = sunday( $str_date );

	return $s->format( 'Y-m-j' );
	// var_dump($s);
	// $d = DateTime::createFromFormat("Y-m-j", $str_date);

	// sunday($d)->format("Y-m-j");
}

function handle_sql_error( $sql ) {
	global $conn;

	print $sql . "<br/>";
	print mysqli_error( $conn );
	die( 1 );
}

function get_meta_field( $post_id, $field_name ) {
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $post_id
	       . " AND meta_key = '" . $field_name . "'";

	// print $sql . "<br>";
	return sql_query_single_scalar( $sql );

}

function get_last_order( $user_id ) {
	global $conn;

	// get last order id
	$sql = " SELECT max(meta.post_id) " .
	       " FROM `wp_posts` posts, wp_postmeta meta" .
	       " where meta.meta_key = '_customer_user'" .
	       " and meta.meta_value = " . $user_id .
	       " and meta.post_id = posts.ID";

	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$row      = mysqli_fetch_row( $result );
	$order_id = $row[0];

	return $order_id;
}

function get_logo_url() {
	global $logo_url;

	return $logo_url;
}

function header_text( $print_logo = true, $close_header = true, $rtl = true ) {
	global $business_info;
	global $logo_url;

	$text = '<html';
	if ( $rtl ) {
		$text .= ' dir="rtl"';
	}
	$text .= '>';
	$text .= '<head>';
	$text .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
	$text .= '<title>';
	$text .= $business_info;
	$text .= '</title>';
	$text .= '<p style="text-align:center;">';
	if ( $print_logo ) {
		$text .= '<img src=' . $logo_url . '>';
	}
	$text .= '</p>';
	$text .= "<style>";
	$text .= file_get_contents( STORE_DIR . "/tools/im.css" );
	$text .= "</style>";
	if ( $close_header ) {
		$text .= '</head>';
	}

	return $text;
}

function multisite_map_get_remote( $prod_id, $remote_site_id ) {
	$sql = "SELECT local_prod_id FROM im_multisite_map WHERE remote_prod_id = " . $prod_id .
	       " AND remote_site_id = " . $remote_site_id;

	return sql_query_single_scalar( $sql );
}

function print_page_header( $display_logo ) {
	global $business_name;
	print '<html dir="rtl">
    <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>' . $business_name . '</title>';
	if ( $display_logo ) {
		print '<center><img src="http://store.im-haadama.co.il/wp-content/uploads/2014/11/cropped-imadama-logo-7x170.jpg"></center>';
	}
	print '</head>';
}

function get_user_name( $id ) {
//    var_dump(get_user_meta($id, 'first_name'));
	return get_user_meta( $id, 'first_name' )[0] . " " . get_user_meta( $id, 'last_name' )[0];

}

function get_product_variations( $prod_id ) {
	$vars = array();

	$args       = array(
		'post_type'   => 'product_variation',
		'post_status' => 'publish',
		'numberposts' => - 1,
		'orderby'     => 'menu_order',
		'order'       => 'asc',
		'post_parent' => $prod_id // $post->ID
	);
	$variations = get_posts( $args );

	foreach ( $variations as $v ) {
		array_push( $vars, $v->ID );
	}

	return $vars;
}

function comma_implode( $array ) {
//	print "<p dir=\"ltr\">";
//	var_dump($array);
//	print "</p>";
	if ( is_null( $array ) ) {
		return "";
	}
	if ( is_bool( $array ) ) {
		return $array;
	}
	if ( ! is_array( $array ) ) {
		return "not array!";
	}
	if ( is_string( $array[0] ) ) {
		return trim( implode( ", ", $array ), ", " );
	}
	$result = "";
	foreach ( $array as $var ) { // not string...
		$result .= $var->name;
		$result .= ", ";
	}

	return rtrim( $result, ", " );
}
?>