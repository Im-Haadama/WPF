<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../r-shop_manager.php' );
require_once( 'account.php' );

$customer_id = $_GET["customer_id"];
$amount      = $_GET["amount"];
$date        = $_GET["date"];
$ref         = $_GET["ref"];
$type        = $_GET["type"];

account_add_transaction( $customer_id, $date, $amount, $ref, $type );

?>

