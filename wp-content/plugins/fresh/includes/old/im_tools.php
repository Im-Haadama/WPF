<?php
// Display errors and messages only on develop server
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 21:42
 */

// Major version.
$power_version = file_get_contents(dirname(dirname(__FILE__)) . "/version");

require_once( FRESH_INCLUDES . "/im-config.php" );
require_once( "im_tools_light.php" );
# require_once( STORE_DIR . "/wp-config.php" );
# require_once( STORE_DIR . "/wp-load.php" );
# require_once( FRESH_INCLUDES . "/core/wp.php" );

//if ( (get_user_id() == 1) or ! isset( $_SERVER['SERVER_NAME'] ) or ( $_SERVER['SERVER_NAME'] == "127.0.0.1" ) ) {
////	 print "debug";
//	error_reporting( E_ALL );
//	ini_set( 'display_errors', 'on' );
//}

function order_get_shipping_fee( $order_id ) {
	$order = wc_get_order( $order_id );
	foreach ( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ) {
		return $shipping_item_obj->get_total();
		// var_dump($shipping_item_obj); print "<br/>";
		// return $shipping_item_obj["price"];
	}
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
			'state'    => '',
			'postcode' => $postcode,
		),
	) )->get_id();
	my_log( "zone: " . $zone1 );

//	print $zone1;

	return $zone1;
}

function get_user_address( $user_id, $full = false ) {
	$u = $user_id + 0;
	if ( is_numeric( $u ) ) {
		$data = get_user_meta( $user_id, 'shipping_address_1', true ) . " " .
		        get_user_meta( $user_id, 'shipping_city', true );
		if ( $full ) {
			$data .= " " . get_user_meta( $user_id, 'shipping_address_2', true );
		}

		return $data;
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

function get_current_user_name() {
	return get_customer_name( wp_get_current_user()->ID );
}

function get_customer_by_email( $email ) {
// print "email = " . $email;

	$user = get_user_by( "email", $email );

// 	var_dump($user);

	return $user->ID;
}


function get_term_name($term_id)
{
	$term = get_term($term_id);

	return $term->name;
}
//function is_manager( $user_id ) {
//	$user    = new WP_User( $user_id );
//	$manager = false;
//	if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
//		foreach ( $user->roles as $role ) {
//			if ( $role == 'administrator' or $role == 'shop_manager' ) {
//				$manager = true;
//			}
//		}
//	}
//
//	return $manager;
//}

//function is_admin($user_id)
//{
//	return false;
//	$user    = new WP_User( $user_id );
//	$manager = false;
//	if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
//		foreach ( $user->roles as $role ) {
//			if ( $role == 'administrator' ) {
//				$manager = true;
//			}
//		}
//	}
//	return $manager;
//}

