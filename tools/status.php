<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/05/17
 * Time: 09:12
 */
require_once( 'tools_wp_login.php' );
require_once( 'orders/stat.php' );

$order_count = get_order_status();

function show_info_item( $text, $url ) {

}