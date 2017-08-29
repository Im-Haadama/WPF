<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/06/17
 * Time: 18:46
 */
require_once '../im_tools.php';
// require_once('../header.php');
require_once( '../orders/orders-common.php' );

print header_text( true );
orders_create_subs();