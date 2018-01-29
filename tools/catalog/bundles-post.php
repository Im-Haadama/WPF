<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */
require_once( '../r-shop_manager.php' );
require_once( '../catalog/bundles.php' );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
my_log( "Operation: " . $operation, __FILE__ );

$bl = new Bundles();


switch ( $operation ) {
	case "get_bundles":
		$bl->PrintHTML();
		break;

	case "calculate":
		$product_name = $_GET["product_name"];
		$q            = $_GET["q"];
		$margin       = $_GET["margin"];
		$b            = Bundle::createNew( $product_name, $q, $margin );
		break;

	case "update_price":
		// TODO: update bundle

//		$params = explode( ',', $_GET["params"] );
//		for ( $pos = 0; $pos < count( $params ); $pos += 3 ) {
//			$supplier_id  = $params[ $pos + 2 ];
//			$price        = $params[ $pos + 1 ];
//			$product_name = $params[ $pos ];
//			my_log( "supplier_id " . $supplier_id, "pricelist-post.php" );
//			my_log( "price " . $price, "pricelist-post.php" );
//			my_log( "product_name " . $product_name, "pricelist-post.php" );
//			$pl->Update( $price, $product_name );
//		}


		break;

	case "delete_item":
		my_log( "operation delete bundle", __FILE__ );
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$item_id = $params[ $pos ];
			$b       = Bundle::load( $item_id );
			$b->Delete();
		}
		break;

	case "add_item":
//		var request = post_url + "?operation=add_item&product_name=" + encodeURI(prod_name) + '&quantity=' + quantity +
//		              '&margin=' + margin + '&bundle_prod_name=' + encodeURI(bundle_prod_name);

		$product_name = urldecode( $_GET["item_name"] );
		$quantity     = $_GET["quantity"];
		$margin       = $_GET["margin"];
		// $bundle_prod_name = urldecode( $_GET["bundle_prod_name"] );
//        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
//        my_log("price " . $price, "pricelist-post.php");
//        my_log("product_name " . $product_name, "pricelist-post.php");
//        my_log("date " . date('Y-m-d'), "pricelist-post.php");
		$bundles = new Bundles();
		$bundles->Add( $product_name, $quantity, $margin );
		break;

	case "update_all":
		$sql    = "select id from im_bundles";
		$result = sql_query( $sql );
		while ( $row = mysqli_fetch_row( $result ) ) {
			print $row[0] . "<br/>";
			$b = Bundle::createFromDb( $row[0] );
			$b->Update();
		}
		break;

	case "update":
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos += 2 ) {
			$item_id = $params[ $pos ];
			$margin  = $params[ $pos + 1 ];
			$b       = Bundle::createFromDb( $item_id );
			$b->UpdateMargin( $margin );
		}
		break;
}

?>

