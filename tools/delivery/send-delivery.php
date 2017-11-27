<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/04/16
 * Time: 15:32
 */

include_once( "../r-shop_manager.php" );
include_once( "../orders/orders-common.php" );
include_once( "../account/account.php" );
include_once( "delivery.php" );

$del_id = $_GET["del_id"];
if ( ! ( $del_id > 0 ) ) {
	print "Usage: send_delivery.php&del_id=##<br/>";
	die( 1 );
}
$edit = false;
if ( isset( $_GET["edit"] ) ) {
	$edit = true;
}

$delivery = new delivery( $del_id );
// print "info: " . $info_email;
$delivery->send_mail( $track_email, $edit );
