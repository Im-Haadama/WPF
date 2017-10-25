<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/10/17
 * Time: 16:18
 */
require_once( "../tools_wp_login.php" );

require_once( "../sql.php" );

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

if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
	switch ( $operation ) {
		case "dup":
			$id  = $_GET["id"];
			$sql = "INSERT INTO im_missions (date, start_h, end_h, zones, name) 
						SELECT date + 7, start_h, end_h, zones, name FROM ihstore.im_missions WHERE id = " . $id;
			sql_query( $sql );
			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
			exit;
			break;

	}
}