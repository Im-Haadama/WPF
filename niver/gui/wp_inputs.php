<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/09/17
 * Time: 19:02
 */


function gui_select_category( $id, $list = true ) {
//	$result = '<select id="product_cat">';
//	$product_categories = get_terms( 'product_cat', array(
//		'hide_empty' => false,
//	) );
//	foreach ( $product_categories as $cat ) {
//
//		$result .= '<option value="' . $cat->slug . '"data-category-id=' . $cat->term_id . '>' . $cat->name . '</option>';
//		// $line .= '<option value="' . $new_price . '" data-supplier = ' . $row1[1] . ' data-supplier-price = ' . $supplier_price . '>' . $new_price . ' ' . get_supplier_name($row1[1]) . '</option>';
//
//	}
//	$result .= "</select>";

	$result = "";

	if ( $list ) {
		$result = gui_datalist( "category", "im_categories", "name", 0 );
	}

	$result .= '<input id="cat_' . $id . '" list="category" onkeypress="select_category(' . $id . ')">';

	return $result;
}
