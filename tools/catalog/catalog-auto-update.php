<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/03/17
 * Time: 20:05
 */
require_once( 'catalog.php' );

$sql = "SELECT id, post_title, post_type
FROM wp_posts WHERE post_type IN ('product', 'product_variation') ";

$debug = false;
// $debug_product = 225;
if ( $debug ) {
	print "DEBUG<br/>";
}

$result = sql_query( $sql );
if ( ! isset( $_GET["no_header"] ) ) {
	print header_text();
}

// print table_content($sql);
print "<table>";
print gui_row( array( "מזהה", "שם מוצר", "מספר חלופות", "חלופה זולה", "פעולה" ) );
$prod_count = 0;
while ( $row = mysqli_fetch_row( $result ) ) {
	if ( $debug ) {
		print "<br/>" . $row[0] . " " . $row[1] . " ";
	}
	$prod_count ++;
	if ( $prod_count % 100 == 0 ) {
		print $prod_count . "<br/>";
	}
	$prod_id = $row[0];

	$print_line = Catalog::UpdateProduct( $prod_id, $line );

	if ( $print_line ) {
		print $line;
	}
}
// if ($count == 1) { continue; }
// We have at least two alternatives.
// See if "selected" available.
// If not, select cheapest.

print "</table>";
if ( $prod_count == 0 ) {
	print $sql;
}
print "done. handled " . $prod_count;

$line = "<tr>";
