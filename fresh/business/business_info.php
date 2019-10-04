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

require_once( ROOT_DIR . '/fresh/im_tools.php' );
require_once( ROOT_DIR . '/fresh/suppliers/gui.php' );
require_once( ROOT_DIR . '/fresh/people/people.php' );
require_once( ROOT_DIR . '/fresh/gui.php' );

function business_supply_info( $bid ) {
	$sql = "SELECT amount, ref, net_amount FROM im_business_info WHERE id = " . $bid;

	$result = sql_query_single( $sql );

	return "תעודת משלוח מספר " . $result[1] . " סכום " . $result[0] . " (ללא מע\"מ " . $result[2] . ")";
}

function display_part_name( $part_id ) {
	return sql_query_single_scalar( "select client_displayname($part_id)" );
}