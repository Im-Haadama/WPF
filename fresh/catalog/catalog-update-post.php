<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../r-shop_manager.php' );
require_once( 'bundles.php' );
require_once( 'catalog.php' );
require_once( '../pricing.php' );
require_once( ROOT_DIR . "/fresh/catalog/Basket.php" );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
my_log( "operation = " . $operation, "catalog-update-post.php" );

switch ( $operation ) {
	case "get_prices_change":
		$include_sale = $_GET["sale"];
		get_changed_prices( $include_sale );
		break;

	case "get_items_to_remove":
		get_items_to_remove();
		break;

	case "get_items_to_publish":
		get_items_to_publish();
		break;

	case "select_suppliers":
		$update_ids = $_GET["update_ids"];
		$ids        = explode( ',', $update_ids );
		my_log( $ids, "catalog-update-post.php" );
		select_suppliers( $ids );
		break;
	case "draft_items":
		$update_ids = $_GET["update_ids"];
		$ids        = explode( ',', $update_ids );
		// my_log ($ids, "catalog-update-post.php");
		catalog::DraftItems( $ids );
		break;
	case "publish_items":
		$update_ids = $_GET["update_ids"];
		$ids        = explode( ',', $update_ids );
		// my_log ($ids, "catalog-update-post.php");
		Catalog::PublishItems( $ids );
		break;

}

function select_suppliers( $update_ids ) {
	// $bl = new Bundles();
	my_log( "start update", "catalog-update-post.php" );
	for ( $pos = 0; $pos < count( $update_ids ); $pos += 2 ) {
		$product_id    = $update_ids[ $pos ];
		$priceslist_id = $update_ids[ $pos + 1 ];
		// $supplier_id = $update_ids[$pos + 1];
//        $supplier_id = $update_ids[$pos + 2];
//        $supplier_price = $update_ids[$pos + 3];

//        my_log("update product " . $product_id . /* " price = " . $price . */ " supplier_id= " .
//            $supplier /* . "supplier_price= ". $supplier_price*/ , "catalog-update-post.php");

		Catalog::SelectOption( $product_id, $priceslist_id );
//        update_post_meta($product_id, "_regular_price", $price);
//        update_post_meta($product_id, "_price", $price);
//        update_post_meta($product_id, "supplier_name", get_supplier_name($supplier));
//        update_post_meta($product_id, "buy_price", pricelist_get_price($product_id));
		// Check if there are bundles and update their prices.
//        $bl->UpdatePrice($product_id, );
	}
}

//function add_mapping($product_id, $supplier_id, $product_name, $pricelist_id)
//{
//    my_log("add_mapping");
//    $sql = "INSERT INTO im_supplier_mapping (product_id, supplier_id, supplier_product_name, pricelist_id)"
//        . "VALUES ( "
//        . $product_id . ", "
//        . $supplier_id . ', '
//        . $product_name . ', '
//        . $pricelist_id . ' )';
//
//}

function get_items_to_remove() {
	$sql = 'SELECT id, post_title FROM wp_posts'
	       . ' WHERE post_status = \'publish\''
	       . ' AND post_type = \'product\''
	       . ' AND id NOT IN (SELECT product_id'
	       . ' FROM im_supplier_mapping'
	       . ' WHERE (supplier_id, supplier_product_name)'
	       . ' IN (SELECT supplier_id, product_name FROM im_supplier_price_list))';

	$result = sql_query( $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>קוד פריט</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "<td>ספק</td>";
	$data .= "<td>מחיר נוכחי</td>";
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$product_id = $row[0];
		if ( is_basket( $product_id ) || is_bundle( $product_id ) ) {
			continue;
		}
		$product_name = $row[1];
		my_log( "to remove " . $product_name );

		$line = "<tr>";
		$line .= "<td><input id=\"chk" . $product_id . "\" class=\"remove_product_checkbox\" type=\"checkbox\"></td>";
		$line .= "<td>" . $product_id . "</td>";
		$line .= "<td>" . $product_name . "</td>";
		$line .= "<td>" . get_postmeta_field( $product_id, "supplier_name" ) . "</td>";
		$line .= "<td>" . get_postmeta_field( $product_id, "_price" ) . "</td>";
		$line .= "</tr>";


		$data .= $line;
	}
	print $data;
}

function get_items_to_publish() {
	$sql = 'SELECT mp.product_id, pl.product_name FROM im_supplier_price_list pl'
	       . ' JOIN wp_posts po, im_supplier_mapping mp'
	       . ' WHERE mp.product_id = po.id'
	       . ' AND po.post_status = \'draft\''
	       . ' AND pl.product_name = mp.supplier_product_name'
	       . ' AND pl.supplier_id = mp.supplier_id';

	$result = sql_query( $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>קוד פריט</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "<td>מחיר נוכחי</td>";
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$product_id   = $row[0];
		$product_name = $row[1];
		my_log( "to publish " . $product_name );

		$line = "<tr>";
		$line .= "<td><input id=\"chk" . $product_id . "\" class=\"publish_product_checkbox\" type=\"checkbox\"></td>";
		$line .= "<td>" . $product_id . "</td>";
		$line .= "<td>" . $product_name . "</td>";
		$line .= "<td>" . get_postmeta_field( $product_id, "_price" ) . "</td>";
		$line .= "</tr>";

		$data .= $line;
	}
	print $data;
}

// Write the result to screen. Caller will insert to result_table
function get_changed_prices( $include_sale ) {
//    print "include = " . $include_sale . "<br/>";
//
	// Get all active store products
	$sql = 'SELECT id, post_title'
	       . ' from wp_posts'
	       . ' where post_status = \'publish\''
	       . ' and post_type = \'product\'';

	$result = sql_query( $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>קוד פריט</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "<td>מחיר נוכחי</td>";
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$product_id   = $row[0];
		$product_name = $row[1];

		if ( is_basket( $product_id ) ) {
			continue;
		}

		$line = "<tr>";
		$line .= "<td><input id=\"chk" . $product_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
		$line .= "<td>" . $product_id . "</td>";
		$line .= "<td>" . $product_name . "</td>";
		$line .= "<td>" . get_postmeta_field( $product_id, "_price" ) . "</td>";
		$line .= "<td><select>";

		$min_new_price = 9999;

//        if (is_bundle($product_id)) {
//            $bundle = new Bundle($product_id);
//            $new_price = $bundle->CalculatePrice();
//            $supplier = $bundle->GetSupplier();
//            $supplier_price = $bundle->GetBuyPrice();
//            $line .= '<option value="' . $new_price . '" data-pricelist-id = ' . $pricelist_id . ' data-supplier-price = ' . $supplier_price . '>' . $new_price . ' ' .
//                $supplier . '</option>';
//            $min_new_price = $new_price;
//
//        } else {
		// Get all mapped supplier items
		$sql1 = 'SELECT pl.price, pl.supplier_id, pl.id FROM im_supplier_price_list pl'
		        . ' JOIN im_supplier_mapping mp'
		        . ' WHERE mp.product_id = ' . $product_id
		        . ' AND mp.supplier_id = pl.supplier_id'
		        . ' AND mp.supplier_product_name = pl.product_name';

		//print $sql1;
		// Get line options
		$result1 = sql_query( $sql );
		while ( $row1 = mysqli_fetch_row( $result1 ) ) {
			$supplier_price = $row1[0];
			$supplier_id    = $row1[1];
			$pricelist_id   = $row1[2];
//                print "product: " . $product_name . " supplier: " . get_supplier_name($supplier_id)
//                    . " supplier_price: " . $supplier_price . " id: " . $price_id . "<br/>";
			$new_price = calculate_price( $supplier_price, $supplier_id );
			if ( $new_price < $min_new_price ) {
				$min_new_price = $new_price;
			}
			$line .= '<option value="' . $new_price . '" data-pricelist-id = ' . $pricelist_id . ' data-supplier-price = ' . $supplier_price . '>' . $new_price . ' ' . get_supplier_name( $row1[1] ) . '</option>';
		}
//        }

		$line       .= '</select></td>';
		$line       .= "</tr>";
		$curr_price = get_price( $product_id );
		my_log( "name = " . $product_name . ", price = " . $curr_price . " min price = " . $min_new_price, "catalog-update-post.php" );
		$display_product = false;
		if ( $min_new_price != 9999 ) { // We have options from suppliers
			if ( $curr_price == "" or $curr_price > 0 ) { // Product has price
				if ( $curr_price == 0 || $min_new_price / $curr_price > 1.05 || $min_new_price / $curr_price < 0.95 ) {
					// Price changed by 5% or more
					$display_product = true;
				}
			} else {
				$display_product = true;
			}
		}
		// if (get_postmeta_field($product_id, "_sale_price") != null) $display_product = false;

		if ( get_buy_price( $product_id ) == 0 && ! is_bundle( $product_id ) ) {
			$display_product = 1;
		}
		$sale_price = get_sale_price( $product_id );
		if ( ( $sale_price > 0 ) and ! $include_sale ) {
//            print $product_id . " " . $sale_price . "<br/>";
			$display_product = false;
		}
//        if ($sale_price > 0 ) print $product_id . " " . $sale_price . " " . ! $include_sale . "<br/>";

		if ( $display_product ) {
			$data .= $line;
		}
	}

	print $data;
}

// select id from im_suppliers
// where date = (select max(date) from im_suppliers)
// and id not in (
// SELECT supp.id FROM `im_suppliers` supp
// join im_supplier_products prod
// where supp.supplier_id = prod.supplier_id
// and supp.product_code = prod.supplier_product_code)
?>

