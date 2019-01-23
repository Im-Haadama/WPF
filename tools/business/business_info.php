<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/06/17
 * Time: 17:31
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

function business_supply_info( $bid ) {
	global $conn;

	$sql = "SELECT amount, ref FROM im_business_info WHERE id = " . $bid;

	$result = sql_query_single( $sql );

	return "תעודת משלוח מספר " . $result[1] . " סכום " . $result[0];
}

function display_part_name( $part_id ) {
	return sql_query_single_scalar( "select client_displayname($part_id)" );
}