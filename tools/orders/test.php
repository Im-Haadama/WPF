<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/06/17
 * Time: 06:40
 */

require_once( "../r-shop_manager.php" );
require_once( 'orders-post.php' );

//$last= order_get_last(1);
//
//$diff = date_diff(new DateTime($last), new DateTime());
//print $diff->d;

print header_text( false, true, false );

// order_calculate( 2101 );

// map_get_

// print order_get_mission_name( 8097, true );

//$user_id  = 1;
//$postcode = get_user_meta( $user_id, 'shipping_postcode', true );
//// print "postcode: " . $postcode . "<br/>";
//$package = array( 'destination' => array( 'country' => 'IL', 'postcode' => $postcode ) );
//$zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
//$zone_id = $zone->get_id();
//// print "zone id: " . $zone_id . "<br/>";
//// $method   = WC_Shipping_Zones::get_shipping_method( $zone->get_id() );
////
////$aa = new $method;
////print $aa->cost;
//// var_dump($aa);
//// print $method->calculate_shipping();
//
//// var_dump($method);
//
//
//$sql = "SELECT id, zones, date, start_h, end_h FROM im_missions WHERE date > curdate() ORDER BY 3";
//
//$result = sql_query( $sql );
//
//while ( $row = mysqli_fetch_row( $result ) ) {
//	$zones = $row[1];
//	// print "zones: " . $zones . "<br/>";
//	if ( in_array( $zone_id, explode( ",", $zones ) ) ) {
//		print $row[0] . " " . $row[2] . " " . $row[3] . ":00-" . $row[4] . ":00<br/>";
//	}
//}