<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/03/17
 * Time: 09:09
 */

require_once( 'catalog.php' );
print header_text();
// print Catalog::GetBuyPrice(32, 100005);

$line = "";
//Catalog::UpdateProduct(4425, $line);
//
//print $line;

// $alt = alternatives(297, true);
// var_dump($alt);
//
// print_category_select( "aaa" );

//$line = "";
//$details = true;
//$print_line = Catalog::UpdateProduct( $prod_id, $line, $details );
//if ($print_line) print $line;

// Call this at each point of interest, passing a descriptive string

//prof_flag("start");
//$sql = 'select '
//       . ' id, post_title '
//       . ' from wp_posts '
//       . ' where post_type = \'product\'';
//
//$result = mysqli_query($conn, $sql);
//
//$count = 0;
//while ($row = mysqli_fetch_row($result))
//{
////	prof_flag($row[0]);
//	$alternatives = alternatives( $row[0] );
//	print $row[0] ." ";
//	$count ++;
//	if ($count == 20) break;
//
//}
//prof_flag("end");
//
//prof_print();

// var_dump(Catalog::GetProdID(9425));
//prof_flag("start");
$line = "";
//$sql = "SELECT id, post_title, post_type
//FROM wp_posts WHERE post_type IN ('product', 'product_variation') order by 1";
//
//$result = sql_query($sql);
//while ($row = mysqli_fetch_row($result)){
//	// print $row[0];
//	$line = "";
//	Catalog::UpdateProduct( $row[0], $line, false );
//}
//prof_flag("end");
//$prod_id       = 3703;
//$debug_product = $prod_id;
//Catalog::UpdateProduct( $prod_id, $line, false );

// Catalog::DeleteMappingProductId(7345);
print get_post_status( 7345 );

//prof_print();
