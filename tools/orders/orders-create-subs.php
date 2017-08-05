<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/12/15
 * Time: 21:07
 */

require_once( '../tools.php' );
require_once( 'orders-common.php' );

// Read all subscriptions and create order for them
print header_text();
orders_create_subs();

//$sql = "select client, basket from im_subscriptions where datediff(now(), last_order) > weeks * 7 - 2";
//
//$export = mysql_query ( $sql ) or die ( "Sql error : " . mysql_error( ) );
//
//my_log("creating subscriptions orders", __FILE__);
//
//while( $row = mysql_fetch_row( $export ))
// {
//    $user_id = $row[0];
//    $user = get_userdata($user_id);
//
//    $product_id = $row[1];
//    $order = wc_create_order();
//    my_log("create order: product = " . $product_id, __METHOD__);
//
//    order_add_product($order, $product_id, 1);
//    $order_id = $order->id;
//
//    my_log("add_product");
//    $order->calculate_totals();
//    my_log("totals");
//    // assign the order to the current user
//    update_post_meta($order->id, '_customer_user', $user_id);
//    // payment_complete
//    $order->payment_complete();
//
//    // billing info
//    add_post_meta($order_id, '_billing_first_name',  get_user_meta( $user_id, 'billing_first_name', true));
//    add_post_meta($order_id, '_billing_last_name',  get_user_meta( $user_id, 'billing_last_name', true));
//    add_post_meta($order_id, '_billing_phone',      get_user_meta( $user_id, 'billing_phone', true));
//    add_post_meta($order_id, '_billing_address_1',  get_user_meta( $user_id, 'billing_address_1', true));
//    add_post_meta($order_id, '_billing_address_2',  get_user_meta( $user_id, 'billing_address_2', true));
//    add_post_meta($order_id, '_shipping_first_name', get_user_meta( $user_id, 'shipping_first_name', true ));
//    add_post_meta($order_id, '_shipping_last_name', get_user_meta( $user_id, 'shipping_last_name', true ));
//    add_post_meta($order_id, '_shipping_address_1', get_user_meta( $user_id, 'shipping_address_1', true ));
//    add_post_meta($order_id, '_shipping_address_2', get_user_meta( $user_id, 'shipping_address_2', true ));
//    add_post_meta($order_id, '_shipping_city',      get_user_meta( $user_id, 'shipping_city', true ));
//
//     $sql1 = "update im_subscriptions set last_order=now() where client = " . $user_id;
//
//     $export1 = mysql_query ( $sql1 ) or die ( "Sql error : " . mysql_error( ) );
//
//}


?>