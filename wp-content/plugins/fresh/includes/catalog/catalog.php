<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/12/15
 * Time: 10:07
 */
// require_once( '../im-tools.php' );
if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) );
}
require_once( FRESH_INCLUDES . '/pricelist/pricelist.php' );
require_once( FRESH_INCLUDES . '/wp/terms.php' );
require_once( FRESH_INCLUDES . '/catalog/pricing.php' );
require_once( 'bundles.php' );

class Catalog {
	static function CreateProducts( $category_name, $ids ) {
		my_log( "Create_products. Category = " . $category_name );

		for ( $pos = 0; $pos < count( $ids ); $pos += 2 ) {
			// $product_name          = urldecode( $ids[ $pos ] );
			$supplier_id  = $ids[ $pos ];
			$pricelist_id = $ids[ $pos + 1 ];
			// $supplier_product_code = $ids[ $pos + 3 ];
			// print $product_name . ", " . $supplier_id . ", " . $pricelist_id . ", " . $supplier_product_code . "<br/>";

			$pricelist = new PriceList( $supplier_id );
			$id = Catalog::CreateProduct( $pricelist_id, $category_name );
			// Create link to supplier price list
			Catalog::AddMapping( $id, $pricelist_id, Core_Db_MultiSite::LocalSiteID() );
			/// my_log ("add mapp done. Site id = " . $pricelist->SiteId() . " " . MultiSite::LocalSiteID());
			if ( $pricelist->SiteId() != Core_Db_MultiSite::LocalSiteID() ) {
				// Map to remote
				my_log( "map to remote" );
				$pricelist_item = PriceList::Get( $pricelist_id );
				// my_log("got");
				// my_log("calling MultiSite " . $pricelist->SiteId(). " " . $id .
//             //   $pricelist_item["supplier_product_code"]);
				$site_id = $pricelist->SiteId();
//				print "site_id: " . $site_id . "<br/>";
				if ( is_numeric( $site_id ) and $site_id != Core_Db_MultiSite::LocalSiteID() ) {
					// Copy information from remote site
					$remote_id = $pricelist_item["supplier_product_code"];
					Core_Db_MultiSite::map( $site_id, $id, $remote_id );
					// MultiSite::CopyImage( $id, $remote_id, $pricelist->SiteId() );
				}
			}
		}
		print "done";
	}

	static function CreateProduct( $pricelist_id, $category_name ) // Create product from pricelist information
	{
		$item = new PricelistItem( $pricelist_id );

		Pricelist::Get( $pricelist_id );

		if ( $item->getCategory() ) { // Comma seperated list
			$categ = explode( ",", $item->getCategory() );
		} else {
			$categ = $category_name;
		}
		$post_id = Catalog::DoCreateProduct( $item->getProductName(), $item->getSellPrice(), $item->getSupplierName(),
			$categ, $image = $item->getPicturePath() );

		// update_post_meta( $post_id, 'fifu_image_url', $item->getPicturePath() );

		return $post_id;
	}

	static function DoCreateProduct( $product_name, $sell_price, $supplier_name, $categ = null, $image_path = null, $sale_price = 0 ) {
		$post_information = array(
			'post_title'  => $product_name,
			// 'post_content' => 'this is new item shop',
			'post_status' => 'publish',
			'post_type'   => "product"
		);
		$post_id          = wp_insert_post( $post_information, true );

		update_post_meta( $post_id, "_regular_price", $sell_price );
		update_post_meta( $post_id, "_price", $sell_price );
		update_post_meta( $post_id, "supplier_name", $supplier_name );
		update_post_meta( $post_id, "_visibility", "visible" );
		if ( $sale_price ) {
//			print "sale: $sale_price<br/>";
			update_post_meta( $post_id, "_sale_price", $sale_price );
			update_post_meta( $post_id, "_price", $sale_price );
		}
		if ( $categ ) {
			wp_set_object_terms( $post_id, $categ, 'product_cat' );
		}// Get The image
		;
		if ( strlen( $image_path ) > 5 ) {
			// print "image: $image<br/>";

			upload_image( $post_id, $image_path);
		}

		return $post_id;
	}

	static function AddMapping( $product_id, $pricelist_id, $site_id ) {
		my_log( "add_mapping" . $product_id, "catalog-map.php" );

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
				if ( $p->getSupplierId() == $supplier_id ) {
					$sql    = "SELECT id FROM im_supplier_mapping WHERE supplier_id = " . $supplier_id .
					          " AND product_id = " . $product_id;
					$result = sql_query( $sql );
					if ( $result )
						while ( $row = mysqli_fetch_row( $result ) ) {
							Catalog::DeleteMapping( $row[0] );
						}
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

	static function PricelistFromProduct($prod_id, $supplier_id)
	{
		$sql = "select pricelist_id from im_supplier_mapping " .
		       " where product_id = " . $prod_id .
		       " and supplier_id = " . $supplier_id;
		// print $sql . "<br/>";
		$pricelist_id = sql_query_single_scalar($sql);
		return $pricelist_id;
	}

	static function UpdateProduct( $prod_id, &$line, $details = false ) {
		$debug_product = 0;

		$P = new Fresh_Product( $prod_id );
		if ( ! ( $prod_id > 0 ) ) {
			print __METHOD__ . " bad prod_id: " . $prod_id . "<br/>";
			die ( 1 );
		}
		$line = "<tr>";
		my_log( "update $prod_id" );
		global $debug;
		if ( $prod_id == $debug_product ) {
			$print_line = true;
		} else {
			$print_line = $debug;
		}

		// Current infoAd
		$current_supplier_name = get_meta_field( $prod_id, "supplier_name" );
		$current_price         = get_price( $prod_id );
		if ( $prod_id == $debug_product ) {
			print "CP = " . $current_price . "<br/>";
		}
		$line .= gui_cell( $prod_id );
		$line .= gui_cell( get_product_name( $prod_id ) );
		// print "prod_id: " . $prod_id . "<br/>";

		// Find alternatives
		$alternatives = alternatives( $prod_id, $details );
		if ( $debug_product == $prod_id ) {
			print "count= " . count( $alternatives ) . "<br/>";
			// var_dump( $alternatives );
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
			// Product is in stock. Do nothing.
			if ( ( $P->getStock() > 0 ) ) {
				return true;
			}

			if ( get_post_status( $prod_id ) == 'draft' ) {
				return false;
			}
			$line .= gui_cell( "מוצר לא מקושר" );
			$line .= gui_cell( 'מוצר יורד' );
			$line .= "</tr>";

			// Draft the product.
			Catalog::DraftItems( array( $prod_id ) );

			// Draft Bundles.
			print "checking bundles<br>";
			$sql     = "SELECT bundle_prod_id FROM im_bundles WHERE prod_id = " . $prod_id;
			$bundles = sql_query_array_scalar( $sql );
			if ( $bundles )
				Catalog::DraftItems( $bundles );

			return true;
		}
		$best = best_alternatives( $alternatives, $debug_product == $prod_id );
		if ( $prod_id == $debug_product ) {
			print "best:<br/>";
			var_dump( $best );
		}
		$best_price       = $best->getSellPrice();
		$best_supplier    = $best->getSupplierId();
		$best_pricelistid = $best->getId();
		$sale_price       = $best->getSalePrice();

		if ( $sale_price > 0 and $sale_price < $best_price )
			$new_price = $sale_price;
		else $new_price = $best_price;

		$line        .= gui_cell( get_supplier_name( $best_supplier ) . " " . $best_price );
		$prod_status = sql_query_single_scalar( "SELECT post_status FROM wp_posts WHERE id=" . $prod_id );
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

			$p = new Fresh_Product( $product_id );
			if (!$p->Draft()) { print "fail drafting $product_id"; return false; }
		}
		return true;
	}

	static function SelectOption( $product_id, $pricelist_id ) {
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

		// print "set regular price " . $regular_price . "<br/>";
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
		       " AND supplier_id = " . $supplier;

		sql_query( $sql );
		// update_post_meta($product_id, "buy_price", $buy_price);

		// Update variations

		$var = get_product_variations( $product_id );

		foreach ( $var as $v ) {
			my_log( "updating variation " . $v . " to pricelist " . $pricelist_id );
			$var = new WC_Product_Variation( $v );
			update_post_meta( $v, "supplier_name", get_supplier_name( $supplier ) );
			update_post_meta( $v, "_regular_price", $regular_price );
			update_post_meta( $v, "_price", $regular_price );
			update_post_meta( $v, "buy_price", $pricelist["price"] );
		}
		sql_query( "UPDATE wp_posts SET post_modified = NOW() WHERE id = " . $product_id );

		// Update bundle
		$sql     = "SELECT id FROM im_bundles WHERE prod_id = " . $product_id;
		$bundles = sql_query_single( $sql );
		if ( $bundles ) {
			foreach ( $bundles as $bundle_id ) {
				my_log( "updating bundle " . $bundle_id );
				// TODO: update bundle
				$b = Bundle::CreateFromDb( $bundle_id );
				$b->Update();
			}
		}
	}

	static function GetProdImage( $prod_id ) {
		$tid = get_post_thumbnail_id( $prod_id );
		print "tid=" . $tid . " ";

		$img_a = wp_get_attachment_image_src( $tid );
		$img   = $img_a[0];

		return $img;
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
		$debug = false;
		if ( $pricelist_id == 22737 )
			$debug = true;
		$result_ids = array();
		// find products mapped by id.
		$sql = "SELECT product_id, id, supplier_product_name FROM im_supplier_mapping WHERE pricelist_id = " . $pricelist_id;
		if ( ! $include_hide )
			$sql .= " AND product_id > 0";
		// print $sql;
		$result = sql_query( $sql );
		if ( $result ) {
			while ( $row = mysqli_fetch_row( $result ) ) {
				array_push( $result_ids, $row[0] );
			}
			if ( $debug ) {
				my_log( "direct link to $pricelist_id " . implode( ",", $result_ids ) );
				my_log( $sql );
			}
		}

		// find products mapped by name
		$result       = PriceList::Get( $pricelist_id );
		$product_name = $result["product_name"];
		if (strlen($product_name)) {
			foreach ( array( "חדשה", "מוגבל", "גדול", "טעים", "מבצע", "חדש", "ויפה", "יפה" ) as $word_to_remove ) {
				$product_name = str_replace( $word_to_remove, "", $product_name );
			}
			$sql = "SELECT product_id, id FROM im_supplier_mapping " .
			       " WHERE supplier_product_name = '" . escape_string( $product_name ) . "'" .
			       " AND supplier_id = " . $result["supplier_id"];

			if ( ! $include_hide )
				$sql .= " and product_id > 0";
			// print $sql;
			$result = sql_query( $sql );
			if ( $result ) {
				{
					while ( $row = mysqli_fetch_row( $result ) ) {
						if ( ! in_array( $row[0], $result_ids ) ) {
							array_push( $result_ids, $row[0] );
						}
						if ( $debug ) {
							my_log( "name link to $pricelist_id " . $row[0] );
						}
					}
				}
			}
		}

		return $result_ids;
	}

	static function GetBuyPrice( $product_id, $supplier ) {
		// print "prod_id = " . $product_id . " supplier = " . $supplier . "<br/>";

		$alternatives = alternatives( $product_id );
		for ( $i = 0; $i < count( $alternatives ); $i ++ ) {
			if ( $alternatives[ $i ]->getSupplierId() == $supplier ) {
				return $alternatives[ $i ]->getPrice();
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

	static function GetProdOptions( $product_name ) {
		$array = array();

		$product_name_prf = name_prefix( $product_name );
//        my_log($product_name_prf);

		if ( $pos = strpos( $product_name, " " ) ) {
			$product_name_prf = substr( $product_name, 0, $pos );
		}
		$sql1 = 'SELECT DISTINCT id, post_title FROM `wp_posts` WHERE '
		        . ' post_type IN (\'product\', \'product_variation\')'
		        . ' AND (post_status = \'publish\' OR post_status = \'draft\')'
		        . ' AND (post_title LIKE \'%' . addslashes( $product_name_prf ) . '%\' '
		        . ' OR id IN '
		        . '(SELECT object_id FROM wp_term_relationships WHERE term_taxonomy_id IN '
		        . '(SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id IN '
		        . "(SELECT term_id FROM wp_terms WHERE name LIKE '%" . addslashes( $product_name_prf ) . "%'))))"
		        . " ORDER BY 2";

//	print $sql1 . "<br/>";
//	die(1);

		$result1 = sql_query( $sql1 );

		while ( $row1 = mysqli_fetch_assoc( $result1 ) ) {
			array_push( $array, $row1 );
		}

		return $array;
	}

	function DeleteMappingByPricelistId( $pricelist_id ) {
		my_log( "delete_mapping", "catalog-map.php" );

		$sql = "delete from im_supplier_mapping where pricelist_id = $pricelist_id";

		my_log( $sql, "catalog-map.php" );

		sql_query( $sql );
	}

	function HideProduct( $pricelist_id )
	{
		my_log( "null_mapping", "catalog-map.php" );

		// Good if already mapped
		sql_query( "UPDATE im_supplier_mapping SET product_id =-1 WHERE pricelist_id = " . $pricelist_id );

		// Otherwise need to add map to -1
		if ( sql_affected_rows( ) < 1 ) {
			$this->AddMapping( - 1, $pricelist_id, Core_Db_MultiSite::LocalSiteID() );
		}
	}

	static function auto_mail() {
		global $business_name;
		global $support_email;

		$sql = "SELECT user_id FROM wp_usermeta WHERE meta_key = 'auto_mail'";

		$auto_list = sql_query_array_scalar( $sql );

		print "Auto mail...<br/>";
		print "Today " . date( "w" ) . "<br/>";

		foreach ( $auto_list as $client_id ) {
			print get_customer_name( $client_id ) . "<br/>";
			$last = get_user_meta( $client_id, "last_email", true );
			if ( $client_id != 1 and ($last == date( 'Y-m-d' ) )) {
				print "already sent";
				continue;
			}
			$setting = get_user_meta( $client_id, 'auto_mail', true );
			$day     = strtok( $setting, ":" );
			$categ   = strtok( ":" );
			print "day: " . $day . "<br/>";
			print "categ: " . $categ . "<br/>";
			$customer_type = customer_type( $client_id );

			if ( $day == date( 'w' ) or $client_id == 1) {
				print "שולח...<br/>";
				$subject = "מוצרי השבוע ב-" . $business_name;
				$mail    = "שלום " . get_customer_name( $client_id ) .
				           " להלן רשימת מוצרי פרוטי ";
				do {
					if ( $categ == 0 ) {
						$mail = show_category_all( false, true, false, false, $customer_type );
						break;
					}
					if ( $categ == "f" ) {
						$mail = show_category_all( false, true, true, false, $customer_type );
						break;
					}
					foreach ( explode( ",", $categ ) as $categ ) {
						$mail .= show_category_by_id( $categ, false, true, $customer_type );
					}
				} while ( 0 );
				$user_info = get_userdata( $client_id );
				$to        = $user_info->user_email . ", " . $support_email;

				$rc = send_mail( $subject, $to, $mail );
				print "subject: " . $subject . "<br/>";
				print "mail: " . $mail . "<br/>";
				print "to: " . $to . "<br/>";
				print "rc: " . $rc . "<br/>";

				update_user_meta( $client_id, "last_email", date( 'Y-m-d' ) );
			}
		}
	}
	static function HandleMinusQuantity()
	{
		$categs = self::GetFreshCategories();

		foreach ($categs as $categ){
//			print gui_header(1, get_term_name($categ)) . "<br/>";
			$iter = new ProductIterator();
			$iter->iteratePublished($categ );

			while ( $prod_id = $iter->next() ) {
				$p = new Fresh_Product( $prod_id );
				if ($p->getStock() < 0.5){
					$p->setStock(0);
				}
			}
		}
	}

	static function GetFreshCategories()
	{
		return explode(",", info_get( "fresh"));
	}
}

function best_alternatives( $alternatives, $debug = false ) {
	my_log( "best_alternatives" );
	$min  = 1111111;
	$best = null;
	for ( $i = 0; $i < count( $alternatives ); $i ++ ) {
		//$price, $supplier, $sale_price = '', $terms = null
		$price = calculate_price( $alternatives[ $i ]->getPrice(), $alternatives[ $i ]->getSupplierId(),
			$alternatives[ $i ]->getSalePrice());
//		print "price: " . $alternatives[ $i ]->getSupplierId() . " " . $price . "<br/>";
		my_log( "price $price" );
		if ( $price < $min ) {
			$best = $alternatives[ $i ];
			// my_log( "best $best[0]" );
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
require_once( ABSPATH . '/wp-admin/includes/image.php' );

function upload_image( $post_id, $image ) {
	print "post id: " . $post_id . "<br/>";
	print "image: " . $image . "<br/>";
	$get = wp_remote_get( $image );

	$type = wp_remote_retrieve_header( $get, 'content-type' );

	print "type: $type<br/>";
	if ( $type ) {
		$mirror = wp_upload_bits( basename( $image ), '', wp_remote_retrieve_body( $get ) );
		print "mirror: <br/>";
		var_dump( $mirror );
		print "<br/>";

		$attachment = array(
			'post_title'     => basename( $image ),
			'post_mime_type' => $type
		);

		$attachment_id = wp_insert_attachment( $attachment, $mirror['file'], $post_id );

		print "attach_id: " . $attachment_id . "<br/>";

		wp_generate_attachment_metadata( $attachment_id, $mirror['file'] );

		set_post_meta_field( $post_id, '_thumbnail_id', $attachment_id );
//		var_dump ($meta); print "<br/>";
//		$r = wp_update_attachment_metadata($attachment_id, $meta);
//		var_dump($r); print "<br/>";
//		$r = add_post_meta($attachment_id, '_wp_attachment_context', $image);
//		var_dump($r); print "<br/>";

//		$attach_data = wp_generate_attachment_metadata( $attach_id, $mirror['file'] );
//
//		var_dump($attach_data);
//
//		print "<br/>";
//
//		print wp_update_attachment_metadata( $post_id, $attach_data );
	}
}

function supplier_prod_id( $prod_id, $supplier_id ) {
	$a = alternatives( $prod_id, false );

//	print "<br/>";
//	print "supplier: " . $supplier_id . "<br/>";
	foreach ( $a as $s ) {
//		print "option: " . $s->getSupplierID() . "<br/>";
		if ( $s->getSupplierId() == $supplier_id ) {
			// print "found<br/>";
			return $s->getSupplierProductCode();
		}
	}

	return 0;
}

function alternatives( $prod_id, $details = false, $supplier_id = null )
{
	global $debug_product;
	$debug_product = 0;

	// prof_flag("start " . $id);
	if ( ! ( $prod_id > 0 ) ) {
		print __METHOD__ . "! (id>0) " . $prod_id . "</br>";
	}

	if ( $prod_id == $debug_product ) {
		print "Alternatives<br/>";
	}
	// Search by pricelist_id
	$sql = "select price, supplier_id, id, sale_price from 
			im_supplier_price_list where id in (select pricelist_id from im_supplier_mapping where product_id = $prod_id)";

//    print "<br/>" . $sql . "<br/>";
	$result = sql_query( $sql );
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
			// array_push( $rows, $row );
			$n = new PricelistItem( $row[2] );
			array_push( $rows, $n);
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
		AND m.product_id = " . $prod_id;

	$result = sql_query( $sql );
	$c      = 0;
	while ( $row = mysqli_fetch_row( $result ) ) {
		$supplier_id = $row[1];
		if ( $prod_id == $debug_product ) {
			print $c ++ . ")";
			var_dump( $row );
			print "<br/>";
		}
		$found = false;
		// Don't repeat alternatives
		for ( $i = 0; $i < count( $rows ); $i ++ ) {
			if ( $rows[ $i ]->getSupplierId() == $supplier_id ) {
				$found = true;
			}
		}
		if ( ! $found ) {
			if ( $details ) {
				$output .= get_supplier_name( $row[1] ) . " " . $row[0] . ", ";
			}
			// array_push( $rows, $row );
			array_push( $rows, new PricelistItem( $row[2]));
		}
	}

	if ( $details ) {
		print rtrim( $output, ", ");
	}
	return $rows;
}

function name_prefix( $name ) {
	return strtok( $name, "-()" );
}
