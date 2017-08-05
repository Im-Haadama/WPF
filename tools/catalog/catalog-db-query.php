<?php
require_once( '../tools.php' );
require_once( "../gui/inputs.php" );
require_once( "../catalog/catalog.php" );

$search_text = $_GET["search_txt"];
$operation   = $_GET["operation"];

// Check connection
if ( $link->connect_error ) {
	die( "Connection failed: " . $conn->connect_error );
}

mysql_select_db( $dbname );

my_log( $operation, "catalog-db-query.php" );
switch ( $operation ) {
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
	default:
		$for_update = false;
		show_catalog( $for_update, '', true );
		break;
}

function show_catalog( $for_update, $search_text, $csv, $active = false, $siton = false, $buy = false, $category = null, $order = false ) {
	my_log( "search_text = " . $search_text, "catalog-db-query.php" );
	$sql = 'select '
	       . ' id, post_title '
	       . ' from wp_posts '
	       . ' where post_type = \'product\'';

	if ( $search_text <> "" ) {
		$sql .= ' and post_title like \'%' . $search_text . '%\'';
	}

	if ( $active ) {
		$sql .= ' and post_status = \'publish\'';
	}

	$sql .= ' order by 2';

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$data = "<table><tr>";
	if ( $for_update ) {
		$data .= "<td></td>";
	}
	$data .= "<td><h3>מזהה</h3></td><td><h3>שם פריט</h3></td><td><h3>מחיר</h3></td><td><h3>מעם</h3></td><td><h3>ספק</h3></td>";
	if ( $buy ) {
		$data .= "<td>רשימה</td>";
	}

	if ( $order ) {
		$data .= "<td>הזמנה</td>";
	}
	$data        .= "</tr>";
	$line_number = 0;

	while ( $row = mysql_fetch_row( $export ) ) {
		$line_number ++;
		$prod_id            = $row[0];
		$product_categories = array();
		if ( $category ) { // Check if this product in the given categories
			$terms = get_the_terms( $prod_id, 'product_cat' );
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
			$line         .= "<td>";
			$alternatives = alternatives( $prod_id );
			foreach ( $alternatives as $alt ) {
				$line .= get_supplier_name( $alt[1] ) . ", ";
			}

			$line = rtrim( $line, ", " );

			$line .= "</td>";

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
		$line .= "</tr>";

		// $line .= "<td>" . $categ . "</td>";
		// $line .= "</tr>";

		if ( $show_in_list ) {
			$data .= trim( $line );
		}
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

