<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../r-shop_manager.php' );
require_once( 'catalog.php' );
require_once( '../pricelist/pricelist.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( "../wp/terms.php" );

// print header_text();

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
// print $operation . "<br/>";

switch ( $operation ) {
	case "get_unmapped":
		MyLog( "get_unmapped" );
		search_unmapped_products();
		break;

	case "create_term":
		MyLog( $operation );
		$category_name = $_GET["category_name"];
		terms_create( $category_name );
		break;

	case "get_unmapped_terms":
		MyLog( "get_unmapped_terms" );
		search_unmapped_terms();
		break;

	case 'get_invalid_mapped':
		search_invalid_mapping();
		break;

	case "remove_map":
		$id_to_remove = $_GET["id_to_remove"];
		$ids          = explode( ',', $id_to_remove );
		remove_map( $ids );
		break;

	case "hide":
		MyLog( "hide" );
		$id_ = $_GET["ids"];
		$ids = explode( ',', $id_ );
		hide_product( $ids );
		break;

	case "map":
		$ids = $_GET["ids"];
		$ids = explode( ',', $ids );
		map_products( $ids );
		break;

	case "create_products":
		$category_name = $_GET["category_name"];
		MyLog( "category: " . $category_name );
		$create_info = GetParamArray( "create_info" );
		//var_dump($ids);
		Catalog::CreateProducts( $category_name, $create_info );
		break;
}

function map_products( $ids ) {
	print "start mapping<br/>";

//    my_log(__METHOD__, __FILE__);
	for ( $pos = 0; $pos < count( $ids ); $pos += 3 ) {
		$site_id      = $ids[ $pos ];
		$product_id   = $ids[ $pos + 1 ];
		$pricelist_id = $ids[ $pos + 2 ];
//        my_log("product_id = " . $product_id . ", supplier_id=" . $supplier_id . ", product_name=" . $product_name);
		print "adding " . $site_id . " " . $product_id . " " . $pricelist_id . "<br/>";
		Catalog::AddMapping( $product_id, $pricelist_id, $site_id );
	}
}

function remove_map( $ids ) {
	$catalog = new Catalog();

	for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
		$map_id = $ids[ $pos ];
		$catalog->DeleteMapping( $map_id );
	}
}

// Hide this items.
function hide_product( $ids ) {
	MyLog( "start hide" );
//    print "hide";
	$catalog = new Catalog();

	for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
		$pricelist_id = $ids[ $pos ];
		$catalog->HideProduct( $pricelist_id );
	}
//    print "done";

//    for ($pos = 0; $pos < count($ids); $pos ++) {
//        $prod_id = $ids[$pos];
//        my_log("hide prod " . $prod_id);
//        $catalog->HideProduct($prod_id);
//    }
}

function is_mapped( $code ) {
	$sql = 'SELECT id FROM `im_supplier_mapping` WHERE supplier_product_code = ' . $code .
	       ' AND supplier_product_code != 10';

	$id = sql_query_single_scalar( $sql );

	if ( $id > 0 ) {
		// print $sql;
		return true;
	}

	return false;
}

// Write the result to screen. Client will insert to result_table
//function search_unmapped_products() {
//	search_unmapped_local();
//	//  search_unmapped_remote();
//}

function search_unmapped_remote() {

	$data = "";

	$sql = "SELECT site_id, id FROM im_suppliers WHERE site_id > 0";

	$result = sql_query( $sql );

	if ( ! $result ) {
		sql_error( $sql );

		return "No result";
	}

	while ( $row = mysqli_fetch_row( $result ) ) {
		$site_id     = $row[0];
		$supplier_id = $row[1];
		print $site_id . " " . $supplier_id . "<br/>";

		// print $site_id;

		$remote = get_site_tools_url( $site_id ) . "/catalog/get-as-pricelist.php";
		$html   = im_file_get_html( $remote );
		foreach ( $html->find( 'tr' ) as $row ) {
			$prod_id = $row->find( 'td', 1 )->plaintext;
			//print "prod id " . $prod_id;
			$name          = $row->find( 'td', 2 )->plaintext;
			$date          = $row->find( 'td', 3 )->plaintext;
			$price         = $row->find( 'td', 4 )->plaintext;
			$local_prod_id = multisite_map_get_remote( $prod_id, $site_id );

			if ( ! ( $local_prod_id > 0 ) and $local_prod_id != - 1 ) {
				$data .= print_unmapped( $prod_id, $prod_id, $name, $supplier_id, $site_id );
			}
		}
	}
	print $data;
}

function search_unmapped_products()
{
	$sql    = "SELECT id, supplier_id, product_name " .
	          " FROM im_supplier_price_list ORDER BY 2, 3";
	$result = sql_query( $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>קוד</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "<td>מזהה ספק</td>";
	$data .= "<td>מוצר שלנו </td>";
	$data .= gui_cell( "קטגוריה" );
	$data .= gui_cell( "תמונה" );
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) )
	{
		$pricelist_id = $row[0];

		$pricelist = PriceList::Get( $pricelist_id );

		$prod_link_id = Catalog::GetProdID( $pricelist_id, true );

		if ( $prod_link_id ) {
			$prod_id = $prod_link_id[0];
			if ( ( ( $prod_id == - 1 ) or ( $prod_id > 0 ) ) and get_post_status( $prod_id ) != 'trash' ) {
				continue;
			}
		}
		$data .= print_unmapped( $pricelist_id, $pricelist["supplier_product_code"], $pricelist["product_name"],
			$pricelist["supplier_id"] );
	}
	print $data;
}

function search_unmapped_terms() {
	// print header_text();
	$all_terms      = array();
	$all_terms_flat = array();
	$sql            = "SELECT id, supplier_id, product_name " .
	                  " FROM im_supplier_price_list ORDER BY 2, 3";
	$result         = sql_query( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$pricelist_id = $row[0];

		$prod_link_id = Catalog::GetProdID( $pricelist_id, true );

		$prod_id = $prod_link_id[0];
		if ( ( ( $prod_id == - 1 ) or ( $prod_id > 0 ) ) and get_post_status( $prod_id ) != 'trash' ) {
			continue;
		}

		$item      = PriceList::Get( $pricelist_id );
		$prod_term = $item["category"];
		$terms     = explode( ",", $prod_term );
		foreach ( $terms as $term ) {
			// print "term: " . $term . "<br/>";
			if ( ! in_array( $term, $all_terms_flat ) ) {
				array_push( $all_terms_flat, $term );
				array_push( $all_terms, array( 'term' => $term, 'id' => terms_get_id( $term ) ) );
			}
		}
	}
	// var_dump($all_terms);

	// print gui_select_datalist("term", "name", $all_terms, "");
	print gui_select( "create_term", "term", $all_terms, "", "" );
}


function print_unmapped( $pricelist_id, $supplier_product_code, $product_name, $supplier_id, $site_id = 0 ) {
	$match            = false;

//	if (substr($striped_prod, 0, 8) == "סברס") print "Y" . $striped_prod . "Y<br/>";

	$striped_prod = $product_name;
	foreach ( array( "אורגני", "יחידה", "טרי" ) as $word_to_remove ) {
		$striped_prod = str_replace( $word_to_remove, "", $striped_prod );
//		print $word_to_remove . " " . $striped_option . "<br/>";
	}

	$striped_prod = trim( $striped_prod );

	$prod_options = Catalog::GetProdOptions( $product_name );

	$options = "";

	foreach ( $prod_options as $row1 ) {

		// Get line options
//         print $row1[1] . " " . $product_name . "<br/>";
		// var_dump($row1); print "<br/>";
		$striped_option = $row1["post_title"];
		$striped_option = str_replace( "-", " ", $striped_option );
		$striped_option = trim( $striped_option, " " );
//        if (substr($striped_option, 0, 8) == "סברס") print "X" . $striped_option . "X<br/>";
		$options .= '<option value="' . $row1["id"] . '" ';
		if ( ! strcmp( $striped_option, $striped_prod ) ) {
			$options .= 'selected';
//			$match   = true;
		}
		$options .= '>' . $row1["post_title"] . '</option>';
	}

	$line = "<tr>";
	$line .= "<td>" . gui_checkbox( "chk" . $pricelist_id, "product_checkbox", $match ) . "</td>";
	$line .= "<td>" . $supplier_product_code . "</td>";
	$line .= "<td>" . $product_name . "</td>";
	$line .= "<td>" . $supplier_id . "</td>";
	$line .= "<td><select onchange='selected(this)' id='$pricelist_id'>";

	$line .= $options;

	$line .= '</select></td>';
	$line .= "<td>" . get_supplier_name( $supplier_id ) . "</td>";
	if ( $site_id > 0 ) {
		$line .= "<td style=\"display:none;\">" . $site_id . "</td>";
	}

	$item = PriceList::Get( $pricelist_id );
	$line .= gui_cell( $item["category"] );
	$url  = $item["picture_path"];
	$line .= gui_cell( basename( $url ) );
	$line .= "</tr>";

	return $line;
}

// Write the result to screen. Client will insert to result_table
function search_invalid_mapping() {
	// Purpose: read supplier items and map them to our database.
	// First get all unmapped items
//    $sql = 'SELECT id, product_id, supplier_id, supplier_product_name FROM `im_supplier_mapping` WHERE (supplier_id, supplier_product_name) not in' .
//        ' (select supplier_id, product_name from im_supplier_price_list)' ;
	$sql = 'SELECT id, supplier_id, supplier_product_name, supplier_product_code
            FROM im_supplier_mapping';


	$result = sql_query( $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>מזהה מיפוי</td>";
	$data .= "<td>מזהה מוצר</td>";
	$data .= "<td>מזהה ספק</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$line_id      = $row[0];
		$product_id   = $row[1];
		$supplier_id  = $row[2];
		$product_name = $row[3];

		$line = "<tr>";
		$line .= "<td><input id=\"chk" . $product_id . "\" class=\"invalid_map_checkbox\" type=\"checkbox\"></td>";
		$line .= "<td>" . $line_id . "</td>";
		$line .= "<td>" . $product_id . "</td>";
		$line .= "<td>" . get_supplier_name( $supplier_id ) . "</td>";
		$line .= "<td>" . $product_name . "</td>";

		$line .= "</tr>";
		$data .= $line;
	}
	print $data;
}

?>

