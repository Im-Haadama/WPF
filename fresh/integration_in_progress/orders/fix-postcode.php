<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/01/18
 * Time: 15:32
 */

require_once( "../r-shop_manager.php" );

print header_text( false, true );

// print israelpost_get_address_postcode("כפר יונה", "גרניט", "23");
$sql = 'SELECT posts.id'
       . ' FROM `wp_posts` posts'
       . " WHERE post_status = 'wc-processing'" .
       " order by 1";

$result = SqlQuery( $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$order_id = $row[0];

	print "<br/>" . $order_id . " ";

	print get_postmeta_field( $order_id, '_shipping_postcode' ) . " ";

	$city = get_postmeta_field( $order_id, '_shipping_city' );

	$street_num = get_postmeta_field( $order_id, '_shipping_address_1' );

	if ( preg_match( '/([^\d]+)\s?(.+)/i', $street_num, $result ) ) {
		// $result[1] will have the steet name
		$streetName = $result[1];
		// and $result[2] is the number part.
		$streetNumber = $result[2];
	}

	print $city . " " . $streetName . " " . $streetNumber;
	print israelpost_get_address_postcode( $city, $streetName, $streetNumber );
}