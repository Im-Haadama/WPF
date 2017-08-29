<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 14/03/17
 * Time: 00:10
 */
require_once( "../im_tools.php" );

$ids = $_GET["ids"];

$sql = "UPDATE im_delivery_legacy SET status = 2";
mysqli_query( $conn, $sql );

var_dump( $ids );

$i = explode( ",", $ids );
var_dump( $i );
create_deliveries( $i );

function create_deliveries( $ids ) {
	global $conn;
	foreach ( $ids as $id ) {
		print $id . " ";
		$sql = "INSERT INTO im_delivery_legacy (client_id, status) " .
		       " VALUES (" . $id . ", 1) ";
		mysqli_query( $conn, $sql );

	}
}

