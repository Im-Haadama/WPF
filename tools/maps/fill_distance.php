<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/11/17
 * Time: 19:36
 */

require_once( "../r-shop_manager.php" );
print header_text( false, false, false );
require_once( "config.php" );

$sql    = "SELECT order_id FROM ihstore.im_need_orders";
$result = sql_query_array( $sql );
$users  = array();

foreach ( $result as $row ) {
	array_push( $users, order_get_customer_id( $row[0] ) );
}

array_push( $users, 1 );

foreach ( $users as $user_a ) {
	foreach ( $users as $user_b ) {

	}
}
// var_dump($users);

