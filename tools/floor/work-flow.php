<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/12/18
 * Time: 08:16
 */

require_once( "../im_tools.php" );
require_once( '../r-shop_manager.php' );
// require_once 'orders-common.php';
require_once '../delivery/delivery.php';

print header_text( false, false );

print orders_supply_table();


function orders_supply_table() {
	$sql =
}
