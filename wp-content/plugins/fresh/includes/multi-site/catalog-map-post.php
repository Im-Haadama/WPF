<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
// require_once('multi-catalog.php');
require_once( '../r-shop_manager.php' );
require_once( "multi-site.php" );
require_once( 'simple_html_dom.php' );
require_once( "multi-site.php" );
require_once( "../gui/inputs.php" );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
$remote_id = $_GET["remote_id"];

my_log( __FILE__, $operation );
switch ( $operation ) {
	case "get_unmapped":
		my_log( "get_unmapped" );
		multi_search_unmapped_products( $remote_id );
		break;

//    case 'get_invalid_mapped':
//        search_invalid_mapping();
//        break;
//
//    case "remove_map":
//        $id_to_remove = $_GET["id_to_remove"];
//        $ids = explode(',', $id_to_remove);
//        remove_map($ids);
//        break;
//
	case "map":
		$map_ids = $_GET["ids"];
		$ids     = explode( ',', $map_ids );
		map_products( $remote_id, $ids );
		break;

	case "create_products":
		$category_name = $_GET["category_name"];
		my_log( "category: " . $category_name );
		$map_triplets = $_GET["create_info"];
		$ids          = explode( ',', $map_triplets );
		Catalog::CreateProducts( $category_name, $ids );
		break;
	default:
		my_log( __FILE__, "unhandled" );
}

//function create_products($category_name, $ids)
//{
//    $catalog = new Catalog();
//
//    my_log("Create_products. Category = " . $category_name);
//
//    for ($pos = 0; $pos < count($ids); $pos += 4) {
//        $product_name = urldecode($ids[$pos]);
//        $supplier_id = $ids[$pos + 1];
//        $pricelist_id = $ids[$pos + 2];
//        $supplier_product_code = $ids[$pos+3];
//        // print $product_name . ", " . $supplier_id . ", " . $pricelist_id . ", " . $supplier_product_code . "<br/>";
//
//        // Calculate the price
//        $pricelist = new PriceList($supplier_id);
//        $buy_price = $pricelist->GetByName($product_name);
//        $sell_price = calculate_price($buy_price, $supplier_id);
//
//        my_log("supplier_id = " . $supplier_id . " name = " . $product_name);
//        $id = create_product ($sell_price, $supplier_id, $product_name, $category_name);
//        // Create link to supplier price list
//        $catalog->AddMapping($id, $pricelist_id, MultiSite::LocalSiteID());
//        print "add mapp done<br/>";
//    }
//}

function create_product( $sell_price, $supplier_id, $product_name, $category_name ) {
	my_log( "title= " . $product_name . ", supplier_id=" . $supplier_id . ", sell_price=" . $sell_price, __METHOD__ );
	$post_information = array(
		'post_title'  => $product_name,
		// 'post_content' => 'this is new item shop',
		'post_status' => 'publish',
		'post_type'   => "product"
	);
	my_log( "calling wp_insert_post" );
	$post_id = wp_insert_post( $post_information, true );
	my_log( "after" );
	update_post_meta( $post_id, "_regular_price", $sell_price );
	update_post_meta( $post_id, "_price", $sell_price );
//    update_post_meta($post_id, "supplier_name", get_supplier_name($supplier_id));
	update_post_meta( $post_id, "_visibility", "visible" );
	wp_set_object_terms( $post_id, $category_name, 'product_cat' );
	// wc_set_term_order($category_id, $post_id);

	// print "create product done <br/>";
	return $post_id;
}


function map_products( $remote_id, $ids ) {
	my_log( __METHOD__, __FILE__ );
	for ( $pos = 0; $pos < count( $ids ); $pos += 2 ) {
		$local_prod_id  = $ids[ $pos ];
		$remote_prod_id = $ids[ $pos + 1 ];
		// my_log("product_id = " . $product_id . ", supplier_id=" . $supplier_id . ", product_name=" . $product_name);
		Core_Db_MultiSite::map( $remote_id, $local_prod_id, $remote_prod_id );
	}
}

function remove_map( $ids ) {
	$catalog = new Catalog();

	for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
		$map_id = $ids[ $pos ];
		$catalog->DeleteMapping( $map_id );
	}
}

function is_mapped( $code ) {
	$sql = 'SELECT id FROM `im_supplier_mapping` WHERE supplier_product_code = ' . $code .
	       ' AND supplier_product_code != 10';

	$result = sql_query( $sql );

	$row = mysqli_fetch_row( $result );

	if ( $row[0] > 0 ) {
		return true;
	}

	return false;
}

// Write the result to screen. Client will insert to result_table
// Get remote data as html and parse it
function multi_search_unmapped_products( $site_id ) {
	$remote_prods = array();

	$remote = get_site_tools_url( $site_id ) . "/catalog/catalog-db-query.php?operation=show";
	$html   = im_file_get_html( $remote );
	foreach ( $html->find( 'tr' ) as $row ) {
		$id               = $row->find( 'td', 0 )->plaintext;
		$name             = $row->find( 'td', 1 )->plaintext;
		$product_name_prf = name_prefix( $name );
//        print $product_name_prf . "<br/>";
		$price    = $row->find( 'td', 2 )->plaintext;
		$supplier = $row->find( 'td', 3 )->plaintext;

		if ( ! isset( $remote_prods[ $product_name_prf ] ) ) {
			$remote_prods[ $product_name_prf ] = array();
		}

		array_push( $remote_prods[ $product_name_prf ], array( $id, $name, $price, $supplier ) );
	}

	my_log( "multi_search_unmaaped_products" );
	// Purpose: read supplier items and map them to our database.
	// First get all unmapped items
	$sql = " SELECT id, post_title as product_name " .
	       " from im_products " .
	       " where " .
	       " (id, " . $site_id . ") not in " .
	       " (select local_prod_id, remote_site_id from im_multisite_map)";
	/// . ' supplier_product_code not in (select supplier_product_code from im_supplier_mapping)';

	$result = sql_query( $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
//    $data .= "<td>קוד</td>";
	$data .= "<td>מוצר שלנו </td>";
	$data .= "<td>חלופות באתר מרוחק</td>";
	$data .= "<td>תמונה מקומית</td>";
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$product_id = $row[0];
//        $supplier_id = $row[1];
		$product_name = $row[1];
//        $supplier_product_code = $row[4];

//        if (is_mapped($supplier_product_code)) {
//            my_log($supplier_product_code . " is mapped");
//            continue;
//        }

		$line = "<tr>";
		$line .= "<td><input id=\"chk" . $product_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
//        $line .= "<td>" . $supplier_product_code . "</td>";
		$line .= "<td>" . $product_name . "</td>";
//        $line .= "<td>" . $supplier_id . "</td>";
		$line .= "<td><select>";
//
		$product_name_prf = name_prefix( $product_name );
//        my_log($product_name_prf);
//
		if ( $pos = strpos( $product_name, " " ) ) {
			$product_name_prf = substr( $product_name, 0, $pos );
		}
		if ( isset( $remote_prods[ $product_name_prf ] ) ) {

			foreach ( $remote_prods[ $product_name_prf ] as $option ) {
				// $line .= '<option value="' . $row1[0] . '">' . $row1[1] . '</option>';
				$line .= '<option value="' . $option[0] . '">' . $option[1] . '</option>';
			}
		}
		$line .= '</select></td>';
//        $line .= "<td>" . get_supplier_name($supplier_id) . "</td>";
		$attachment_id = get_meta_field( $product_id, '_thumbnail_id' );
		if ( $attachment_id > 0 ) {
			$result = sql_query_single( "SELECT guid FROM wp_posts WHERE id = " . $attachment_id );
			if ( $result ) {
				$url = $result[0];
				if ( $i = strstr( $url, "wordpress" ) ) {
					$url = strstr( $url, "wordpress", true ) . substr( $i, 10 );
//                    $url = substr($url, 0, $i) . substr($url, $i+10);
//                    $url = substr($url, 10);
				}

//                print $url . "<br/>";
//                $line .= "<td>" . gui_image($url, 80, 80) . "</td>";
			} else {
				$line .= "<td></td>";
			}
		} else {
			$line .= "<td></td>";
		}
		$line .= "</tr>";
		$data .= $line;
	}
	print "<table>" . $data . "</table>";
}

function name_prefix( $name ) {
	return strtok( $name, " -()" );
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
//        $line .= "<td>" . get_supplier_name($supplier_id) . "</td>";
		$line .= "<td>" . $product_name . "</td>";

		$line .= "</tr>";
		$data .= $line;
	}
	print $data;
}

?>

die(0);

print "y";
require_once("orders-common.php");
print "z";

print header_text(false,true);
$operation = $_GET["operation"];
// my_log( "Operation: " . $operation, __FILE__ );

switch ( $operation ) {
case "create_order":
$email = $_GET["email"];
$params=  $_GET["params"];
express_create_order($email, explode(",", $params));
break;
}

function express_create_order($email, $params)
{
print "יוצר הזמנה ליוזר " . $email . "<br/>";
$user = get_user_by("email", $email);

if (! $user){
print "יוזר לא קיים";
die(0);
}
$prods = array();
$quantities = array();

for ($i = 0; $i < count($params); $i += 2){
array_push($prods, $params[$i]);
array_push($quantities, $params[$i+1]);
}

create_order($user->ID,0, $params, $quantities, "");
}
