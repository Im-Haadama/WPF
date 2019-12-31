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
		$product_id = $_GET["product_id"];
		$p          = new Fresh_Product( $product_id );
		$q          = $_GET["quantity"];
		if ( ! $q > 0 ) {
			$q = 0;
		}
		$margin = $_GET["margin"];
		if ( ! ( $margin > 0 ) and ! strstr( $margin, "%" ) ) {
			$margin = 0;
		}
		$b = Bundle::CreateNew( $product_id, $q, $margin );
		print $p->getBuyPrice() . "," . $p->getPrice() . "," . $b->CalculatePrice();
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
			$b       = Bundle::CreateFromDb( $item_id );
			$b->Delete();
		}
		break;

	case "add_item":
		$product_id = $_GET["product_id"];
		$quantity   = $_GET["quantity"];
		$margin     = $_GET["margin"];
		// print $product_id . " " . $quantity . " " . $margin . "<br/>";

		// Create in memory
		$bundle = Bundle::CreateNew( $product_id, $quantity, $margin );

		// var_dump($bundle);

		$bundle->CreateOrUpdate();
		break;

	case "update_all":
		$sql    = "select id from im_bundles";
		$result = sql_query( $sql );
		while ( $row = mysqli_fetch_row( $result ) ) {
			print $row[0] . "<br/>";
			$b = Bundle::CreateFromDb( $row[0] );
			$b->Update();
		}
		break;

	case "update":
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos += 3 ) {
			$item_id  = $params[ $pos ];
			$quantity = $params[ $pos + 1 ];
			$margin   = $params[ $pos + 2 ];
			$b        = Bundle::CreateFromDb( $item_id );
			$b->Update( $quantity, $margin );
		}
		break;

	case "disable":
		$id = $_GET["id"];
		if ( ! ( $id > 0 ) ) {
			die( "no id" );
		}
		$b = Bundle::CreateFromDb( $id );
		// var_dump($b);
		$b->disable();

}

?>

