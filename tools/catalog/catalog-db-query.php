<?php
require_once( '../r-shop_manager.php' );
require_once( "../gui/inputs.php" );
require_once( "../catalog/catalog.php" );

$search_text = $_GET["search_txt"];
$operation   = $_GET["operation"];

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
		$for_update = false;
		show_catalog( $for_update, $search_text, false, true, true, false, array( 15, 390 ), false );
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
//	default:
//		$for_update = false;
//		show_catalog( $for_update, '', true );
//		break;
}
function set_category( $prod_ids, $category ) {

	// my_log($debug_string, __FILE__);
	foreach ( $prod_ids as $product_id ) {
		// my_log("set supplier " . $prod, __FILE__);
//		set_post_meta_field( $prod, "supplier_name", $supplier_name );
		print "prod " . $product_id . " " . get_product_name( $product_id );
		terms_add_category( $product_id, $category );

	}
}

function show_catalog(
	$for_update, $search_text, $csv, $active = false, $siton = false, $buy = false, $category = null,
	$order = false, $suppliers = null
) {

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

	$data = "<table><tr>";
	if ( $for_update ) {
		$data .= "<td></td>";
	}
	$data .= "<td><h3>מזהה</h3></td><td><h3>שם פריט</h3></td><td><h3>מחיר</h3></td><td><h3>מעם</h3></td><td><h3>ספק</h3></td>";
	$data .= gui_cell( gui_header( 3, "סטטוס" ) );
	$data .= gui_cell( gui_header( 3, "קטגוריה" ) );
	if ( $buy ) {
		$data .= "<td>רשימה</td>";
	}

	if ( $order ) {
		$data .= "<td>הזמנה</td>";
	}
	$data        .= "</tr>";
	$line_number = 0;//	default:
//		$for_update = false;
//		show_catalog( $for_update, '', true );
//		break;


	while ( $row = mysqli_fetch_row( $result ) ) {
		$line_number ++;
		$prod_id            = $row[0];
		// prof_flag("handle " . $prod_id);
		$product_categories = array();

		$terms = get_the_terms( $prod_id, 'product_cat' );

		if ( $category ) { // Check if this product in the given categories
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$product_cat_id = $term->term_id;

					$parents = get_ancestors( $product_cat_id, 'product_cat' );
					array_push( $product_categories, $term->term_id );
					foreach ( $parents as $parent ) {
						array_push( $product_categories, $parent );
					}
				}
			} else {
				print "no terms for " . $prod_id . "<br/>";
				continue;
			}
//			if ($prod_id == 6602) {
//				print "found: ";
//				var_dump($product_categories); print "<br/>";
//				print "wanted: ";
//				var_dump($category); print "<br/>";
//			}

			if ( sizeof( array_intersect( $product_categories, $category ) ) == 0 ) {
				continue;
			}
		}

		$prod_name = $row[1];

		// Display product line
		$line = "<tr>";
		if ( $for_update ) {
			$line .= "<td><input id=\"chk" . $prod_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
		}
		$line .= "<td>" . $prod_id . '</td>';
		$line .= '<td>' . $prod_name . '</td>';

		// price
		$price = get_postmeta_field( $prod_id, '_price' );
		$line  .= '<td>';
		if ( $for_update ) {
			$line .= '<input type="text" value="' . $price . '">';
		} else {
			if ( $siton ) {
				$price = siton_price( $prod_id );
			} else if ( $buy ) {
				$price = get_buy_price( $prod_id );
			}
			$line .= $price;
		}
		$line .= '</td>';

		// vat percent
		$vat_percent = get_postmeta_field( $prod_id, 'vat_percent' );
		$line        .= '<td>';
		if ( $vat_percent > 0 ) {
			$line .= $vat_percent . '%';
		}

		//
		$line .= '</td>';
		$line .= '<td>' . get_postmeta_field( $prod_id, 'supplier_name' ) . '</td>';

		if ( $for_update ) {
			$line .= '<td>' . get_post_status( $prod_id ) . '</td>';
		}

		if ( $buy ) {
			// prof_flag("alternative-start" . $prod_id);
			$line         .= "<td>";
			$alternatives = alternatives( $prod_id );
			foreach ( $alternatives as $alt ) {
//				if (! $suppliers or in_array($alt[1], $suppliers)) {
//
//				}

				$line .= get_supplier_name( $alt[1] ) . ", ";
			}

			$line = rtrim( $line, ", " );

			$line .= "</td>";
			// prof_flag("alternative-end" . $prod_id);
		}

		$show_in_list = 1;
//        $product_cats = wp_get_post_terms($prod_id, 'product_cat' );
//
//        // $line .= "<td>";
//        foreach ($product_cats as &$categ) {
//            $categ_name = $categ->name;
//            if ($categ_name == "משתלה" || $categ_name == "ציוד" || $categ_name == "גינון" || $categ_name == "שתילי חורף"
//                || $categ_name == "גינון" || $categ_name == "מארז כמות") $show_in_list = 0;
//            // $line .=  $categ_name . ", ";
//        }
		if ( $order ) {
			$line .= gui_cell( gui_input( "prod_quantity" . $prod_id, "", null, "q_" . $prod_id ) );
		}
		$line .= gui_cell( comma_implode( $terms ) );
		$line .= "</tr>";

		// $line .= "<td>" . $categ . "</td>";
		// $line .= "</tr>";

		if ( $show_in_list ) {
			$data .= trim( $line );
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

