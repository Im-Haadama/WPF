<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */

require_once( '../r-shop_manager.php' );
require_once( 'pricelist.php' );
require_once( '../catalog/catalog.php' );
require_once( '../multi-site/imMulti-site.php' );

?>
<?php
// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
$option = GetParam("option");

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
		$args = array();
		if ($option) $args[$option] = "yes";

		$pl->PrintHTML( isset( $_GET["ordered"] ) ? 1 : 0, isset( $_GET["need_supply"] ) ? 1 : 0 , $args);
		break;

	case "get_csv":
		$pl->PrintCSV();
		break;

	case "refresh_prices":
		print "XXXX";
		print header_text( false, true, false );
		$pl->Refresh();
		break;

	case "update_price":
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos += 2 ) {
			$line_id = $params[ $pos ];
//            $supplier_id = $params[$pos + 2];
			$price = $params[ $pos + 1 ];
//                        $product_name_code = $params[$pos];
//            my_log("supplier_id " . $supplier_id, "pricelist-post.php");
			MyLog( "price " . $price, "pricelist-post.php" );
//            my_log("product_name " . $product_name_code, "pricelist-post.php");
//			$regular_price, $sale_price = 0, $product_name = null, $code = 10, $category = null, &$id, $parent_id = null,
//		$picture_path = null

			// Todo - display and update sale price
			$pl->Update( $line_id, $price, 0 );
		}
		break;

	case "managed":
		if ( ! isset ( $_GET["prod_id"] ) ) {
			die( "send prod_id" );
		};
		if ( ! isset ( $_GET["is_managed"] ) ) {
			die( "send is_managed" );
		};
		$prod_id    = $_GET["prod_id"];
		$is_managed = $_GET["is_managed"] == "1";
		$P          = new Fresh_Product( $prod_id );
//		print "fresh? " . $P->isFresh() . "<br/>";
		$P->setStockManaged( $is_managed, $P->isFresh() ? "yes" : "no" );
		break;

	case "inactive":
		print "Remove lines from list->draft items<br/>";
		$pl->RemoveLines( 1 );
		print "Remove the list<br/>";
		$sql = "UPDATE im_suppliers SET is_active = 0 WHERE id = " . $supplier_id;
		sql_query( $sql );
		break;

	case "delete_price":
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$price_id = $params[ $pos ];
			$pl->Delete( $price_id );
		}
		print "done";
		break;

	case "delete_map":
		print "start delete<br/>";
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$pricelist_id = $params[ $pos ];
			// print $map_id . "<br/>";
			PriceList::DeleteMapping( $pricelist_id );
		}
		print "done delete";
		break;

	case "dont_price":
		MyLog( "start dont sell" );
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$price_id = $params[ $pos ];

			MyLog( "hiding " . $price_id );

			$cat->HideProduct( $price_id );
		}
		MyLog( print "done dont sell" );
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
		print gui_table_args( array(
			gui_row( array( gui_cell( "תאריך עדכון אחרון " ), gui_cell( " מרווח מכירה " ) ) ),
			gui_row( array( gui_cell( $pl->GetUpdateDate() ), gui_cell( $pl->GetFactor() ) ) )
		) );

		break;

	case "add_price":
		$product_name = $_GET["product_name"];
		$price        = $_GET["price"];
		$code         = 10;
		if ( isset( $_GET["code"] ) ) {
			$code = $_GET["code"];
		}

//        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
//        my_log("price " . $price, "pricelist-post.php");
//        my_log("product_name " . $product_name, "pricelist-post.php");
//        my_log("date " . date('Y-m-d'), "pricelist-post.php");
//        print "Adding " . $product_name . " " . " price: " . $price . "<br/>";
		$pl->AddOrUpdate( trim( $price ), '', $product_name, $code, "", $pricelist_id, 0 );
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
		MyLog( "update in slave" . $product_name . " " . $price . " " . $line_id );
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
		break;

	case 'get_prod':
		$pricelist_id = $_GET["pricelist"];
		$prod_link_id = Catalog::GetProdID( $pricelist_id, true );

		$prod_id = $prod_link_id[0];
		print $prod_id;
		break;

}

?>
