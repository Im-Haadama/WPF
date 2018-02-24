<?php
require_once( '../im_tools.php' );
require_once( "../gui/inputs.php" );
require_once( "../catalog/catalog.php" );

$search_text = $_GET["search_txt"];
$operation   = $_GET["operation"];

class CatalogFields {
	const
		/// User interface
		line_select = 0,
		id = 1,
		name = 2,
		price = 3,
		vat = 4,
		supplier = 5,
		status = 6,
		category = 7,
		order = 8,
		cost_price = 9,
		field_count = 10;
}

;

$header_fields = array(
	"בחר",
	"מזהה",
	"שם פריט",
	"מחיר",
	"מע\"מ",
	"ספק",
	"סטטוס",
	"קטגוריה",
	"הזמנה",
	"מחיר עלות"
);

// Check connection

my_log( $operation, "catalog-db-query.php" );
switch ( $operation ) {
	case "set_category":
		$category = $_GET["category"];
		print "setting category " . $category . "<br/>";
		$prod_ids = $_GET["prod_ids"];
		$ids      = explode( ',', $prod_ids );
		set_category( $ids, $category );
		break;

	case "add_category":
		$category = $_GET["category"];
		print "setting category " . $category . "<br/>";
		$prod_ids = $_GET["prod_ids"];
		$ids      = explode( ',', $prod_ids );
		add_category( $ids, $category );
		break;

	case "set_supplier":
		$supplier_name = $_GET["supplier_name"];
		$prod_ids      = $_GET["prod_ids"];
		$ids           = explode( ',', $prod_ids );
		set_supplier_name( $ids, $supplier_name );
		break;

	case "set_vat":
		$prod_ids = $_GET["update_ids"];
		$ids      = explode( ',', $prod_ids );
		set_vat( $ids, $global_vat );
		break;
	case "show":
		$for_update = false;
		show_catalog( $for_update, $search_text, false, true );
		break;
	case "fresh_siton":
		show_fresh_siton();
		break;

	case "fresh_siton_order":
		$for_update = false;
		show_catalog( $for_update, $search_text, false, true, true, false, array( 15 ), true );
		break;

	case "cost_price_list":
		show_catalog( false, $search_text, false, true, false, true );
		break;

	case "fresh_buy":
		$for_update = false;
		show_catalog( $for_update, $search_text, false, true, false, true, array( 15 ) );
		break;

	case "for_update":
		$for_update = true;
		show_catalog( $for_update, $search_text, false );
		break;

	case "customer_prices":
		show_catalog( false, '', false, true, false, false, array( 15 ) );
		break;
}

function show_fresh_siton() {
	print header_text( true, true, true );

//	foreach ( array( 11, 12, 27, 28, ) as $categ ) {

	foreach ( array( 62, 18, 19, 66 ) as $categ ) {
		$term = get_term( $categ );

		print gui_header( 1, $term->name );
		show_catalog( 0, "", false, true, true, false, array( $term->term_id ), false );
	}

}

function add_category( $prod_ids, $category ) {

	// my_log($debug_string, __FILE__);
	foreach ( $prod_ids as $product_id ) {
		// my_log("set supplier " . $prod, __FILE__);
//		set_post_meta_field( $prod, "supplier_name", $supplier_name );
		print "prod " . $product_id . " " . get_product_name( $product_id );
		terms_add_category( $product_id, $category );

	}
}

function set_category( $prod_ids, $category ) {
//	print header_text(false);
//	var_dump($prod_ids); print "<br/>";
//	print $category . "<br/>";
	// my_log($debug_string, __FILE__);
	foreach ( $prod_ids as $product_id ) {
		// my_log("set supplier " . $prod, __FILE__);
//		set_post_meta_field( $prod, "supplier_name", $supplier_name );
//		print "prod " . $product_id . " " . get_product_name( $product_id );
		terms_remove_category( $product_id );
		terms_add_category( $product_id, $category );
	}
}

function show_catalog(
	$for_update, $search_text, $csv, $active = false, $siton = false, $buy = false, $category = null,
	$order = false, $suppliers = null
) {

	global $header_fields;
	$show_fields = array();

	for ( $i = 0; $i < CatalogFields::field_count; $i ++ ) {
		$show_fields[ $i ] = false;
	}

	$show_fields[ CatalogFields::name ] = true;

	if ( $siton ) {
		$show_fields[ CatalogFields::id ]    = true;
		$show_fields[ CatalogFields::price ] = true;
	}
	if ( $buy ) {
		$show_fields[ CatalogFields::id ]         = true;
		$show_fields[ CatalogFields::cost_price ] = true;
		$show_fields[ CatalogFields::supplier ]   = true;
	} else {
		$show_fields[ CatalogFields::vat ] = true;
	}

	$count = 0;
	my_log( "search_text = " . $search_text, "catalog-db-query.php" );
	$sql = 'select '
	       . ' id, post_title '
	       . ' from wp_posts '
	       . ' where post_type = \'product\'';

	if ( $search_text <> "" ) {
		if ( is_numeric( $search_text ) ) {
			$sql .= ' and id = ' . $search_text . ' ';
		} else {
			$ids = explode( "|", $search_text );
			if ( is_array( $ids ) and is_numeric( $ids[0] ) ) {
				$sql .= ' and id in (';
				foreach ( $ids as $id ) {
					$sql .= $id . ", ";
				}
				$sql = rtrim( $sql, ", " ) . ")";

			} else {
				$sql .= " and post_title like '%" . $search_text . "%'";
			}
		}
	}

	if ( $active ) {
		$sql .= ' and post_status = \'publish\'';
	}

	$sql .= ' order by 2';

	$result = sql_query( $sql );

	$data = "<table>";

	$data        .= gui_row( $header_fields, "", $show_fields );
	$line_number = 0;//	default:

	if ( $for_update ) {
		print "update<br/>";
		$show_fields[ CatalogFields::line_select ] = true;
		$show_fields[ CatalogFields::status ]      = true;
		$show_fields[ CatalogFields::category ]    = true;

	}
	if ( $order ) {
		$show_fields[ CatalogFields::order ] = true;
	}

	while ( $row = mysqli_fetch_row( $result ) ) {
		$fields = array();
		for ( $i = 0; $i < CatalogFields::field_count; $i ++ ) {
			$fields[ $i ] = "";
		}
		$line_number ++;
		$prod_id = $row[0];
		// prof_flag("handle " . $prod_id);
		$product_categories = array();

		$terms = get_the_terms( $prod_id, 'product_cat' );

		if ( $category ) { // Check if this product in the given categories
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$product_cat_id = $term->term_id;

					$parents = get_ancestors( $product_cat_id, 'product_cat' );

					array_push( $product_categories, $term->term_id );
					if ( $term->term_id == 341 )
						continue;
					foreach ( $parents as $parent ) {
						if ( $parent->term_id == 341 )
							continue;
						array_push( $product_categories, $parent );
					}
				}
			} else {
				// print "no terms for " . $prod_id . "<br/>";
				continue;
			}

			if ( sizeof( array_intersect( $product_categories, $category ) ) == 0 ) {
				continue;
			}
		}

		$fields[ CatalogFields::name ] = $row[1];
		// print "XXX" . CatalogFields::name . "XXX<br/>";

		$fields[ CatalogFields::line_select ] = "<input id=\"chk" . $prod_id . "\" class=\"product_checkbox\" type=\"checkbox\">";
		$fields[ CatalogFields::id ]          = $prod_id;

		// price
		$price = get_postmeta_field( $prod_id, '_price' );
		if ( $for_update ) {
			$fields[ CatalogFields::price ] = '<input type="text" value="' . $price . '">';
		} else {
			if ( $siton ) {
				$price = siton_price( $prod_id );
			} else if ( $buy ) {
				$price = get_buy_price( $prod_id );
			}
			$fields[ CatalogFields::price ] = $price;
		}

		// vat percent
		$vat_percent = get_postmeta_field( $prod_id, 'vat_percent' );
		if ( $vat_percent )
			$fields[ CatalogFields::vat ] = $vat_percent . "%";

		// $fields[CatalogFields::supplier] = get_postmeta_field( $prod_id, 'supplier_name' );

		$fields[ CatalogFields::status ] = get_post_status( $prod_id );

		if ( $buy ) {
			// prof_flag("alternative-start" . $prod_id);
			$alternatives = alternatives( $prod_id );
			$supplies     = "";
			foreach ( $alternatives as $alt ) {
				$supplies .= get_supplier_name( $alt->getSupplierId() ) . ", ";
			}

			$fields[ CatalogFields::supplier ] = rtrim( $suppliers, ", " );
		}

		$fields[ CatalogFields::cost_price ] = get_buy_price( $prod_id );
		$fields[ CatalogFields::supplier ]   = get_supplier( $prod_id);

		$show_in_list = 1;

		// $fields[CatalogFields::order] =gui_cell( gui_input( "prod_quantity" . $prod_id, "", null, "q_" . $prod_id ) );
		$fields[ CatalogFields::category ] = comma_implode( $terms );

		if ( $show_in_list ) {
			// var_dump($fields); print "<br/>";
			// var_dump($show_fields); print "<br/>";
			$data .= gui_row( $fields, "cat", $show_fields, $sums);
		}
		// prof_flag("end " . $prod_id);
		$count ++;
	}
	$data .= "</table>";
	if ( $csv ) {
		$data = str_replace( "</td><td>", ",", $data );
		$data = str_replace( "<tr>", "<br>", $data );
		$data = str_replace( "<td>", "", $data );
	}

	print $data;
}

?>

