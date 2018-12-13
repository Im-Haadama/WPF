<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/12/18
 * Time: 11:21
 */

require_once( "../im_tools.php" );
require_once( '../r-shop_manager.php' );
require_once( "../orders/orders-common.php" );

print header_text( false, true, true );
print gui_header( 1, "לקוח חדש" );
$order_id = $_GET["order_id"];
print "צור קשר טלפוני עם הלקוח<br/>";
print "מספר הזמנה " . $order_id . "<br/>";
print order_info_data( $order_id, true );
