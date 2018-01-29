<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:08
 */

require_once( "../options.php" );

function print_category_select( $id, $select = false ) {
//	$term             = get_term( info_get( "suppliers_category" ) );
//	$suppliers_father = urldecode( $term->slug );
	//print "father: " . $suppliers_father . "<br/>";
	print '<select id="' . $id . '">';
	$categ_lines = array();

	if ( $select ) {
		$line = '<option value="' . - 1 . '"data-category-id=' . - 1 . '>' . " בחר" . '</option>';
		// $line .= '<option value="' . $new_price . '" data-supplier = ' . $row1[1] . ' data-supplier-price = ' . $supplier_price . '>' . $new_price . ' ' . get_supplier_name($row1[1]) . '</option>';
		array_push( $categ_lines, array( " בחר", $line ) );

	}

	$product_categories = get_terms( 'product_cat', array(
		'hide_empty' => false,
	) );
	foreach ( $product_categories as $cat ) {
//		print $cat->term_id . " " . "<br/>";
//		$parents = explode( ",", get_category_parents( $cat->term_id, false, ',' ) );
		//	var_dump($parents); print "<br/>";
//		if ( in_array( $suppliers_father, $parents ) ) {
//			continue;
//		}

		$line = '<option value="' . $cat->slug . '"data-category-id=' . $cat->term_id . '>' . $cat->name . '</option>';
		// $line .= '<option value="' . $new_price . '" data-supplier = ' . $row1[1] . ' data-supplier-price = ' . $supplier_price . '>' . $new_price . ' ' . get_supplier_name($row1[1]) . '</option>';
		array_push( $categ_lines, array( $cat->name, $line ) );
	}
	sort( $categ_lines );
	foreach ( $categ_lines as $line ) {
		print $line[1];
	}
	print '</select>';
}
