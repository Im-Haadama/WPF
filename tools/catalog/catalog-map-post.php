<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( 'catalog.php' );
require_once( '../pricelist/pricelist.php' );
require_once( '../tools.php' );
require_once( '../multi-site/multi-site.php' );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
// print $operation . "<br/>";

switch ( $operation ) {
	case "get_unmapped":
		my_log( "get_unmapped" );
		search_unmapped_products();
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
		my_log( "hide" );
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
		my_log( "category: " . $category_name );
		$map_triplets = $_GET["create_info"];
		$ids          = explode( ',', $map_triplets );
		Catalog::CreateProducts( $category_name, $ids );
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
	my_log( "strart hide" );
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

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$row = mysql_fetch_row( $export );

	if ( $row[0] > 0 ) {
		// print $sql;
		return true;
	}

	return false;
}

// Write the result to screen. Client will insert to result_table
function search_unmapped_products() {
	search_unmapped_local();
	//  search_unmapped_remote();
}

function search_unmapped_remote() {
	global $conn;

	$data = "";

	$sql = "SELECT site_id, id FROM im_suppliers WHERE site_id > 0";

	$result = mysqli_query( $conn, $sql );

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
		$html   = file_get_html( $remote );
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


function search_unmapped_local() {
	global $conn;
//    my_log("search_unmaaped_products");
//    // Purpose: read supplier items and map them to our database.
//    // First get all unmapped items
//    $sql = 'SELECT id, supplier_id, product_name, date, supplier_product_code'
//        . ' from im_supplier_price_list'
//        . ' where '
//        . ' (supplier_id, product_name) not in '
//        . ' (select supplier_id, supplier_product_name from im_supplier_mapping)'
//        . ' group by supplier_id, product_name ';
//    // print $sql;
//        /// . ' supplier_product_code not in (select supplier_product_code from im_supplier_mapping)';
//
//    $export = mysql_query($sql) or die ("Sql error : " . mysql_error());

	$sql    = "SELECT id, supplier_id, product_name FROM im_supplier_price_list ORDER BY 2, 3";
	$result = mysqli_query( $conn, $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>קוד</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "<td>מזהה ספק</td>";
	$data .= "<td>מוצר שלנו </td>";
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) ) // mysql_fetch_row($export))
	{
		$pricelist_id = $row[0];
//        $supplier_id = $row[1];
//        $product_name = $row[2];
//        $supplier_product_code = $row[4];
//
//        if (is_mapped($supplier_product_code)) {
//            // print $supplier_product_code . "is mapped<br/>";
////            my_log($supplier_product_code . " is mapped");
//            continue;
//        }
		// print $pricelist_id . "<br/>";

		$pricelist = PriceList::Get( $pricelist_id );
//        $sql = " select product_name, supplier_id, date, price, supplier_product_code from im_supplier_price_list " .

		$prod_link_id = Catalog::GetProdID( $pricelist_id );
		$prod_id      = $prod_link_id[0];
		if ( ( $prod_id == - 1 ) or ( $prod_id > 0 ) ) {
			continue;
		}
		$data .= print_unmapped( $pricelist_id, $pricelist["supplier_product_code"],
			$pricelist["product_name"], $pricelist["supplier_id"] );
	}
	print $data;
}

function print_unmapped( $pricelist_id, $supplier_product_code, $product_name, $supplier_id, $site_id = 0 ) {
	$match            = false;
	$product_name_prf = name_prefix( $product_name );
//        my_log($product_name_prf);

	if ( $pos = strpos( $product_name, " " ) ) {
		$product_name_prf = substr( $product_name, 0, $pos );
	}
	$sql1 = 'SELECT DISTINCT id, post_title FROM `wp_posts` WHERE '
	        . ' post_title LIKE \'%' . addslashes( $product_name_prf ) . '%\' AND post_type IN (\'product\', \'product_variation\')'
	        . ' AND (post_status = \'publish\' OR post_status = \'draft\')' .
	        ' OR id IN ' .
	        '(SELECT object_id FROM wp_term_relationships WHERE term_taxonomy_id IN ' .
	        '(SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id IN ' .
	        "(SELECT term_id FROM wp_terms WHERE name LIKE '%" . addslashes( $product_name_prf ) . "%'))) ORDER BY 2";

	$striped_prod = $product_name;
	foreach ( array( "אורגני", "יחידה", "טרי" ) as $word_to_remove ) {
		$striped_prod = str_replace( $word_to_remove, "", $striped_prod );
//		print $word_to_remove . " " . $striped_option . "<br/>";
	}

	$striped_prod = trim( $striped_prod );

//	if (substr($striped_prod, 0, 8) == "סברס") print "Y" . $striped_prod . "Y<br/>";

	// Get line options
	$options = "";
	$export1 = mysql_query( $sql1 );
	while ( $row1 = mysql_fetch_row( $export1 ) ) {
//        print $row1[1] . " " . $product_name . "<br/>";
		$striped_option = $row1[1];
		$striped_option = str_replace( "-", " ", $striped_option );
		$striped_option = trim( $striped_option, " " );
//        if (substr($striped_option, 0, 8) == "סברס") print "X" . $striped_option . "X<br/>";
		$options .= '<option value="' . $row1[0] . '" ';
		if ( ! strcmp( $striped_option, $striped_prod ) ) {
			$options .= 'selected';
			$match   = true;
		}
		$options .= '>' . $row1[1] . '</option>';
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

	$line .= "</tr>";


//    print $line;
	return $line;
}

function name_prefix( $name ) {
	return strtok( $name, "-()" );
//    $pos = strpos($name, "-()");
//    my_log($name . ": pos= " . $pos);
//    if ($pos > 0)
//        return substr($name, 0, $pos);
//
//    return $name;
}

// Write the result to screen. Client will insert to result_table
function search_invalid_mapping() {
	// Purpose: read supplier items and map them to our database.
	// First get all unmapped items
//    $sql = 'SELECT id, product_id, supplier_id, supplier_product_name FROM `im_supplier_mapping` WHERE (supplier_id, supplier_product_name) not in' .
//        ' (select supplier_id, product_name from im_supplier_price_list)' ;
	$sql = 'SELECT id, supplier_id, supplier_product_name, supplier_product_code
            FROM im_supplier_mapping';


	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>מזהה מיפוי</td>";
	$data .= "<td>מזהה מוצר</td>";
	$data .= "<td>מזהה ספק</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "</tr>";

	while ( $row = mysql_fetch_row( $export ) ) {
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

