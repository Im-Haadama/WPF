<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/08/18
 * Time: 20:43
 */

//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );
require_once( "orders-common.php" );

// Calculate what needed for packed deliveries

$sql = "SELECT id, post_status FROM wp_posts " .
       " WHERE post_status LIKE '%wc%waiting%'";

$result = sql_query( $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$id = $row[0];
	print "order " . $row[0] . " " . $row[1] . ": <br/>";

	$o = new WC_Order( $id );

	$order_items = $order->get_items();


}