<?php
require_once( "../tools.php" );
require_once( "../gui/sql_table.php" );
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/02/17
 * Time: 14:56
 */


$sql = "select p.id as מזהה, post_title as 'שם מוצר', m.meta_value as 'מחיר מבצע', m1.meta_value as 'מחיר רגיל'" .
       " from im_products p " .
       " join wp_postmeta m " .
       " join wp_postmeta m1 " .
       " where m.post_id = p.id " .
       " and m.meta_key = '_sale_price' " .
       " and m.meta_value > 0 " .
       " and m1.post_id = p.id " .
       " and m1.meta_key = '_regular_price'
    ";

print header_text();

$result = sql_query( $sql );

$fields = mysqli_fetch_fields( $result );
// var_dump($header);
$headers = array();
foreach ( $fields as $val ) {
	array_push( $headers, $val->name );
}
$rows = array();
array_push( $headers, "מזהה מרוחק" );
array_push( $rows, $headers );

while ( $row = mysqli_fetch_row( $result ) ) {
	$new_row = $row;
	$sql_r   = "select remote_prod_id from im_multisite_map where local_prod_id = $row[0]";
	$remote  = sql_query_single_scalar( $sql_r );
	array_push( $new_row, $remote );
	array_push( $rows, $new_row );
}
print gui_table( $rows );

