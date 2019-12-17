<?php
/require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
require_once( "../catalog/catalog.php" );

//if ( ! im_user_can( "show_catalog" ) ) {
//	print "אין הרשאה";
//	die( 1 );
//}

$search_text = isset( $_GET["search_txt"] ) ? $_GET["search_txt"] : null;
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
		inventory = 10,
		field_count = 11;
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
	case "monthly":
		print show_monthly();
		break;
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

	case "pos":
		show_pos_pricelist();
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
		$term_info  = terms_get_id( $search_text );// , "product_cat");
//		var_dump($term_info);
		$term_array = null;
		if ( $term_info ) {
			$term_id = $term_info["term_id"];
			print "term id: " . $term_id . " <br/>";
			$term_array  = array( $term_id );
			$search_text = null;
		}
		show_catalog( $for_update, $search_text, false, false, false, false, $term_array );
		break;

	case "customer_prices":
		show_catalog( false, '', false, true, false, false, array( 15 ) );
		break;

	case "zero_inv":
		show_catalog( false, '', false, true, false, false, null, null, null, 0 );
		break;

	case "show_hidden":
		wp_cache_flush();
		$args = array(
			'post_type'  => 'product',
			'meta_key'   => '_visibility',
			'meta_value' => 'hidden'
		);

		$the_query = new WP_Query( $args );

		// The Loop
		if ( $the_query->have_posts() ) {
			echo '<ul>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				echo '<li>' . get_the_title() . '</li>';
			}
			echo '</ul>';
			/* Restore original Post Data */
			wp_reset_postdata();
		} else {
			// no posts found
		}

}

function show_pos_pricelist() {
	print header_text( true, true, true );

	$sql     = "SELECT prod_id FROM i_total WHERE q > 0";
	$results = sql_query( $sql );

	$data_lines = array();

	while ( $row = sql_fetch_row( $results ) ) {
		$prod_id = $row[0];
		$p       = new Product( $prod_id );
		if ( ! $p->isPublished() ) {
			continue;
		}
		$price = get_price( $prod_id, 5 );
		// array_push($lines, array(get_product_name($prod_id), $price));

		$terms = get_the_terms( $prod_id, 'product_cat' );
		// print $terms[0]->name . "<br/>";
		$prod_name = get_product_name( $prod_id );

		array_push( $data_lines, array(
			$terms[0]->name . "@" . get_product_name( $prod_id ),
			array( $prod_name, $price )
		) );
	}

	sort( $data_lines );

	$data = "<table>";
	$term = "";
	for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
		$line_term = strtok( $data_lines[ $i ][0], '@' );
		if ( $line_term <> $term ) {
			$term = $line_term;
			$data .= gui_row( array( $term, "" ) );
		}
		$line = gui_row( array( $data_lines[ $i ][1][0], $data_lines[ $i ][1][1] ) );
		$data .= trim( $line );
	}
	$data .= "</table>";

	print $data;
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
	delete_option( "category_children" );
	wp_cache_flush();
}

function show_catalog(
	$for_update, $search_text, $csv, $active = false, $siton = false, $buy = false, $category = null,
	$order = false, $suppliers = null, $inv = null
) {
	global $header_fields;
	$show_fields = array();

	// print "search text: " . $search_text . "<br/>";
	// print "category: " . $category . "<br/>";
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
//	}

	if ( $active ) {
		$sql .= ' and post_status = \'publish\'';
	}

	$sql .= ' order by 2';

	$result = sql_query( $sql );

	$data = "<table>";

	$data        .= gui_row( $header_fields, "", $show_fields );
	$line_number = 0;//	default:

	if ( $for_update ) {
		// print "update<br/>";
		$show_fields[ CatalogFields::line_select ] = true;
		$show_fields[ CatalogFields::status ]      = true;
		$show_fields[ CatalogFields::category ]    = true;

	}
	if ( ! is_null( $inv ) ) {
		print "show inv<br/>";
		$show_fields[ CatalogFields::id ]        = true;
		$show_fields[ CatalogFields::inventory ] = true;
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
			if ( $terms ) { // Products terms
				foreach ( $terms as $term ) {
					array_push( $product_categories, $term->term_id );
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
		$fields[ CatalogFields::id ]          = gui_hyperlink( $prod_id, "../../wp-admin/post.php?post=" . $prod_id . " &action=edit" );
		$p                                    = new Product( $prod_id );

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

		if ( ! is_null( $inv ) ) {
			if ( ! $p->getStockManaged() or $p->getStock() > $inv ) {
				continue;
			}
			$fields[ CatalogFields::inventory ] = $p->getStock();
		}

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

