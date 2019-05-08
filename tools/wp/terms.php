<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/17
 * Time: 05:51
 */
//print header_text(false);

require_once( ROOT_DIR . '/wp-admin/includes/taxonomy.php' );

function terms_add_category( $object_id, $term ) {
	wp_set_object_terms( $object_id, $term, "product_cat", true );
}

// Remove given terms or all.
function terms_remove_category( $object_id, $terms = null ) {
	if ( ! $terms ) {
		$terms = wp_get_object_terms( $object_id, "product_cat" );
	}
	foreach ( $terms as $term ) {
		wp_remove_object_terms( $object_id, $term->name, "product_cat" );
	}
}

function terms_print( $object_id ) {
	$the_terms = wp_get_object_terms( $object_id, "product_cat" );

	foreach ( $the_terms as $term ) {
		print $term->name . "<br/>";
	}
}

function terms_get_as_string( $object_id ) {
	$the_terms = wp_get_object_terms( $object_id, "product_cat" );

	$result = "";
	foreach ( $the_terms as $term ) {
		$result .= $term->name . ",";
	}

	return rtrim( $result, "," );

}

function terms_get_id( $term ) {
	return term_exists( $term, 'product_cat' );
}

function terms_create( $cat_name, $parent = 0 ) {

	if ( $id = category_exists( $cat_name, $parent ) ) {
		return $id;
	}

	$term = wp_insert_term( $cat_name, 'product_cat' );
	if ( is_wp_error( $term ) ) {
		return null;
	} else {
		return $term['term_id'];
	}
}

//terms_add_category(35, "עם האדמה");
//terms_print(35);
