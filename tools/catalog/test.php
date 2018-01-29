<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/03/17
 * Time: 09:09
 */

require_once( "../im_tools.php" );
require_once( 'catalog.php' );

print header_text( false, true, false );
// print Catalog::GetBuyPrice(32, 100005);

//$line = "";
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
// print get_post_status( 7345 );

//prof_print();

// set_category( array( 1803 ), "פירות וירקות" );

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

print Catalog::GetProdImage( 1141 );

////require_once( STORE_DIR . '/wp-admin/includes/image.php' );
////require_once( STORE_DIR . '/wp-admin/includes/file.php' );
////require_once( STORE_DIR . "/wp-admin/includes/media.php" );
//
//
//// update_picture( 2382 );
//$prod_id = 79;
////$debug_product = $prod_id;
////$line = "";
////Catalog::UpdateProduct(1117, $line, true);
//
//$image = 'http://store.im-haadama.co.il/wp-content/uploads/2014/10/etinger.jpg';
//
//upload_image($prod_id, $image);

//function update_picture( $prod_id ) {
//	$a = alternatives( $prod_id );
//	foreach ( $a as $alter ) {
//		$image = $alter->getPicturePath();
//		if ( $image ) {
//			print "$image<br/>";
//			$xx = 'id';
//			$i  = media_sideload_image( $image, $prod_id, "", $xx );
//			// print "x: " . $i;
//			// $i = 2407;
//			$attach_data = wp_generate_attachment_metadata( $i, $image );
//			//$res1= wp_update_attachment_metadata( $i, $attach_data );
//			//$res2= set_post_thumbnail( $prod_id, $i);
//
////			print "res1: $res1<br/>";
//			//print "res2: $res2<br/>";
//			return;
//		}
//	}
//	// $pl = Pricelist
//}
//
