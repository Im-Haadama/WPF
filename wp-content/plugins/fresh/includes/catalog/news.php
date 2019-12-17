<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/10/18
 * Time: 18:44
 */
require_once( "../../core/gui/inputs.php" );

$sql_products = "SELECT id FROM wp_posts WHERE post_type = 'product' ";

print gui_header( 1, "מוצרים פופלריים" );
print popular_products( 10 );

print gui_header( 1, "מוצרים שיצאו" );
print non_available();

function non_available( $count = 0 ) {
	global $sql_products;

	$sql = $sql_products . " and post_status = 'draft'" .
	       " and post_modified >= current_date - 7";

	if ( $count ) {
		$sql .= " limit " . $count;
	}

	return prod_list( $sql );
}

function popular_products( $count = 0 ) {
	$sql = "SELECT dl.prod_id, sum(dl.quantity) FROM im_delivery_lines dl" .
	       " JOIN im_delivery d WHERE dl.prod_id > 0 AND d.date >= current_date -14 AND dl.delivery_id = d.id " .
	       " GROUP BY dl.prod_id ORDER BY 2 DESC ";

	if ( $count ) {
		$sql .= " limit " . $count;
	}

	return prod_list( $sql );
}


//print new_products(5);


function new_products( $count = 0 ) {
	global $sql_products;

	$sql = $sql_products . " and post_status = 'publish'" .
	       " and post_modified >= current_date - 7";

	if ( $count ) {
		$sql .= " limit " . $count;
	}
	// print $sql;
}

function prod_list( $sql ) {
	$ids  = sql_query_array_scalar( $sql );
	$text = "";
	foreach ( $ids as $id ) {
//		print $id . " " . get_product_name($id) . "<br/>";
		$text .= get_product_name( $id ) . ", ";
//		print $text;
	}
	$text = rtrim( $text, ", " );

	return $text;

}