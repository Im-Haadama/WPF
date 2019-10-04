<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/09/17
 * Time: 06:18
 */

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( TOOLS_DIR, dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/r-shop_manager.php' );
require_once( TOOLS_DIR . '/gui/sql_table.php' );

print header_text( false );

$sql = "SELECT product_name, price, quantity, prod_id_from_name(product_name) as מזהה FROM im_delivery_lines " .
       " WHERE prod_id = 0 " .
       " AND product_name NOT IN ('הנחת כמוות','משלוח')" .
       " GROUP BY product_name " .
       " ORDER BY id DESC";

$results = sql_query( $sql );

$table = "<table>";

$table .= gui_row( array( "מוצר", "מחיר", "כמות", "מזהה מוצר", "סטטוס מוצר" ) );
while ( $row = mysqli_fetch_row( $results ) ) {
	$prod_id = $row[3];
	if ( ! ( $prod_id > 0 ) ) {
		$pos              = strpos( $row[0], " " );
		$product_name_prf = substr( $row[0], 0, $pos );
		if ( strlen( $product_name_prf ) > 2 ) {
			$prod_id = sql_query_single_scalar( "select max(id) from wp_posts where post_title like '%$product_name_prf%'" );
			// print $prod_id;
		}
	}
	$status = ( $prod_id > 0 ) ? get_post_status( $prod_id ) : "";
	if ( $status == 'publish' ) {
		continue;
	}
	array_push( $row, $status );

	$table .= gui_row( $row );
}

$table .= "</table>";
print $table;