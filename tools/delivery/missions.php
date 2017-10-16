<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/10/17
 * Time: 16:18
 */

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