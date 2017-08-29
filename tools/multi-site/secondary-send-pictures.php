<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/06/17
 * Time: 21:03
 * Sending pictures from secodary to main
 */
require_once( "../im_tools.php" );
$ids = explode( ',', $_GET["ids"] );

$result = "";
for ( $i = 0; $i < count( $ids ); $i += 2 ) {
	// print $id . "<br/>";
	$tid = get_post_thumbnail_id( $ids[ $i + 1 ] );

	$img_a = wp_get_attachment_image_src( $tid );
	$img   = $img_a[0];
	if ( strlen( $img ) > 1 ) {
		print $ids[ $i ] . "," . $img . "<br/>";
	}
}
