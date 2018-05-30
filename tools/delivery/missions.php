<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/10/17
 * Time: 16:18
 */
require_once( "../r-shop_manager.php" );

require_once( ROOT_DIR . '/agla/sql.php' );

// print header_text();
function show_zone_names( $str_zones, $line_id ) {
	if ( strlen( $str_zones ) > 1 ) {
		$zones  = explode( ",", $str_zones );
		$result = "";
		foreach ( $zones as $zone ) {
			$result .= sql_query_single_scalar( "SELECT zone_name FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone ) . "(" . $zone . "), ";
		}

		return rtrim( $result, ", " );
	}

	return "";
}

// create_missions();

// Check if create is needed.
//$delta = sql_query_single_scalar("select max(date) - curdate() from im_missions");

//if ($delta > 14) return;

// print get_zones('ג');

function get_zones_per_path( $path_code ) {
	// print $path_code . "<br/>";
	// Collect zone, days info
	$sql    = "SELECT zone_id, codes FROM wp_woocommerce_shipping_zones";
	$result = sql_query_array( $sql );
	if ( ! is_array( $result ) ) {
		my_log( __METHOD__ . " " . $result );

		return "error";
	}
	$days = array();
	foreach ( $result as $zone_info ) {
		$zone_id = $zone_info[0];
		if ( $zone_info ) {
			$codes = explode( ",", $zone_info[1] );
			// print $zone_id . " "; var_dump($codes); print "<br/>";
			foreach ( $codes as $code ) {
				if ( is_null( $days[ $code ] ) ) {
					$days[ $code ] = array();
				}
				//	print "adding $zone_id to $code <br/>";
				array_push( $days[ $code ], $zone_id );
			}
		}
	}

	// var_dump($days[$path_code]); print "<br/>";
	if ( is_array( $days[ $path_code ] ) ) {
		return implode( ",", $days[ $path_code ] );
	}
	print header_text();
	print "no path for $path_code";
	die( 1 );

}

//	// Get ready
//	$last_sunday = "select max(date) - dayofweek(max(date)) from im_missions";
//	foreach ($days as $key => $day){
//
//		$weekday = get_letter_day($key)
//		$id = "select max(";
//		$sql = "INSERT INTO im_missions (date, start_h, end_h, zones, name)
//						SELECT ADDDATE(date, INTERVAL 7 DAY), start_h, end_h, zones, name FROM ihstore.im_missions WHERE id = " . $id;
//
//		print  . " " . implode(",", $day) . "<br/>";
//	}

if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
	switch ( $operation ) {
		case "dup":
			$id        = $_GET["id"];
			duplicate_mission( $id );

			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
			exit;
	}
}

function duplicate_mission( $id ) {
//	$path_code = sql_query_single_scalar( "SELECT path_code FROM im_missions WHERE id = " . $id );
//	// print $path_code . "<br/>";
//	$zones = get_zones_per_path( $path_code );
	// print $zones . "<br/>";

	$sql = "INSERT INTO im_missions (date, start_h, end_h, zones, name, path_code) 
						SELECT ADDDATE(date, INTERVAL 7 DAY), start_h, end_h, zones, name, path_code FROM im_missions WHERE id = " . $id;
	// print $sql;
	// die(1);
	sql_query( $sql );

}