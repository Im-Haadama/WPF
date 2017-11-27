<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/12/15
 * Time: 10:07
 */
require_once( '../r-shop_manager.php' );
require_once( '../pricelist/pricelist.php' );
require_once( '../wp/terms.php' );
require_once( '../pricing.php' );

class Catalog {
	static function CreateProducts( $category_name, $ids ) {
		my_log( "Create_products. Category = " . $category_name );

		for ( $pos = 0; $pos < count( $ids ); $pos += 4 ) {
			$product_name          = urldecode( $ids[ $pos ] );
			$supplier_id           = $ids[ $pos + 1 ];
			$pricelist_id          = $ids[ $pos + 2 ];
			$supplier_product_code = $ids[ $pos + 3 ];
			print $product_name . ", " . $supplier_id . ", " . $pricelist_id . ", " . $supplier_product_code . "<br/>";

			// Calculate the price
			$pricelist = new PriceList( $supplier_id );
			$buy_price = $pricelist->GetByName( $product_name );
			print "Buy price: " . $buy_price . "<br/>";
			$sell_price = calculate_price( $buy_price, $supplier_id );

			print "Sell price: " . $sell_price . "<br/>";

			my_log( "supplier_id = " . $supplier_id . " name = " . $product_name );
			$id = Catalog::CreateProduct( $sell_price, $supplier_id, $product_name, $category_name );
			// Create link to supplier price list
			Catalog::AddMapping( $id, $pricelist_id, MultiSite::LocalSiteID() );
			/// my_log ("add mapp done. Site id = " . $pricelist->SiteId() . " " . MultiSite::LocalSiteID());
			if ( $pricelist->SiteId() != MultiSite::LocalSiteID() ) {
				// Map to remote
				my_log( "map to remote" );
				$pricelist_item = PriceList::Get( $pricelist_id );
				// my_log("got");
				// my_log("calling MultiSite " . $pricelist->SiteId(). " " . $id .
//             //   $pricelist_item["supplier_product_code"]);
				$site_id = $pricelist->SiteId();
				print "site_id: " . $site_id . "<br/>";
				if ( is_numeric( $site_id ) and $site_id != MultiSite::LocalSiteID() ) {
					// Copy information from remote site
					$remote_id = $pricelist_item["supplier_product_code"];
					MultiSite::map( $site_id, $id, $remote_id );
					MultiSite::CopyImage( $id, $remote_id, $pricelist->SiteId() );
				}
			}
		}
	}

	static function CreateProduct( $sell_price, $supplier_id, $product_name, $category_name ) {
//    my_log("title= " . $product_name . ", supplier_id=" . $supplier_id . ", sell_price=" . $sell_price, __METHOD__);
		$post_information = array(
			'post_title'  => $product_name,
			// 'post_content' => 'this is new item shop',
			'post_status' => 'publish',
			'post_type'   => "product"
		);
//    my_log("calling wp_insert_post");
		$post_id = wp_insert_post( $post_information, true );
//    my_log("after");
		update_post_meta( $post_id, "_regular_price", $sell_price );
		update_post_meta( $post_id, "_price", $sell_price );
		update_post_meta( $post_id, "supplier_name", get_supplier_name( $supplier_id ) );
		update_post_meta( $post_id, "_visibility", "visible" );
		wp_set_object_terms( $post_id, $category_name, 'product_cat' );
		// wc_set_term_order($category_id, $post_id);

		// print "create product done <br/>";
		return $post_id;
	}

	static function AddMapping( $product_id, $pricelist_id, $site_id ) {
		my_log( "add_mapping" . $product_id, "catalog-map.php" );

		if ( $site_id == MultiSite::LocalSiteID() ) {
			catalog::RemoveOldMap( $product_id, $pricelist_id );

			$pricelist = PriceList::Get( $pricelist_id );
			// var_dump($pricelist);

			$supplier_id = $pricelist["supplier_id"];
			// var_dump($supplier_id);
			$supplier_product_name = $pricelist["product_name"];
			// var_dump($supplier_product_name);

			$sql = "INSERT INTO im_supplier_mapping (product_id, supplier_id, supplier_product_name, supplier_product_code, pricelist_id)"
			       . "VALUES (' "
			       . $product_id . "', "
			       . $supplier_id . ", '" . $supplier_product_name . '\', '
			       . $pricelist["supplier_product_code"] . ", " . $pricelist_id . ')';

			sql_query( $sql );

			if ( $product_id > 0 ) { // not hide
				$pricelist = new PriceList( $supplier_id );
				$buy_price = $pricelist->GetByName( $supplier_product_name );
				update_post_meta( $product_id, "buy_price", $buy_price );
				update_post_meta( $product_id, "supplier_name", get_supplier_name( $supplier_id ) );
				// print "going to publish ";
				Catalog::PublishItem( $product_id );
			}
		} else {
			// Remote product
			print "NOT implemeted<br/>";

		}
		if ( $product_id > 0 ) {
			self::UpdateProduct( $product_id, $line );
		}
	}

	static function RemoveOldMap( $product_id, $pricelist_id ) {
		$pricelist    = PriceList::Get( $pricelist_id );
		$supplier_id  = $pricelist["supplier_id"];
		if ( $product_id > 0 ) {
			$alternatives = alternatives( $product_id );
			foreach ( $alternatives as $p ) {
				if ( $p[1] == $supplier_id ) {
					Catalog::DeleteMapping( $p[2] );
				}
			}
		}
	}

	static function DeleteMapping( $map_id ) {
		my_log( "delete_mapping", "catalog-map.php" );

		$sql = "delete from im_supplier_mapping where id = $map_id";

		my_log( $sql, "catalog-map.php" );

		sql_query( $sql );
	}

	static function PublishItem( $product_id ) {
//        print "start ";
		$my_post                = array();
		$my_post['ID']          = $product_id;
		$my_post['post_status'] = 'publish';

		// Update the post into the database
		my_log( "publish prod id: " . $product_id, "catalog-update-post.php" );
		wp_update_post( $my_post );

//        print "end<br/>";
	}

	static function UpdateProduct( $prod_id, &$line, $details = false ) {
		if ( ! ( $prod_id > 0 ) ) {
			print __METHOD__ . " bad prod_id: " . $prod_id . "<br/>";
			die ( 1 );
		}
		$line = "<tr>";
		my_log( "update $prod_id" );
		global $debug_product;
		global $debug;
		if ( $prod_id == $debug_product ) {
			$print_line = true;
		} else {
			$print_line = $debug;
		}

		// Current infoAd
		$current_supplier_name = get_meta_field( $prod_id, "supplier_name" );
		$current_price         = get_price( $prod_id );
		$line                  .= gui_cell( $prod_id );
		$line                  .= gui_cell( get_product_name( $prod_id ) );
		// print "prod_id: " . $prod_id . "<br/>";

		// Find alternatives
		$alternatives = alternatives( $prod_id, $details );
		if ( $debug_product == $prod_id ) {
			print "count= " . count( $alternatives ) . " ";
			var_dump( $alternatives );
		}

		// If no alternative to a variation, check alternative from parent
		$count = count( $alternatives );
		if ( $count == 0 ) {
			if ( $details )
				print "count == 0. trying parent ";
			$parent = get_product_parent( $prod_id );
			// print "parent: " . $parent ;
			if ( $parent > 0 ) {
				if ( $details ) {
					print "parent " . $parent . " ";
				}
				$alternatives = alternatives( $parent, $details );
				$count        = count( $alternatives);
			}
			// print " " . count($alternatives) . "<br/>";
		}
		$line .= gui_cell( $count );
		// my_log( "count $count" );
		// print $count . "<br/>";

		if ( $count == 0 ) {
			if ( get_post_status( $prod_id ) == 'draft' ) {
				return false;
			}
			$line .= gui_cell( "מוצר לא מקושר" );
			// if ($current_supplier_name == "שדות" || $current_supplier_name == "יבולי בר") {
			$line .= gui_cell( 'מוצר יורד' );
			$line .= "</tr>";
			Catalog::DraftItems( array( $prod_id ) );

			return true;
		}
		$best = best_alternatives( $alternatives, $debug_product == $prod_id );
		if ( $prod_id == $debug_product ) {
			print "best:<br/>";
			var_dump( $best );
		}
		$best_price    = $best[0];
		$best_supplier = $best[1];
		$best_pricelistid = $best[2];
		$sale_price = $best[3];
		if ( $sale_price > 0 and $sale_price < $best_price )
			$new_price = $sale_price;
		else $new_price = $best_price;

		$line             .= gui_cell( get_supplier_name( $best_supplier ) . " " . $best_price );
		$prod_status      = sql_query_single_scalar( "SELECT post_status FROM wp_posts WHERE id=" . $prod_id );
		// print $prod_id . " " . $prod_status . "<br/>";

		$status = "";

		if ( $prod_status == 'draft' ) {
			$status .= "פרסום ";
			Catalog::PublishItem( $prod_id );
			$print_line = true;
		}


		if ( $new_price <> $current_price ) {
			$print_line = true;
			// $status     .= gui_cell( "מחיר חדש " . $calc_price . " מחיר ישן " . $current_price );
			Catalog::SelectOption( $prod_id, $best_pricelistid );
		}

		if ( get_supplier_name( $best_supplier ) <> $current_supplier_name ) {
			$status .= "משנה לספק " . get_supplier_name( $best_supplier );
		}

		// var_dump($alternatives);
		$line .= gui_cell( $status );

		// ???????
		// Catalog::SelectOption( $prod_id, $best_pricelistid );
		// $print_line = true;

		$line .= "</tr>";

		return $print_line;
	}

	static function DraftItems( $ids ) {
		my_log( "start draft", "catalog-update-post.php" );
		for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
			my_log( "id = " . $ids[ $pos ] );
			$product_id = $ids[ $pos ];

			$my_post                = array();
			$my_post['ID']          = $product_id;
			$my_post['post_status'] = 'draft';

			// Update the post into the database
			wp_update_post( $my_post );
		}
	}

	static function SelectOption( $product_id, $pricelist_id ) {
		global $conn;

		// print "select ";
		$pricelist = PriceList::Get( $pricelist_id );
		// var_dump($pricelist);
		// print "pricelist:get " . $pricelist ;
		$supplier = $pricelist["supplier_id"];
		// Remove supplier category
		$current_supplier = sql_query_single_scalar( "SELECT meta_value FROM wp_postmeta WHERE meta_key = 'supplier_name' AND post_id = " . $product_id );

		$supplier_name = get_supplier_name( $supplier );
		update_post_meta( $product_id, "supplier_name", $supplier_name );

		$terms         = get_the_terms( $product_id, 'product_cat' );
		$regular_price = calculate_price( $pricelist["price"], $supplier, $terms );
//		print "regular: " . $regular_price . "<br/>";

		$sale_price = calculate_price( $pricelist["price"], $supplier, $pricelist["sale_price"], $terms );
//		print "sale: " . $sale_price . "<br/>";

		// $product_name = $pricelist["product_name"];
		// print $product_id . " " . $product_name . " ". $price . " " . $supplier . "<br/>";

		print "set regular price " . $regular_price . "<br/>";
		update_post_meta( $product_id, "_regular_price", $regular_price );
		if ( is_numeric( $sale_price ) and $sale_price < $regular_price and $sale_price > 0 ) {
			// print "sale ";
			print "set sale price " . $sale_price . "<br/>";
			update_post_meta( $product_id, "_sale_price", $sale_price );
			update_post_meta( $product_id, "_price", $sale_price );
			update_post_meta( $product_id, "buy_price", $pricelist["sale_price"] );
		} else {
			sql_query( "DELETE FROM wp_postmeta WHERE post_id = " . $product_id . " AND meta_key = '_sale_price'" );
			update_post_meta( $product_id, "buy_price", $pricelist["price"] );
			update_post_meta( $product_id, "_price", $regular_price );

		}

		$sql = "UPDATE im_supplier_mapping SET selected = TRUE WHERE product_id = " . $product_id .
		       " AND supplier = " . $supplier;

		mysqli_query( $conn, $sql );
		// update_post_meta($product_id, "buy_price", $buy_price);

		// Update variations

		$var = get_product_variations( $product_id );

		foreach ( $var as $v ) {
			my_log( "updating variation " . $v . " to pricelist " . $pricelist_id . " price " . $price );
			$var = new WC_Product_Variation( $v );
			update_post_meta( $v, "supplier_name", get_supplier_name( $supplier ) );
			update_post_meta( $v, "_regular_price", $price );
			update_post_meta( $v, "_price", $price );
			update_post_meta( $v, "buy_price", $buy_price );
		}
		mysqli_query( $conn, "UPDATE wp_posts SET post_modified = NOW() WHERE id = " . $product_id );
	}

	static function PublishItems( $ids ) {
		my_log( "start publish. ids count " . count( $ids ), "catalog-update-post.php" );
		for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
			Catalog::PublishItem( $ids[ $pos ] );
		}
	}

	// Get product id linked to that pricelist line.
	// Or null? if not exists.
	// TOdo: rewrite...

	static function GetProdID( $pricelist_id, $include_hide = false ) # Returns prod id if this supplier product mapped
	{
		global $conn;
		$sql = "SELECT product_id, id FROM im_supplier_mapping WHERE pricelist_id = " . $pricelist_id;
		if ( ! $include_hide )
			$sql .= " AND product_id > 0";
		// print $sql;
		$id = sql_query_single( $sql );

		/// print "id=" . $id . "<br/>";
		// my_log(__METHOD__ . " " . $pricelist_id . " " . $id);
		if ( $id ) {
			return $id;
		}

		# If product removed in supplier and previously mapped we update the link
		$result = PriceList::Get( $pricelist_id );
//        // var_dump($result);
		$product_name = $result["product_name"];
		foreach ( array( "חדשה", "מוגבל", "גדול", "טעים", "מבצע", "חדש", "ויפה", "יפה" ) as $word_to_remove ) {
			$product_name = str_replace( $word_to_remove, "", $product_name );
		}
		// print $result["product_name"] . ": " . $product_name . "<br/>";
		$sql = "SELECT product_id, id FROM im_supplier_mapping " .
		       " WHERE supplier_product_name = '" . mysqli_real_escape_string( $conn, $product_name ) . "'" .
		       " AND supplier_id = " . $result["supplier_id"];

		if ( ! $include_hide )
			$sql .= " and product_id > 0";
		// print $sql;
		// Todo: should return more the one
		$id = sql_query_single( $sql );

		return $id;
	}

	static function GetBuyPrice( $product_id, $supplier ) {
		// print "prod_id = " . $product_id . " supplier = " . $supplier . "<br/>";

		$alternatives = alternatives( $product_id );
		for ( $i = 0; $i < count( $alternatives ); $i ++ ) {
			if ( $alternatives[ $i ][1] == $supplier ) {
				return $alternatives[ $i ][0];
			}

		}
		return null;
	}

//    static function Options($prod_id)
//    {
//        $sql = "select pricelist_id from im_supplier_mapping where product_id = " . $prod_id;
//
//        return sql_query_array_scalar($sql);
//    }

	function DeleteMappingByPricelistId( $pricelist_id ) {
		my_log( "delete_mapping", "catalog-map.php" );

		$sql = "delete from im_supplier_mapping where pricelist_id = $pricelist_id";

		my_log( $sql, "catalog-map.php" );

		sql_query( $sql );
	}

	function HideProduct( $pricelist_id )
	{
		global $conn;

		my_log( "null_mapping", "catalog-map.php" );

		// Good if already mapped
		sql_query( "UPDATE im_supplier_mapping SET product_id =-1 WHERE pricelist_id = " . $pricelist_id );

		// Otherwise need to add map to -1
		if ( mysqli_affected_rows( $conn ) < 1 ) {
//			$sql = "INSERT INTO im_supplier_mapping (product_id, pricelist_id) "
//			       . "VALUES (-1, " . $pricelist_id . ")";
//
//			print $sql;
//			sql_query ($sql);
			$this->AddMapping( - 1, $pricelist_id, MultiSite::LocalSiteID() );
		}
	}
}

function best_alternatives( $alternatives, $debug = false ) {
	global $debug_product;
	my_log( "best_alternatives" );
	$min  = 1111111;
	$best = null;
	for ( $i = 0; $i < count( $alternatives ); $i ++ ) {
		if ( $debug ) {
			print "option: " . $i . " " . $alternatives[ $i ][0] . " " . $alternatives[ $i ][1] . " " . $alternatives[ $i ][3] . "<br/>";
		}
		$price = calculate_price( $alternatives[ $i ][0], $alternatives[ $i ][1], $alternatives[ $i ][3] );
		//print "price: " . $price . "<br/>";
		my_log( "price $price" );
		if ( $price < $min ) {
			$best = $alternatives[ $i ];
			my_log( "best $best" );
			$min = $price;
		}
	}

	return $best;
}

//function prof_flag( $str ) {
//	global $prof_timing, $prof_names;
//	$prof_timing[] = microtime( true );
//	$prof_names[]  = $str;
//}
//
//// Call this when you're done and want to see the results
//function prof_print() {
//	global $prof_timing, $prof_names;
//	$size = count( $prof_timing );
//	for ( $i = 0; $i < $size - 1; $i ++ ) {
//		echo "<b>{$prof_names[$i]}</b><br>";
//		echo sprintf( "&nbsp;&nbsp;&nbsp;%f<br>", $prof_timing[ $i + 1 ] - $prof_timing[ $i ] );
//	}
//	echo "<b>{$prof_names[$size-1]}</b><br>";
//}

function alternatives( $id, $details = false )
{
	global $conn;
	global $debug_product;

	// prof_flag("start " . $id);
	if ( ! ( $id > 0 ) ) {
		print __METHOD__ . "! (id>0) " . $id . "</br>";
		die ( 1 );
	}

	if ( $id == $debug_product ) {
		print "Alternatives<br/>";
	}
	// Search by pricelist_id
	$sql = "select price, supplier_id, id, sale_price from 
			im_supplier_price_list where id in (select pricelist_id from im_supplier_mapping where product_id = $id)";

//    print "<br/>" . $sql . "<br/>";
	$result = mysqli_query( $conn, $sql );
	$output = "";
	if ( ! $result ) {
		sql_error( $sql );

		return null;
	} else {
		$rows = array();
		while ( $row = mysqli_fetch_row( $result ) ) {
			if ( $details ) {
				$output .= get_supplier_name( $row[1] ) . " " . $row[0] . ", ";
			}
			// Todo: handle priority
			// array_push($row, get_supplier_priority($row[1]));
			array_push( $rows, $row );
		}
	}

	// prof_flag("mid " . $id);
	$output .= "by name: ";
	// Search by product_name and supplier_id
	$sql = " SELECT pl.price, pl.supplier_id, pl.id, pl.sale_price
  		FROM im_supplier_price_list pl
    	JOIN im_supplier_mapping m
  		WHERE m.supplier_product_name = pl.product_name
  		AND m.supplier_id = pl.supplier_id
		AND m.product_id = " . $id;

	$result = mysqli_query( $conn, $sql );
	$c      = 0;
	while ( $row = mysqli_fetch_row( $result ) ) {
		if ( $id == $debug_product ) {
			print $c ++ . ")";
			var_dump( $row );
			print "<br/>";
		}
		$found = false;
		// Don't repeat alternatives
		for ( $i = 0; $i < count( $rows ); $i ++ ) {
			if ( $rows[ $i ][2] == $row[2] ) {
				$found = true;
			}
		}
		if ( ! $found ) {
			if ( $details ) {
				$output .= get_supplier_name( $row[1] ) . " " . $row[0] . ", ";
			}
			array_push( $rows, $row );
		}
	}

	if ( $details ) {
		print rtrim( $output, ", ");
	}

//	prof_flag("end " . $id);

	return $rows;
}
