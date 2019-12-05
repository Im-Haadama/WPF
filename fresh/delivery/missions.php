<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/10/17
 * Time: 16:18
 */
// require_once( "../r-shop_manager.php" );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

// require_once( ROOT_DIR . "/im_tools.php" );
require_once( ROOT_DIR . '/niver/data/sql.php' );

// print header_text();
//function show_zone_names( $str_zones, $line_id )
//{
//	if ( strlen( $str_zones ) > 1 ) {
//		$zones  = explode( ",", $str_zones );
//		$result = "";
//		foreach ( $zones as $zone_id ) {
//			$result .= zone_get_name($zone_id) . "(" . $zone_id . "), ";
//		}
//
//		return rtrim( $result, ", " );
//	}
//
//	return "";
//}

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
		case "del":
			$id = $_GET["id"];
			delete_mission( $id );

			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
			exit;
		case "dup_week":
			duplicate_week();
			break;
	}
}

function duplicate_week() {
	$sql = "SELECT id FROM im_missions " .
	       " WHERE date >= curdate() - INTERVAL DAYOFWEEK(curdate())+6 DAY
                   AND DATE < curdate() - INTERVAL DAYOFWEEK(curdate())-1 DAY";

	$ids = sql_query_array_scalar( $sql );
	foreach ( $ids as $id ) {
		// print "mission: " . $id . "<br/>";
		duplicate_mission( $id );
	}
}

function delete_mission( $id ) {
	// print "deleting...<br/>";
	$sql = "DELETE FROM im_missions WHERE id = " . $id;
	sql_query( $sql );
	print "deleted";
}

function duplicate_mission( $id ) {
//	$path_code = sql_query_single_scalar( "SELECT path_code FROM im_missions WHERE id = " . $id );
//	// print $path_code . "<br/>";
//	$zones = get_zones_per_path( $path_code );
	// print $zones . "<br/>";

	$path_code = sql_query_single_scalar( "select path_code from im_missions where id = " . $id );

	$max_weeks = 4;
	$sql       = "select date from im_missions where id = " . $id;
	$date      = sql_query_single_scalar( $sql );

	for ( $i = 1; $i <= $max_weeks; $i ++ ) {
		$sql = "select count(id) from im_missions where path_code = " . quote_text( $path_code ) .
		       " and date = ADDDATE(' " . $date . "', interval " . $i * 7 . " DAY) ";
		// print $sql . "<br/>";
		$c = sql_query_single_scalar( $sql );
		// print "c= " . $c . "<br/>";
		if ( ( $c == 0 ) ) {
			$sql = "INSERT INTO im_missions (date, start_h, end_h, zones, name, path_code, start_address, end_address)
						SELECT ADDDATE(date, INTERVAL " . $i * 7 . " DAY), start_h, end_h, zones, name, path_code, start_address, end_address FROM im_missions WHERE id = " . $id;
			//print $sql;
			sql_query( $sql );

			return;
		}
	}
	// print $sql;
	// die(1);
	sql_query( $sql );

}