<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/08/18
 * Time: 20:43
 */

//
//
require_once( "orders-common.php" );

// Calculate what needed for packed deliveries

$sql = "SELECT id, post_status FROM wp_posts " .
       " WHERE post_status LIKE '%wc%waiting%'";

$result = SqlQuery( $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$id = $row[0];
	print "order " . $row[0] . " " . $row[1] . ": <br/>";

	$o = new WC_Order( $id );

	$order_items = $order->get_items();


}