<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/06/17
 * Time: 06:40
 */

require_once( 'orders-common.php' );

//$last= order_get_last(1);
//
//$diff = date_diff(new DateTime($last), new DateTime());
//print $diff->d;

$user_id  = 91;
$postcode = get_user_meta( $user_id, 'shipping_postcode', true );
$package  = array( 'destination' => array( 'country' => 'IL', 'postcode' => $postcode ) );
$zone     = WC_Shipping_Zones::get_zone_matching_package( $package );
$method   = WC_Shipping_Zones::get_shipping_method( $zone->get_id() );

$aa = new $method;
print $aa->cost;
// var_dump($aa);
// print $method->calculate_shipping();

// var_dump($method);