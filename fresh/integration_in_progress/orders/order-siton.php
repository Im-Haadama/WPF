<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/11/15
 * Time: 20:59
 */

require_once( '../r-shop_manager.php' );
require_once( 'orders-common.php' );


$order_id = $_GET["order_id"];

$sql = 'select '
       . ' woim.meta_value, woim.order_item_id'
       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
       . ' where order_id = ' . $order_id
       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
       . ' group by woi.order_item_name order by 1'
       . ' ';
MyLog( $sql, "get-order.php" );

$result = SqlQuery( $sql );

// Get product_id, order_item_id from the order
while ( $row = mysqli_fetch_row( $result ) ) {

}

