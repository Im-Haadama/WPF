<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/01/18
 * Time: 23:56
 */

error_reporting( E_ALL );
require_once( '../r-shop_manager.php' );
require_once( '../catalog/bundles.php' );

print header_text( false, true, false );
$category = 18;

$sql = 'select '
       . ' id, post_title '
       . ' from wp_posts '
       . ' where post_type = \'product\'';

$result = sql_query( $sql );

$bundles = new Bundles();

while ( $row = mysqli_fetch_row( $result ) ) {
	$product_categories = array();
	$prod_id            = $row[0];

	$terms = get_the_terms( $prod_id, 'product_cat' );

	// var_dump($terms);
	if ( $terms ) {
		foreach ( $terms as $term ) {
			// print $term->term_id . " " ;
			if ( $term->term_id == $category ) {
				$bundles->Add( $row[1], 8, "35%" );
				//print "<br/>" . $row[1];
//			print $row[1]. "<br/>";
			}
		}
	}

}

