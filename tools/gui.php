<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:08
 */

require_once( ROOT_DIR . "/tools/options.php" );

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

// $key, $data, $args
function gui_select_document_type( $id = null, $selected = null, $args = null ) {
	global $DocumentTypeNames;

	$events = GetArg($args, "events", null);
	$types = array();
	for ( $i = 1; $i < ImDocumentType::count; $i ++ ) {
		$value["id"]   = $i;
		$value["name"] = $DocumentTypeNames[ $i ];
		array_push( $types, $value );
	}

	return gui_select( $id, "name", $types, $events, $selected, "id" );
}

// $selector_name( $input_name, $orig_data, $args)
function gui_select_worker( $id = null, $selected = null, $args = null ) {

	// $events = GetArg($args, "events", null);
	$edit = GetArg($args, "edit", true);
	$companies = GetArg($args, "companies", "must send company");
	$debug = false; // (get_user_id() == 1);
	$args["debug"] = $debug;
	$args["name"] = "client_displayname(user_id)";
	$args["where"] = "where is_active=1 and company_id in (" . comma_implode($companies) . ")";
	$args["id_key"] = "user_id";
	$args["selected"] = $selected;

	if ($edit)
		return GuiSelectTable($id, "im_working", $args);
//		return gui_select_table( $id, "im_working", $selected, $events, "",
//			"client_displayname(user_id)",
//			"where is_active=1 and company_id = " . $company, true, false, null, "id" );
	else
		return sql_query_single_scalar("select client_displayname(user_id) from im_working where user_id = " . $selected);
}
