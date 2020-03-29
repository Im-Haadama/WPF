<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 21:42
 */

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname(
		__FILE__ ) ) );
}

require_once( FRESH_INCLUDES . "/im-config.php" );
require_once( FRESH_INCLUDES . "/core/fund.php" );
require_once( FRESH_INCLUDES . "/core/data/sql.php" );
require_once( "vat.php" );

if (!defined("IM_CHARSET"))
	define ("IM_CHARSET", 'utf8');


//// Check connection
//if ( $conn->connect_error ) {
//	die( "Connection failed: " . $conn->connect_error );
//}

// Timezone
if (defined('TIMEZONE')){
	date_default_timezone_set( TIMEZONE );
}

function print_time( $prefix = null, $newline = false ) {
	if ( $prefix ) {
		print $prefix . " ";
	}
	$d = new DateTime( "now", new DateTimeZone( "Asia/Jerusalem" ) );
	// var_dump($d);
	print $d->format( "H:m:s.u" );
	if ( $newline ) {
		print "<br/>";
	}

}

// Logging
//function my_log( $msg, $title = '' ) {
//	$error_file = STORE_DIR . '/logs/php_error.log';
////    print $error_file;
//	$date = date( 'd.m.Y h:i:s' );
//	$msg  = print_r( $msg, true );
//	$log  = $date . ": " . $title . "  |  " . $msg . "\n";
//	error_log( $log, 3, $error_file );
//}

function uptime_log( $msg, $title = '' ) {
	$error_file = STORE_DIR . '/logs/uptime.log';
	$date       = date( 'd.m.Y h:i:s' );
	$msg        = print_r( $msg, true );
	$log        = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
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

function get_supply_status_name( $supplier_id ) {
	if ( is_numeric( $supplier_id ) ) {
		$s = sql_query_single_scalar( 'SELECT status FROM im_supplies WHERE id = ' . $supplier_id );

		// Supply status: 1 = new, 3 = sent, 5 = close, 9 = delete
		return get_supply_status( $s );
	} else {
		return "לא ידוע";
	}
}

function get_supplier( $prod_id ) {
	return get_postmeta_field( $prod_id, "supplier_name" );
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
	return sql_query_single_scalar("SELECT id FROM im_products WHERE post_title = '" . $product_name . "'");
}


function get_product_parent( $prod_id ) {
	return sql_query_single_scalar( "SELECT post_parent FROM wp_posts WHERE id = " . $prod_id );
}

function client_price( $prod_id ) {
	return get_postmeta_field( $prod_id, '_price' );
}

function siton_price( $prod_id ) {
	$buy_price = get_buy_price( $prod_id );
	// my_log("buy price = " . $buy_price);
	$price = round( $buy_price * 1.15, 1 );

	return min( $price, get_price( $prod_id ) );
}



function is_order( $id ) {
//    my_log(__METHOD__, __FILE__);
	$sql = 'SELECT post_type FROM wp_posts WHERE id = ' . $id;

//    my_log(__METHOD__, $sql);

	return sql_query_single_scalar( $sql );
}


function deb_ug_time( $message, $previous_time ) {
	$diff  = microtime( true ) - $previous_time;
	$sec   = intval( $diff );
	$micro = $diff - $sec;
	print "<p dir=\"ltr\"> " . $message . " " . $sec . " sec " . $micro . "</p>";

	return microtime( true );
}

function get_site_tools_url( $site_id ) {
	return sql_query_single_scalar("SELECT tools_url FROM im_multisite " .
	       " WHERE id = " . $site_id);

}

function get_meta_field( $post_id, $field_name ) {
	if ( $post_id > 0 ) {
		$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
		       . ' WHERE pm.post_id = ' . $post_id
		       . " AND meta_key = '" . $field_name . "'";

		// print $sql . "<br>";
		return sql_query_single_scalar( $sql );
	}

	return "Bad post id";
}


function get_logo_url() {
	global $logo_url;

	return $logo_url;
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

function get_customer_phone( $user_id ) {
	if ( $user_id > 0 ) {
		return get_meta_field( get_last_order( $user_id ), '_billing_phone' );
	}

	return "Error: bad user_id";
}

// Days...

function get_week( $str_date ) {
	$s = Sunday( $str_date );

	return $s->format( 'Y-m-j' );
}

function week_day( $idx ) {
	switch ( $idx ) {
		case 1:
			return "ראשון";
			break;
		case 2:
			return "שני";
			break;
		case 3:
			return "שלישי";
			break;
		case 4:
			return "רביעי";
			break;
		case 5:
			return "חמישי";
			break;
		case 6:
			return "שישי";
			break;
	}
}

function get_day_letter( $day ) {
	switch ( $day ) {
		case 0:
			return 'א';
		case 1:
			return 'ב';
		case 2:
			return 'ג';
		case 3:
			return 'ד';
		case 4:
			return 'ה';
		case 5:
			return 'ו';
	}

	return "Error";
}

function get_letter_day( $letter ) {
	switch ( $letter ) {
		case 'א':
			return 0;
		case 'ב':
			return 1;
		case 'ג':
			return 2;
		case 'ד':
			return 3;
		case 'ה':
			return 4;
		case 'ו':
			return 5;
	}

	return "Error";
}



if (!function_exists('customer_type_name')) {
function customer_type_name( $client_id ) {
	$key = get_user_meta( $client_id, '_client_type', true );
	// print "YY" . $key . "<br/>";

	if ( is_null( $key ) ) {
		return 0;
	}

	return $key;
	// return sql_query_single_scalar( "SELECT name FROM im_client_types WHERE type = " . quote_text( $key ) );
}
}
function valid_key( $key ) {
	$valid = sql_query_single_scalar( "SELECT timestamp >
          DATE_SUB(now(), INTERVAL 10 MINUTE) FROM im_auth WHERE dynamic_key = '" . $key . "'" );

	// print $valid;
	return ( $valid == 1 ? 1 : 0 );
}


function get_document_type_name( $type ) {
	$names = array( "", "הזמנה", "תעודת משלוח", "זיכוי", "חשבונית" );

	if ( isset( $type ) and isset( $names[ $type ] ) ) {
		return $names[ $type ];
	}

	return "not set" . isset( $type ) ? $type : "null";
}

?>
