<?php
// Display errors and messages only on develop server
if ( ! isset( $_SERVER['SERVER_NAME'] ) or ( $_SERVER['SERVER_NAME'] == "127.0.0.1" ) ) {
	// print "debug";
	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );
}
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 21:42
 */

if ( ! defined( "STORE_DIR" ) ) {
	define( 'STORE_DIR', dirname( dirname( __FILE__ ) ) );
}

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( __FILE__ ) );
}

require_once( "im_tools_light.php" );

require_once( STORE_DIR . "/wp-config.php" );
require_once( STORE_DIR . "/wp-load.php" );
require_once( "wp.php" );

function order_get_shipping_fee( $order_id ) {
	$order = wc_get_order( $order_id );
	foreach ( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ) {
		return $shipping_item_obj->get_total();
		// var_dump($shipping_item_obj); print "<br/>";
		// return $shipping_item_obj["price"];
	}
}

function order_get_shipping( $order_id ) {
	$debug         = false;
	$shipping_info = "";
	$order         = wc_get_order( $order_id );
	if ( ! $order ) {
		return 0;
	}
	foreach ( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ) {
		// if (get_class($shipping_item_obj) == "WC_Order_Item_Shipping") {
		// var_dump($shipping_item_obj); print "<br/>";
		$shipping_info = $shipping_item_obj['name'];
		if ( $debug ) {
			print $shipping_info;
		}

		return $shipping_info;
		//	break;

	}
}

function order_get_mission_id( $order_id, $debug = false ) {
//	print "aaa";
	global $conn;
//if ($order_id == 8097) $debug = true;
	if ( ! is_numeric( $order_id ) ) {
		print "Bad order id: $order_id<br/>";
		die( 1 );
	}
	$mission = get_post_meta( $order_id, 'mission_id', true );
	if ( $debug ) {
		var_dump( $mission );
		print "<br/>";
	}
	if ( is_array( $mission ) ) {
		$mission_id = $mission[0];
	} else {
		$mission_id = $mission;
	}
//	if ( $debug or ( strlen( $mission_id ) < 1 ) ) {
//
//		$shipping_info = order_get_shipping( $order_id );
//		$sql = "SELECT path_code FROM im_mission_methods WHERE method = '" . mysqli_real_escape_string( $conn, $shipping_info ) . "'";
//		// print $sql;
//		$path_code = sql_query_single_scalar( $sql );
//
//		if ( $debug ) {
//			print "path code: " . $path_code . "<br/>";
//		}
//		$mission_id = sql_query_single_scalar( "SELECT min(id) FROM im_missions WHERE path_code = '" . $path_code . "'" .
//		                                       " AND date >= curdate()" );
//		if ( $debug )
//			print "mission_id: " . $mission_id . "<br/>";
//		update_post_meta( $order_id, 'mission_id', $mission_id );
//	}
	if ( ! is_numeric( $mission_id ) ) {
		return 0;
	}

	return $mission_id;
}

function order_set_mission_id( $order_id, $mission_id ) {
	set_post_meta_field( $order_id, "mission_id", $mission_id );
}

function get_client_type( $id ) {
	// print "meta: " . $meta . "<br/>";
	return get_user_meta( $id, "_client_type", true );
}


function get_mission_name( $mission_id ) {
	// Todo: find better way to do this
	sql_query( "set lc_time_names = 'he_IL'" );
	if ( ! is_numeric( $mission_id ) ) {
		return $mission_id;
	}

	$sql  = "select ifnull(concat(name, ' ', DATE_FORMAT(date, \"%a %d/%m\")), name) from im_missions where id = $mission_id";
	$name = sql_query_single_scalar( $sql );

	return $name;
}

function order_get_mission_name( $order_id, $debug = false ) {
	return get_mission_name( order_get_mission_id( $order_id, $debug ) );
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

function get_user_name( $id ) {
//    var_dump(get_user_meta($id, 'first_name'));
	return get_user_meta( $id, 'first_name' )[0] . " " . get_user_meta( $id, 'last_name' )[0];
}

function get_user_address( $user_id ) {
	$u = $user_id + 0;
	if ( is_numeric( $u ) ) {
		return get_user_meta( $user_id, 'shipping_address_1', true ) . " " .
		       get_user_meta( $user_id, 'shipping_city', true );
	}
	print "bad user id: " . $user_id . "<br/>";
	die(1);
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

function random_str( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
	$pieces = [];
	$max    = mb_strlen( $keyspace, '8bit' ) - 1;
	for ( $i = 0; $i < $length; ++ $i ) {
		$pieces [] = $keyspace[ random_int( 0, $max )];
	}

	return implode( '', $pieces );
}

function order_get_customer_id( $order_id ) {
	return get_postmeta_field( $order_id, "_customer_user" );
}

function get_key() {
	$interval      = " INTERVAL 10 MINUTE ";
	$half_interval = " INTERVAL 5 MINUTE ";
	$u             = wp_get_current_user();
	if ( ! ( $u->ID > 0 ) )
		return null;

	$key = sql_query_single_scalar( "select dynamic_key from im_auth where user_id = " . $u->ID .
	                                " and timestamp > DATE_SUB(now(), $half_interval)");

	// If we have a valid key, return it
	if ( strlen( $key ) > 10 ) {
		return $key;
	}
	// Delete old keys
	sql_query( "delete from im_auth where timestamp < DATE_SUB(now(), $interval)");

	// Generate a key
	$key = random_str( 32 );
	$sql = "insert into im_auth (ip, dynamic_key, timestamp, user_id) VALUES ('"
	       . $_SERVER["REMOTE_ADDR"] . "', '" . $key . "', NOW(), " . $u->ID . ")";

	sql_query( $sql);

	return $key;
}

function im_user_can( $permission ) {
	return ( user_can( login_id(), $permission ) );
}

function login_id() {
	$user = wp_get_current_user();
	if ( $user->ID == "0" ) {
		// Force login
		$inclued_files = get_included_files();
		my_log( __FILE__, $inclued_files[ count( $inclued_files ) - 2 ] );
		$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';

		print '<script language="javascript">';
		print "window.location.href = '" . $url . "'";
		print '</script>';
		print $_SERVER['REMOTE_ADDR'] . "<br/>";
		var_dump( $user );
		exit();
	}

	return $user->ID;
}

function get_project_name( $project_id ) {
	global $conn;

	$sql = "SELECT project_name FROM im_projects WHERE id = " . $project_id;

	$result = mysqli_query( $conn, $sql );
	if ( $result ) {
		$row = mysqli_fetch_assoc( $result );

		return $row["project_name"];
	}
	print "unknown project";
}

?>