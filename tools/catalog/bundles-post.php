<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */
require_once( '../im_tools.php' );
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

	case "update_price":
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos += 3 ) {
			$supplier_id  = $params[ $pos + 2 ];
			$price        = $params[ $pos + 1 ];
			$product_name = $params[ $pos ];
			my_log( "supplier_id " . $supplier_id, "pricelist-post.php" );
			my_log( "price " . $price, "pricelist-post.php" );
			my_log( "product_name " . $product_name, "pricelist-post.php" );
			$pl->Update( $price, $product_name );
		}
		break;

	case "delete_item":
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$item_id = $params[ $pos ];
			$bl->Delete( $item_id );
		}
		break;

	case "add_item":
		$product_id     = $_GET["product_id"];
		$quantity       = $_GET["quantity"];
		$margin         = $_GET["margin"];
		$bundle_prod_id = $_GET["bundle_prod_id"];
//        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
//        my_log("price " . $price, "pricelist-post.php");
//        my_log("product_name " . $product_name, "pricelist-post.php");
//        my_log("date " . date('Y-m-d'), "pricelist-post.php");
		$bundles = new Bundles();
		$bundles->Add( $product_id, $quantity, $margin, $bundle_prod_id );
		break;

	case "map":
		$map_triplets = $_GET["map_triplets"];
		$ids          = explode( ',', $map_triplets );
		map_products( $ids );
		break;
}

?>

