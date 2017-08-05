<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/06/17
 * Time: 17:31
 */
require_once( "../tools_wp_login.php" );

function business_supply_info( $bid ) {
	global $conn;

	$sql = "SELECT amount, ref FROM ihstore.im_business_info WHERE id = " . $bid;

	$result = sql_query_single( $sql );

	return "תעודת משלוח מספר " . $result[1] . " סכום " . $result[0];
}