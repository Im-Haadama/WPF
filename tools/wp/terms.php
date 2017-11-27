<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/17
 * Time: 05:51
 */
require_once( "../r-shop_manager.php" );
//print header_text(false);
function terms_add_category( $object_id, $term ) {
	wp_set_object_terms( $object_id, $term, "product_cat", true );
}

function terms_remove_category( $object_id, $term ) {
	wp_remove_object_terms( $object_id, $term, "product_cat" );
}

function terms_print( $object_id ) {
	$the_terms = wp_get_object_terms( $object_id, "product_cat" );

	foreach ( $the_terms as $term ) {
		print $term->name . "<br/>";
	}
}

//terms_add_category(35, "עם האדמה");
//terms_print(35);
