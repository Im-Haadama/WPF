<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/10/18
 * Time: 19:32
 */

require_once( "../im_tools.php" );
require_once( "../orders/Order.php" );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );

$sql = 'SELECT posts.id, order_is_group(posts.id), order_user(posts.id) '
       . ' FROM `wp_posts` posts'
       . ' WHERE `post_status` in (\'wc-awaiting-shipment\')';


$sql .= ' order by 1';

$orders = sql_query( $sql );

while ( $order = sql_fetch_row( $orders ) ) {
	$order_id   = $order[0];
	$is_group   = $order[1];
	$order_user = $order[2];

	$order = new Order( $order_id );
	$m     = $order->Missing();
	if ( strlen( $m ) ) {
		print gui_header( 1, $order->CustomerName() . " " . gui_hyperlink( $order_id, "../orders/get-order.php?order_id=" . $order_id ) );
		print $m;
	}
}
