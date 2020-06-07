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

class Fresh_Catalog {
	static function init_hooks()
	{
		AddAction("create_products", __CLASS__. '::CreateProduct_wrap');
		AddAction("product_change_regularprice", __CLASS__."::ChangeProductRegularPrice_wrap");
		AddAction("product_change_saleprice", __CLASS__."::ChangeProductSalePrice_wrap");
		AddAction("product_publish", array(__CLASS__, "product_publish"));
	}

	static function product_publish()
	{
//		let url = post_file + '?operation=&product_id='+product_id+'&status='+publish;
		$product_id = GetParam("product_id", true);
		$publish = GetParam("status", true);
		$p = new Fresh_Product($product_id);
		if ($publish)
			$p->PublishItem();
		else
			$p->Draft();
		return true;
	}
	static function CreateProduct_wrap()
	{
		$category_id = GetParam("category_name", true);
		$create_info = explode(",", GetParam("create_info", true));
		$supplier_id = $create_info[0];
		$pl_id = $create_info[1];
		return self::CreateProducts($category_id, array($supplier_id, $pl_id));
	}

	static function ChangeProductRegularPrice_wrap()
	{
		$prod_id = GetParam("prod_id", true);
		$price = GetParam("price", true);
		$P = new Fresh_Product($prod_id);
		if (! $P) return false;
		$P->setRegularPrice($price);
		return true;
	}

	static function ChangeProductSalePrice_wrap()
	{
		$prod_id = GetParam("prod_id", true);
		$price = GetParam("price", true);
		$P = new Fresh_Product($prod_id);
		if (! $P) return false;
		$P->setSalePrice($price);
		return true;
	}


	static function CreateProducts( $category_id, $ids ) {
		MyLog( "Create_products. Category = " . $category_id );

		for ( $pos = 0; $pos < count( $ids ); $pos += 2 ) {
			// $product_name          = urldecode( $ids[ $pos ] );
			$supplier_id  = $ids[ $pos ];
			$pricelist_id = $ids[ $pos + 1 ];
			// $supplier_product_code = $ids[ $pos + 3 ];
//			 print $supplier_id . ", " . $pricelist_id . "<br/>";

			$pricelist = new Fresh_PriceList( $supplier_id );
			$id = Fresh_Catalog::CreateProduct( $pricelist_id, $category_id );
			// Create link to supplier price list
			Fresh_Catalog::AddMapping( $id, $pricelist_id, Core_Db_MultiSite::LocalSiteID() );
			/// my_log ("add mapp done. Site id = " . $pricelist->SiteId() . " " . MultiSite::LocalSiteID());
			if ( $pricelist->SiteId() != Core_Db_MultiSite::LocalSiteID() ) {
				// Map to remote
				MyLog( "map to remote" );
				$pricelist_item = Fresh_PriceList::Get( $pricelist_id );
				$site_id = $pricelist->SiteId();
				if ( is_numeric( $site_id ) and $site_id != Core_Db_MultiSite::LocalSiteID() ) {
					// Copy information from remote site
					$remote_id = $pricelist_item["supplier_product_code"];
					Core_Db_MultiSite::map( $site_id, $id, $remote_id );
					// MultiSite::CopyImage( $id, $remote_id, $pricelist->SiteId() );
				}
			}
		}
		return true;
	}

	static function CreateProduct( $pricelist_id, $category_id ) // Create product from pricelist information
	{
		$item = new Fresh_Pricelist_Item( $pricelist_id );

		Fresh_PriceList::Get( $pricelist_id );

////		if ( $item->getCategory() ) { // Comma seperated list
////			$categ = explode( ",", $item->getCategory() );
////		} else {
//			$categ = $category_id;
//		}
		return self::DoCreateProduct( $item->getProductName(), $item->getSellPrice(), $item->getSupplierName(),
			$category_id, $image = $item->getPicturePath() );

		// update_post_meta( $post_id, 'fifu_image_url', $item->getPicturePath() );

	}

	// "/wp-content/plugins/fresh/post.php?operation=basket_create&basket_name=%D7%9E%D7%95%D7%A1%D7%99%D7%A3&basket_price=99&categ=19"
	// מוסיף, 99, ירקות אורגניים - 19
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
			wp_set_object_terms( $post_id, (int)$categ, 'product_cat' );
		}// Get The image
		;
		if ( strlen( $image_path ) > 5 ) {
			// print "image: $image<br/>";

			self::upload_image( $post_id, $image_path);
		}

		return $post_id;
	}

	static function AddMapping( $product_id, $pricelist_id)
	{
		MyLog( "add_mapping" . $product_id, "catalog-map.php" );

//		self::RemoveOldMap( $product_id, $pricelist_id );

		$pricelist = Fresh_PriceList::Get( $pricelist_id );

		$supplier_id = $pricelist["supplier_id"];
		$Supplier = new Fresh_Supplier($supplier_id);
		$supplier_product_name = Fresh_Pricelist::StripProductName($pricelist["product_name"]);

		$sql = "INSERT INTO im_supplier_mapping (product_id, supplier_id, supplier_product_name, supplier_product_code, pricelist_id)"
		       . "VALUES (' "
		       . $product_id . "', "
		       . $supplier_id . ", '" . $supplier_product_name . '\', '
		       . $pricelist["supplier_product_code"] . ", " . $pricelist_id . ')';

		SqlQuery( $sql );

		if ( $product_id > 0 ) { // not hide
			$pricelist = new Fresh_PriceList( $supplier_id );
			$buy_price = $pricelist->GetByName( $supplier_product_name );
			update_post_meta( $product_id, "buy_price", $buy_price );
			update_post_meta( $product_id, "supplier_name", $Supplier->getSupplierName() );
			// print "going to publish ";
			$p = new Fresh_Product($product_id);
			$p->PublishItem();
		}
		if ( $product_id > 0 ) {
			self::UpdateProduct( $product_id, $line );
		}
	}

	static function RemoveOldMap( $product_id, $pricelist_id ) {
		$pricelist    = Fresh_PriceList::Get( $pricelist_id );
		$supplier_id  = $pricelist["supplier_id"];
		if ( $product_id > 0 ) {
			$alternatives = self::alternatives( $product_id );
			foreach ( $alternatives as $p ) {
				if ( $p->getSupplierId() == $supplier_id ) {
					$sql    = "SELECT id FROM im_supplier_mapping WHERE supplier_id = " . $supplier_id .
					          " AND product_id = " . $product_id;
					$result = SqlQuery( $sql );
					if ( $result )
						while ( $row = mysqli_fetch_row( $result ) ) {
							self::DeleteMapping( $row[0] );
						}
				}
			}
		}
	}

	static function DeleteMapping( $map_id ) {
		MyLog( "delete_mapping", "catalog-map.php" );

		$sql = "delete from im_supplier_mapping where id = $map_id";

		MyLog( $sql, "catalog-map.php" );

		SqlQuery( $sql );
	}

	static function PricelistFromProduct($prod_id, $supplier_id)
	{
		$sql = "select pricelist_id from im_supplier_mapping " .
		       " where product_id = " . $prod_id .
		       " and supplier_id = " . $supplier_id;
		// print $sql . "<br/>";
		$pricelist_id = SqlQuerySingleScalar($sql);
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
		MyLog( "update $prod_id" );
		global $debug;
		if ( $prod_id == $debug_product ) {
			$print_line = true;
		} else {
			$print_line = $debug;
		}

		// Current infoAd
		$current_supplier_name = GetMetaField( $prod_id, "supplier_name" );
		$current_price         = Fresh_Pricing::get_price( $prod_id );
		if ( $prod_id == $debug_product ) {
			print "CP = " . $current_price . "<br/>";
		}
		$line .= Core_Html::gui_cell( $prod_id );
		$line .= Core_Html::gui_cell( $P->getName() );
		// print "prod_id: " . $prod_id . "<br/>";

		// Find alternatives
		$alternatives = self::alternatives( $prod_id, $details );
		if ( $debug_product == $prod_id ) {
			print "count= " . count( $alternatives ) . "<br/>";
		}

		// If no alternative to a variation, check alternative from parent
		$count = count( $alternatives );
		if ( $count == 0 ) {
			if ( $details )
				print "count == 0. trying parent ";
			$parent = GetProductParernt( $prod_id );
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
		$line .= Core_Html::gui_cell( $count );
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
			$line .= Core_Html::gui_cell( "מוצר לא מקושר" );
			$line .= Core_Html::gui_cell( 'מוצר יורד' );
			$line .= "</tr>";

			// Draft the product.
			self::DraftItems( array( $prod_id ) );

			// Draft Bundles.
			$sql     = "SELECT bundle_prod_id FROM im_bundles WHERE prod_id = " . $prod_id;
			$bundles = SqlQueryArrayScalar( $sql );
			if ( $bundles )
				self::DraftItems( $bundles );

			return true;
		}
		$best = Fresh_Catalog::best_alternative( $prod_id); // $alternatives, $debug_product == $prod_id );
		$best_price       = $best->getSellPrice();
		$best_supplier    = $best->getSupplierId();
		$best_pricelistid = $best->getId();
		$sale_price       = $best->getSalePrice();

		if ( $sale_price > 0 and $sale_price < $best_price )
			$new_price = $sale_price;
		else $new_price = $best_price;

		$line        .= Core_Html::gui_cell( $best->getSupplierName() . " " . $best_price );
		$prod_status = SqlQuerySingleScalar( "SELECT post_status FROM wp_posts WHERE id=" . $prod_id );
		// print $prod_id . " " . $prod_status . "<br/>";

		$status = "";

		if ( $prod_status == 'draft' ) {
			$status .= "פרסום ";
			Fresh_Catalog::PublishItem( $prod_id );
			$print_line = true;
		}

		if ( $new_price <> $current_price ) {
			$print_line = true;
			// $status     .= gui_cell( "מחיר חדש " . $calc_price . " מחיר ישן " . $current_price );
			Fresh_Catalog::SelectOption( $prod_id, $best_pricelistid );
		}

		if ( $best->getSupplierName() <> $current_supplier_name ) {
			$status .= "משנה לספק " . get_supplier_name( $best_supplier );
		}

		$line .= Core_Html::gui_cell( $status );

		// ???????
		// Catalog::SelectOption( $prod_id, $best_pricelistid );
		// $print_line = true;

		$line .= "</tr>";

		return $print_line;
	}

	static function DraftItems( $ids ) {
		MyLog( "start draft", "catalog-update-post.php" );
		for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
			MyLog( "id = " . $ids[ $pos ] );
			$product_id = $ids[ $pos ];

			$p = new Fresh_Product( $product_id );
			if (!$p->Draft()) { print "fail drafting $product_id"; return false; }
		}
		return true;
	}

	static function SelectOption( $product_id, $pricelist_id ) {
		$pricelist = Fresh_PriceList::Get( $pricelist_id );
		$Supplier = new Fresh_Supplier($pricelist["supplier_id"]);
		$Product = new Fresh_Product($product_id);

		// Remove supplier category
		$current_supplier = SqlQuerySingleScalar( "SELECT meta_value FROM wp_postmeta WHERE meta_key = 'supplier_name' AND post_id = " . $product_id );


		$supplier_name = $Supplier->getSupplierName();
		update_post_meta( $product_id, "supplier_name", $supplier_name );

		$terms         = get_the_terms( $product_id, 'product_cat' );
		$regular_price = Fresh_Pricing::calculate_price( $pricelist["price"], $Supplier->getId(), $terms );
//		print "regular: " . $regular_price . "<br/>";

		$sale_price = Fresh_Pricing::calculate_price( $pricelist["price"], $Supplier->getId(), $pricelist["sale_price"], $terms );
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
			SqlQuery( "DELETE FROM wp_postmeta WHERE post_id = " . $product_id . " AND meta_key = '_sale_price'" );
			update_post_meta( $product_id, "buy_price", $pricelist["price"] );
			update_post_meta( $product_id, "_price", $regular_price );

		}

		$sql = "UPDATE im_supplier_mapping SET selected = TRUE WHERE product_id = " . $product_id .
		       " AND supplier_id = " . $Supplier->getId();

		SqlQuery( $sql );
		// update_post_meta($product_id, "buy_price", $buy_price);

		// Update variations

		$var = $Product->get_variations( );

		foreach ( $var as $v ) {
			MyLog( "updating variation " . $v . " to pricelist " . $pricelist_id );
			$var = new WC_Product_Variation( $v );
			update_post_meta( $v, "supplier_name", $Supplier->getSupplierName());
			update_post_meta( $v, "_regular_price", $regular_price );
			update_post_meta( $v, "_price", $regular_price );
			update_post_meta( $v, "buy_price", $pricelist["price"] );
		}
		SqlQuery( "UPDATE wp_posts SET post_modified = NOW() WHERE id = " . $product_id );

		// Update bundle
		$sql     = "SELECT id FROM im_bundles WHERE prod_id = " . $product_id;
		$bundles = SqlQuerySingle( $sql );
		if ( $bundles ) {
			foreach ( $bundles as $bundle_id ) {
				MyLog( "updating bundle " . $bundle_id );
				// TODO: update bundle
				$b = Fresh_Bundle::CreateFromDb( $bundle_id );
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
		MyLog( "start publish. ids count " . count( $ids ), "catalog-update-post.php" );
		for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
			Catalog::PublishItem( $ids[ $pos ] );
		}
	}

	// Get product id linked to that pricelist line.
	// Or null? if not exists.
	// TOdo: rewrite...

	static function GetProdID( $pricelist_id, $include_hide = false ) # Returns prod id if this supplier product mapped
	{
		$debug = false; // ($pricelist_id == 750390);
		if ( $debug ) {
			MyLog("debugging pricelist id $pricelist_id");
			$debug = true;
		}

		$result_ids = array();

		// find products mapped by id.
		$sql = "SELECT product_id, id, supplier_product_name FROM im_supplier_mapping WHERE pricelist_id = " . $pricelist_id;
		if ( ! $include_hide ) $sql .= " AND product_id > 0";

		$result = SqlQuery( $sql );
		if ( $result ) {
			while ( $row = mysqli_fetch_row( $result ) ) {
				array_push( $result_ids, $row[0] );
				if ( $debug ) {
					MyLog( $sql );
					MyLog( $row[0] . " direct link to $pricelist_id " . implode( ",", $result_ids ) );
				}
			}
		}

		// find products mapped by name
		$result       = Fresh_PriceList::Get( $pricelist_id );
		$product_name = $result["product_name"];
		if ($debug)
			print "handling $product_name";

		if (strlen($product_name)) {
			$product_name = Fresh_PriceList::StripProductName($product_name, $debug);
			$sql = "SELECT product_id, id FROM im_supplier_mapping " .
			       " WHERE supplier_product_name like '%$product_name%'" .
			       " AND supplier_id = " . $result["supplier_id"];

			if ($debug) MyLog($sql);
			if ( ! $include_hide ) $sql .= " and product_id > 0";

			$result = SqlQuery( $sql );
			if ( $result ) {
				while ( $row = mysqli_fetch_row( $result ) ) {
					if ( ! in_array( $row[0], $result_ids ) ) array_push( $result_ids, $row[0] );
					if ( $debug ) MyLog( "name link to $pricelist_id " . $row[0] );
				}
			}
		}

		return $result_ids;
	}


	static function GetBuyPrice( $product_id, $supplier ) {
		// print "prod_id = " . $product_id . " supplier = " . $supplier . "<br/>";
		$alternatives = Fresh_Catalog::alternatives( $product_id );
		if (! $alternatives) return 0;
		for ( $i = 0; $i < count( $alternatives ); $i ++ ) {
			if ( $supplier == -1 or $alternatives[ $i ]->getSupplierId() == $supplier) {
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

		$product_name_prf = self::name_prefix( $product_name );
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

		$result1 = SqlQuery( $sql1 );

		while ( $row1 = mysqli_fetch_assoc( $result1 ) ) {
			array_push( $array, $row1 );
		}

		return $array;
	}

	function DeleteMappingByPricelistId( $pricelist_id ) {
		MyLog( "delete_mapping", "catalog-map.php" );

		$sql = "delete from im_supplier_mapping where pricelist_id = $pricelist_id";

		MyLog( $sql, "catalog-map.php" );

		SqlQuery( $sql );
	}

	function HideProduct( $pricelist_id )
	{
		MyLog( "null_mapping", "catalog-map.php" );

		// Good if already mapped
		SqlQuery( "UPDATE im_supplier_mapping SET product_id =-1 WHERE pricelist_id = " . $pricelist_id );

		// Otherwise need to add map to -1
		if ( SqlAffectedRowscted_rows( ) < 1 ) {
			$this->AddMapping( - 1, $pricelist_id, Core_Db_MultiSite::LocalSiteID() );
		}
	}

	static function auto_mail() {
		return false;
		global $business_name;
		global $support_email;

		$sql = "SELECT user_id FROM wp_usermeta WHERE meta_key = 'auto_mail'";

		$auto_list = SqlQueryArrayScalar( $sql );

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
//			print Core_Html::gui_header(1, get_term_name($categ)) . "<br/>";
			$iter = new Fresh_ProductIterator();
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

	static function missing_pictures()
	{
		$result = "";
		$sql    = "SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'product_cat'";
		$categs = SqlQueryArrayScalar( $sql );

		$result .= Core_Html::gui_header(1, "תמונות חסרות באתר");

		foreach ($categs as $categ){
			$c = new Fresh_Category($categ);
			$missing_pictures = $c->get_missing_pictures();
			if ($missing_pictures and count($missing_pictures)){
				$result .= Core_Html::gui_header(2, $c->getName());

				foreach ($missing_pictures as $prod_id){
					// https://fruity.co.il/wp-admin/post.php?post=7015&action=edit
					$p = new Fresh_Product($prod_id);
					$result .= Core_Html::GuiHyperlink($p->getName(), get_site_url() . "/wp-admin/post.php?post=$prod_id&action=edit") . "<br/>";
				}
			}
		}
		print $result;
	}

	static function best_alternative($prod_id, $debug = false) : ?Fresh_Pricelist_Item
	{
		if ($debug) MyLog($prod_id, __FUNCTION__);

		$alternatives = self::alternatives($prod_id, $debug);
		$min  = 1111111;
		$best = null;
		if (! $alternatives) {
			if ($debug) MyLog("no alternatives");
			return null;
		}
		for ( $i = 0; $i < count( $alternatives ); $i ++ ) {
			$price = Fresh_Pricing::calculate_price( $alternatives[ $i ]->getPrice(), $alternatives[ $i ]->getSupplierId(),	$alternatives[ $i ]->getSalePrice());
			if ( $price < $min ) {
				$best = $alternatives[ $i ];
				$min = $price;
			}
		}

		return $best;
	}

	static function alternatives( $prod_id, $debug = false)
	{
		if ( ! is_numeric($prod_id) or !( $prod_id > 0 ) ) {
			print debug_trace(6);
			return null;
		}

		// Search by pricelist_id
		$sql = "select price, supplier_id, id, sale_price ".
		       " from im_supplier_price_list where product_id = $prod_id";

		$result = SqlQuery( $sql );
		$output = "";
		if ( ! $result ) {
			SqlError( $sql );

			return null;
		} else {
			$rows = array();
			while ( $row = mysqli_fetch_row( $result ) ) {
				if ($debug) MyLog("supplier_id = ". $row[1], __FUNCTION__);

				$n = new Fresh_Pricelist_Item( $row[2] );
//				print "adding1 pl $row[2]<br/>";
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
		AND m.product_id = $prod_id
		AND pl.date = supplier_last_pricelist_date(pl.supplier_id)";

		$result = SqlQuery( $sql );
		$c      = 0;
		while ( $row = mysqli_fetch_row( $result ) ) {
			$supplier_id = $row[1];
			$found = false;
			// Don't repeat alternatives
			for ( $i = 0; $i < count( $rows ); $i ++ ) {
				if ( $rows[ $i ]->getSupplierId() == $supplier_id ) {
					$found = true;
				}
			}
			if ( ! $found ) {
				// array_push( $rows, $row );
//				print "adding2 pl " . $row[2] . "<br/>";
				array_push( $rows, new Fresh_Pricelist_Item( $row[2]));
			}
		}

		return $rows;
	}

	static function name_prefix( $name ) {
		return strtok( $name, "-()" );
	}

	static function show_catalog(
		$for_update = false, $search_text = null, $csv= false, $active = false, $siton = false, $buy = true, $category = null,
		$order = false, $suppliers = null, $inv = null
	) {
		global $header_fields;
		$show_fields = array();

		// print "search text: " . $search_text . "<br/>";
		// print "category: " . $category . "<br/>";
		for ( $i = 0; $i < CatalogFields::field_count; $i ++ ) {
			$show_fields[ $i ] = false;
		}

		$show_fields[ CatalogFields::name ] = true;

		if ( $siton ) {
			$show_fields[ CatalogFields::id ]    = true;
			$show_fields[ CatalogFields::price ] = true;
		}
		if ( $buy ) {
			$show_fields[ CatalogFields::id ]         = true;
			$show_fields[ CatalogFields::cost_price ] = true;
			$show_fields[ CatalogFields::supplier ]   = true;
		} else {
			$show_fields[ CatalogFields::vat ] = true;
		}

		$count = 0;
		MyLog( "search_text = " . $search_text, "catalog-db-query.php" );
		$sql = 'select '
		       . ' id, post_title '
		       . ' from wp_posts '
		       . ' where post_type = \'product\'';


		if ( $search_text <> "" ) {
			if ( is_numeric( $search_text ) ) {
				$sql .= ' and id = ' . $search_text . ' ';
			} else {
				$ids = explode( "|", $search_text );
				if ( is_array( $ids ) and is_numeric( $ids[0] ) ) {
					$sql .= ' and id in (';
					foreach ( $ids as $id ) {
						$sql .= $id . ", ";
					}
					$sql = rtrim( $sql, ", " ) . ")";

				} else {
					$sql .= " and post_title like '%" . $search_text . "%'";
				}
			}
		}
//	}

		if ( $active ) {
			$sql .= ' and post_status = \'publish\'';
		}

		$sql .= ' order by 2';

		$result = SqlQuery( $sql );

		$data = "<table>";

		$data        .= Core_Html::gui_row( $header_fields, "", $show_fields );
		$line_number = 0;//	default:

		if ( $for_update ) {
			// print "update<br/>";
			$show_fields[ CatalogFields::line_select ] = true;
			$show_fields[ CatalogFields::status ]      = true;
			$show_fields[ CatalogFields::category ]    = true;

		}
		if ( ! is_null( $inv ) ) {
			print "show inv<br/>";
			$show_fields[ CatalogFields::id ]        = true;
			$show_fields[ CatalogFields::inventory ] = true;
		}
		if ( $order ) {
			$show_fields[ CatalogFields::order ] = true;
		}

		while ( $row = mysqli_fetch_row( $result ) ) {
			$fields = array();
			for ( $i = 0; $i < CatalogFields::field_count; $i ++ ) {
				$fields[ $i ] = "";
			}
			$line_number ++;
			$prod_id = $row[0];

			// prof_flag("handle " . $prod_id);
			$product_categories = array();

			$terms = get_the_terms( $prod_id, 'product_cat' );

			if ( $category ) { // Check if this product in the given categories
				if ( $terms ) { // Products terms
					foreach ( $terms as $term ) {
						array_push( $product_categories, $term->term_id );
					}
				} else {
					// print "no terms for " . $prod_id . "<br/>";
					continue;
				}

				if ( sizeof( array_intersect( $product_categories, $category ) ) == 0 ) {
					continue;
				}
			}

			$fields[ CatalogFields::name ] = $row[1];
			// print "XXX" . CatalogFields::name . "XXX<br/>";

			$fields[ CatalogFields::line_select ] = "<input id=\"chk" . $prod_id . "\" class=\"product_checkbox\" type=\"checkbox\">";
			$fields[ CatalogFields::id ]          = Core_Html::GuiHyperlink( $prod_id, "../../wp-admin/post.php?post=" . $prod_id . " &action=edit" );
			$p                                    = new Fresh_Product( $prod_id );

			// price
			$price = get_postmeta_field( $prod_id, '_price' );
			if ( $for_update ) {
				$fields[ CatalogFields::price ] = '<input type="text" value="' . $price . '">';
			} else {
				if ( $siton ) {
					$price = siton_price( $prod_id );
				} else if ( $buy ) {
					$price = Fresh_Pricing::get_buy_price( $prod_id );
				}
				$fields[ CatalogFields::price ] = $price;
			}

			// vat percent
			$vat_percent = get_postmeta_field( $prod_id, 'vat_percent' );
			if ( $vat_percent )
				$fields[ CatalogFields::vat ] = $vat_percent . "%";

			// $fields[CatalogFields::supplier] = get_postmeta_field( $prod_id, 'supplier_name' );

			$fields[ CatalogFields::status ] = get_post_status( $prod_id );

			if ( $buy ) {
				// prof_flag("alternative-start" . $prod_id);
				$alternatives = Fresh_Catalog::alternatives( $prod_id );
				$supplies     = "";
				foreach ( $alternatives as $alt ) {
					$supplies .= $alt->getSupplierName(). ", ";
//						get_supplier_name( $alt->getSupplierId() ) . ", ";
				}

				$fields[ CatalogFields::supplier ] = rtrim( $suppliers, ", " );
			}

			$P = new Fresh_Product($prod_id);
			$fields[ CatalogFields::cost_price ] = $P->getBuyPrice();
			$fields[ CatalogFields::supplier ]   = $P->getSupplierName();

			$show_in_list = 1;

			// $fields[CatalogFields::order] =gui_cell( gui_input( "prod_quantity" . $prod_id, "", null, "q_" . $prod_id ) );
			$fields[ CatalogFields::category ] = CommaImplode( $terms );

			if ( ! is_null( $inv ) ) {
				if ( ! $p->getStockManaged() or $p->getStock() > $inv ) {
					continue;
				}
				$fields[ CatalogFields::inventory ] = $p->getStock();
			}

			if ( $show_in_list ) {
				// var_dump($fields); print "<br/>";
				// var_dump($show_fields); print "<br/>";
				$data .= Core_Html::gui_row( $fields, "cat", $show_fields, $sums);
			}
			// prof_flag("end " . $prod_id);
			$count ++;
		}
		$data .= "</table>";
		if ( $csv ) {
			$data = str_replace( "</td><td>", ",", $data );
			$data = str_replace( "<tr>", "<br>", $data );
			$data = str_replace( "<td>", "", $data );
		}

		print $data;
	}

	static function upload_image( $post_id, $image ) {
		$get = wp_remote_get( $image );

		$type = wp_remote_retrieve_header( $get, 'content-type' );

		if ( $type ) {
			$mirror = wp_upload_bits( basename( $image ), '', wp_remote_retrieve_body( $get ) );
			if (isset($mirror['error'])) return false;
			$attachment = array(
				'post_title'     => basename( $image ),
				'post_mime_type' => $type
			);

			$attachment_id = wp_insert_attachment( $attachment, $mirror['file'], $post_id );

			wp_generate_attachment_metadata( $attachment_id, $mirror['file'] );

			set_post_meta_field( $post_id, '_thumbnail_id', $attachment_id );
		}
	}

}

//function best_alternatives( $alternatives, $debug = false ) {
//	MyLog( "best_alternatives" );
//	$min  = 1111111;
//	$best = null;
//	for ( $i = 0; $i < count( $alternatives ); $i ++ ) {
//		//$price, $supplier, $sale_price = '', $terms = null
//		$price = calculate_price( $alternatives[ $i ]->getPrice(), $alternatives[ $i ]->getSupplierId(),
//			$alternatives[ $i ]->getSalePrice());
////		print "price: " . $alternatives[ $i ]->getSupplierId() . " " . $price . "<br/>";
//		MyLog( "price $price" );
//		if ( $price < $min ) {
//			$best = $alternatives[ $i ];
//			// my_log( "best $best[0]" );
//			$min = $price;
//		}
//	}
//
//	return $best;
//}

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
//require_once( ABSPATH . '/wp-admin/includes/image.php' );
//
//
//function supplier_prod_id( $prod_id, $supplier_id ) {
//	$a = alternatives( $prod_id, false );
//
////	print "<br/>";
////	print "supplier: " . $supplier_id . "<br/>";
//	foreach ( $a as $s ) {
////		print "option: " . $s->getSupplierID() . "<br/>";
//		if ( $s->getSupplierId() == $supplier_id ) {
//			// print "found<br/>";
//			return $s->getSupplierProductCode();
//		}
//	}
//
//	return 0;
//}
//

//if (get_user_id() == 1)
//	print Fresh_Catalog::show_catalog();

class CatalogFields {
	const
		/// User interface
		line_select = 0,
		id = 1,
		name = 2,
		price = 3,
		vat = 4,
		supplier = 5,
		status = 6,
		category = 7,
		order = 8,
		cost_price = 9,
		inventory = 10,
		field_count = 11;
}

function GetProductParernt( $prod_id ) {
	return SqlQuerySingleScalar( "SELECT post_parent FROM wp_posts WHERE id = " . $prod_id );
}
