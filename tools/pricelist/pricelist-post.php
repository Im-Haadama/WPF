<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */
require_once( '../tools_wp_login.php' );
require_once( 'pricelist.php' );
require_once( '../catalog/catalog.php' );
require_once( '../multi-site/multi-site.php' );
?>
<?php
// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
///my_log("Operation: " . $operation, __FILE__);

$supplier_id = $_GET["supplier_id"];
$pl          = new PriceList( $supplier_id );
$cat         = new Catalog();

$pricelist_id = 0;

$debug = false;
if ( $debug ) {
	print $operation;
}
switch ( $operation ) {
	case "get_priceslist":
		print
			print_pricelist( $supplier_id );
		break;

	case "get_csv":
		$pl->PrintCSV();
		break;

	case "update_price":
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos += 2 ) {
			$line_id = $params[ $pos ];
//            $supplier_id = $params[$pos + 2];
			$price = $params[ $pos + 1 ];
//                        $product_name_code = $params[$pos];
//            my_log("supplier_id " . $supplier_id, "pricelist-post.php");
			my_log( "price " . $price, "pricelist-post.php" );
//            my_log("product_name " . $product_name_code, "pricelist-post.php");
			$pl->Update( $line_id, $price );
		}
		break;

	case "delete_price":
		print "start delete";
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$price_id = $params[ $pos ];

//            $sql = 'SELECT product_name, price FROM im_supplier_price_list'
//                . ' where id = ' . $price_id;
//
//            $export = mysql_query($sql) or die ("Sql error : " . mysql_error());
//
//            my_log("delete price " . $price_id . " product " . $row[0] . "price " . $row[1]);
			$pl->Delete( $price_id );
		}
		print "done delete";
		break;

	case "delete_map":
		print "start delete";
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$map_id = $params[ $pos ];
			$cat->DeleteMapping( $map_id );
//			$pl->Delete($price_id);
		}
		print "done delete";
		break;

	case "dont_price":
		my_log( "start dont sell" );
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$price_id = $params[ $pos ];

			my_log( "hiding " . $price_id );

			$cat->HideProduct( $price_id );
		}
		my_log( print "done dont sell" );
		break;
	case "is_slave":
		$id  = $_GET["supplier_id"];
		$sql = "SELECT master FROM im_multisite_pricelist WHERE supplier_id = " . $id;
		// print $sql;
		$r = sql_query_single( $sql );
//        print $r[0];
		if ( $r[0] == '0' ) {
			print "slave";
		} else {
			print "master";
		}
		break;

	case "header":
		print gui_table( array(
			gui_row( array( gui_cell( "תאריך עדכון אחרון " ), gui_cell( " מרווח מכירה " ) ) ),
			gui_row( array( gui_cell( $pl->GetUpdateDate() ), gui_cell( $pl->GetFactor() ) ) )
		) );

		break;

	case "add_price":
		$product_name = $_GET["product_name"];
		$price        = $_GET["price"];
//        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
//        my_log("price " . $price, "pricelist-post.php");
//        my_log("product_name " . $product_name, "pricelist-post.php");
//        my_log("date " . date('Y-m-d'), "pricelist-post.php");
//        print "Adding " . $product_name . " " . " price: " . $price . "<br/>";
		$pl->AddOrUpdate( trim( $price ), '', $product_name, 10, "", $pricelist_id, 0 );
// function AddOrUpdate( $regular_price, $sale_price, $product_name, $code = 10, $category, &$id, $parent_id = null ) {

		break;

	case "add_prices":
		// print "Params: " . $_GET["Params"] . "<br/>";
		$params = explode( ',', $_GET["Params"] );
		// var_dump($params);
		for ( $pos = 0; $pos < count( $params ); $pos += 2 ) {
			$product_name = $params[ $pos + 0 ];
			$price        = $params[ $pos + 1 ];
			//  print "Adding " . $product_name . " " . " price: " . $price . "<br/>";

			$pl->AddOrUpdate( $price, '', $product_name, 10, "", $pricelist_id, 0 );
			print $pricelist_id . "<br/>";
		}
		break;


//    case "add_in_slave":
//        print "add_in_slave";
//        $product_name = $_GET["product_name"];
//        $price = $_GET["price"];
//        $line_id = $_GET["line_id"];
////        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
////        my_log("price " . $price, "pricelist-post.php");
////        my_log("product_name " . $product_name, "pricelist-post.php");
////        my_log("date " . date('Y-m-d'), "pricelist-post.php");
//        $pl->AddInSlave(trim($price), $product_name, $line_id);
//        break;

	case "update_in_slave":
		$product_name = $_GET["product_name"];
		$price        = $_GET["price"];
		$line_id      = $_GET["line_id"];
		my_log( "update in slave" . $product_name . " " . $price . " " . $line_id );
//        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
//        my_log("price " . $price, "pricelist-post.php");
//        my_log("product_name " . $product_name, "pricelist-post.php");
//        my_log("date " . date('Y-m-d'), "pricelist-post.php");
		$pl->UpdateInSlave( trim( $price ), $line_id );
		break;

	case "map":
		$map_triplets = $_GET["map_triplets"];
		$ids          = explode( ',', $map_triplets );
		map_products( $ids );
		break;

	case 'change_status':
		$status      = $_GET["status"];
		$supplier_id = $_GET["supplier_id"];
		$PL          = new PriceList( $supplier_id );
		$PL->ChangeStatus( $status );
		break;

	case 'remove_status':
		$status      = $_GET["status"];
		$supplier_id = $_GET["supplier_id"];
		$PL          = new PriceList( $supplier_id );
		$PL->RemoveLines( $status );

}

function print_pricelist( $supplier_id ) {
	// global $debug;

//    $site_id = sql_query_single_scalar('select site_id from im_suppliers where id = ' . $supplier_id);
//
//    if ($site_id > 0) {
//        // remote
//        $data = "<tr>";
//        $data .= "<td>בחר</td>";
//        $data .= "<td>קוד מוצר</td>";
//        $data .= "<td>שם מוצר</td>";
//        $data .= "<td>תאריך שינוי</td>";
//        $data .= "<td>מחיר קנייה</td>";
//        $data .= "<td>מחיר מחושב</td>";
//        $data .= "<td>פריט מקושר</td>";
//        $data .= "<td>מחיר מכירה</td>";
//        $data .= "<td>מחיר מבצע</td>";
//        $data .= "<td>מזהה</td>";
//        $data .= "</tr>";
//
//        print $data;
//
//        $remote = get_site_tools_url($site_id) . "/catalog/get-as-pricelist.php";
//        $html = file_get_html($remote);
//        foreach($html->find('tr') as $row) {
//            $prod_id = $row->find('td', 1)->plaintext;
//            //print "prod id " . $prod_id;
//            $name = $row->find('td', 2)->plaintext;
//            $date = $row->find('td', 3)->plaintext;
//            $price = $row->find('td', 4)->plaintext;
//            $local_prod_id = multisite_map_get_remote($prod_id, $site_id);
//            print print_html_line($name, $price, $date, $prod_id, $prod_id, 5, $local_prod_id, false);
//        }
//    } else {
//        // Local
	global $pl;
	$pl->PrintHTML();
//    }
}

?>
