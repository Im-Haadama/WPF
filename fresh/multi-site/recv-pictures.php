<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/02/17
 * Time: 23:16
 * Recieves pictures from secondary site to main site (with multisite mapping)
 * As June 2017 from Im haadama(send) to Organic Store(recv)
 */
require_once( '../r-shop_manager.php' );

$ids           = $_GET["imgs"];
$prefix        = $_GET["prefix"];
$input_array   = explode( ',', $ids );
$count         = 0;
$count_changed = 0;
for ( $i = 0; $i < count( $input_array ); $i += 2 ) {
	$count ++;
	$id   = $input_array[ $i ];
	$path = $input_array[ $i + 1 ];
	print "RECV: " . $id . " " . $path . "<br/>";

	if ( update_post_meta( $id, 'fifu_image_url', $path ) ) {
		$count_changed ++;
	}
	// print  $id. ", " . $path . "<br/>";
}
print "התקבלו " . $count . " תמונות " . $count_changed . " חדשות " . "<br/>";
