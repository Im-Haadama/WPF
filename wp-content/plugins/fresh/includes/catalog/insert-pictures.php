<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/02/17
 * Time: 18:49
 */

require_once( "../r-shop_manager.php" );

$img_list  = $_GET["img_array"];
$img_array = explode( ",", $img_list );
for ( $i = 0; $i < count( $img_array ); $i ++ ) {
	$id  = $img_array[ $i ];
	$url = $img_array[ $i + 1 ];

	insert_img( $id, $url );
}

function insert_img( $id, $url ) {
	$sql = "UPDATE wp_posts SET guid = " . $url .
	       " WHERE id = " . $id;

	sql_query( $sql );
}