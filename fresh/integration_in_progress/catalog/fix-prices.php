<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/02/18
 * Time: 16:58
 */

require_once( "../r-shop_manager.php" );
require_once( "bundles.php" );

print header_text( false, true, false );

//$sql = "select post_id from wp_postmeta where meta_key = 'supplier_name' and meta_value = 'עם האדמה'";
$sql = "select product_id from im_supplier_mapping where supplier_id in (100004, 100005, 100016)";

$result = sql_query( $sql );

$line = "";
while ( $row = mysqli_fetch_row( $result ) ) {
	$post_id = $row[0];

	print "<br/>$post_id";
	if ( $product_id > 0 ) {
		Catalog::UpdateProduct( $post_id, $line );
	}

	print $line;
}