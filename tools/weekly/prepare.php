<?php
require_once '../tools_wp_login.php';
require_once( '../header.php' );
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/11/16
 * Time: 23:00
 */

// print all needed for daily functions:
// 1) Orders with colleting sheet
// 2) ride info
// 3) pickup sheets

require_once '../delivery/delivery.php';
require_once '../orders/orders-common.php';
require_once '../supplies/supplies-post.php';

$sql = 'SELECT posts.id as id'
       . ' FROM `wp_posts` posts'
       . " WHERE post_status LIKE '%wc-processing%' order by 1";

// print $sql;
$result = $conn->query( $sql );

print "<style>";
print "@media print {";
print "h1 {page-break-before: always;}";
print "}";
print "</style>";

while ( $row = mysqli_fetch_assoc( $result ) ) {
	$id = $row["id"];
//	print $id . "<br/>";
	print_order_info( $id, true );
	$D = Delivery::CreateFromOrder( $id );
	$D->print_delivery( ImDocumentType::delivery, ImDocumentOperation::collect );
}

$sql = 'SELECT id as id'
       . ' FROM `im_supplies` posts'
       . " WHERE status < 5 order by 1";

// print $sql;
$result   = $conn->query( $sql );
$supplies = Array();
while ( $row = mysqli_fetch_assoc( $result ) ) {
	array_push( $supplies, $row["id"] );
}
print_supplies( $supplies, true );


require_once( '../delivery/get-driver-multi.php' );