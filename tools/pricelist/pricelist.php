<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/12/15
 * Time: 10:16
 */
// require_once( '../im-tools.php' );
// error_reporting( E_ALL );
// ini_set( 'display_errors', 'on' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/catalog/catalog.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( TOOLS_DIR . '/multi-site/multi-site.php' );
require_once( TOOLS_DIR . '/wp/Product.php' );
require_once( TOOLS_DIR . "/orders/orders-common.php" );
require_once( TOOLS_DIR . "/orders/Order.php" );

class PricelistItem {
	private $id;
	private $product_name;
	private $supplier_id;
	private $date;
	private $price;
	private $supplier_product_code;
	private $sale_price;
	private $category;
	private $picture_path;

	function __construct( $pricelist_id ) {
		$sql = " SELECT product_name, supplier_id, date, price, supplier_product_code, sale_price, category, picture_path FROM im_supplier_price_list " .
		       " WHERE id = " . $pricelist_id;

		$result = sql_query_single_assoc( $sql );
		if ( $result == null ) {
			print "pricelist item $pricelist_id not found<br/>";
			die( 1 );
		}
		$this->id                    = $pricelist_id;
		$this->product_name          = $result["product_name"];
		$this->supplier_id           = $result["supplier_id"];
		$this->date                  = $result["date"];
		$this->price                 = $result["price"];
		$this->supplier_product_code = $result["supplier_product_code"];
		$this->sale_price            = $result["sale_price"];
		$this->category              = $result["category"];
		$this->picture_path          = $result["picture_path"];
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @return mixed
	 */
	public function getProductName() {
		return $this->product_name;
	}

	/**
	 * @return mixed
	 */
	public function getSupplierId() {
		return $this->supplier_id;
	}

	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * @return mixed
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @return mixed
	 */
	public function getSupplierProductCode() {
		return $this->supplier_product_code;
	}

	/**
	 * @return mixed
	 */
	public function getSalePrice() {
		return $this->sale_price;
	}

	/**
	 * @return mixed
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @return mixed
	 */
	public function getPicturePath() {
		return $this->picture_path;
	}

	public function getSellPrice() {
		return calculate_price( $this->price, $this->supplier_id, $this->sale_price );
	}

	public function getSupplierName() {
		return get_supplier_name( $this->supplier_id );
	}
}


class UpdateResult {
	const UsageError = 0;
	const UpPrice = 1;
	const NoChangPrice = 2;
	const DownPrice = 3;
	const ExitPrice = 4;
	const NewPrice = 5;
	const SQLError = 6;
	const DeletePrice = 7;
	const NotUsed = 8;
}

class PriceList {
	private $SupplierID = 0;

	public function __construct( $id ) {
		$this->SupplierID = $id;
	}

	static function DeleteMapping( $pricelist_id ) {
		// Get product id
		$prod_ids    = Catalog::GetProdID( $pricelist_id );
		$supplier_id = sql_query_single_scalar( "SELECT supplier_id FROM im_supplier_price_list WHERE ID = " . $pricelist_id );
//		print "supplier id: " . $supplier_id . "<br/>";

		foreach ( $prod_ids as $prod_id ) {
//			print "prod id: " . $prod_id . "<br/>";
			$sql = "DELETE FROM im_supplier_mapping WHERE product_id = " . $prod_id . " AND supplier_id = " . $supplier_id;
//			print $sql . "<br/>";
			sql_query( $sql );

			$line = "";
			Catalog::UpdateProduct( $prod_id, $line );
//			print header_text(false, true, false);
//			print $line;

		}

		return;
	}

	function Refresh() {
		$priceslist_items = sql_query_array_scalar( "SELECT id FROM im_supplier_price_list WHERE supplier_id = " . $this->SupplierID );
		foreach ( $priceslist_items as $pricelist_id ) {
			$prod_ids = Catalog::GetProdID( $pricelist_id );
			foreach ( $prod_ids as $prod_id ) {
				print "update " . $prod_id . get_product_name( $prod_id ) . "<br>";

				Catalog::UpdateProduct( $prod_id, $line );
			}
		}
	}

	function SiteId() {
		return sql_query_single_scalar( "SELECT site_id FROM im_suppliers WHERE id =" . $this->SupplierID );
	}

	function PrintCSV() {
		global $conn;

		$sql = 'SELECT product_name, price, supplier_product_code' .
		       ' FROM im_supplier_price_list pl '
		       . ' where supplier_id = ' . $this->SupplierID;

		$result = mysqli_query( $conn, $sql );

		print "שם, מחיר, קוד\n";

		while ( $row = mysqli_fetch_row( $result ) ) {
			print $row[0] . ", " . $row[1] . ", " . $row[2] . "\n";
		}
	}

	function PrintHTML( $ordered_only = false, $need_supply_only = false ) {
		// print "nso=" . $need_supply_only . "oo=" . $ordered_only . "<br/>";
		Order::CalculateNeeded( $needed_products );

		$catalog = new Catalog();

		$sql = 'SELECT product_name, price, date, pl.id, supplier_product_code, s.factor ' .
		       ' FROM im_supplier_price_list pl ' .
		       ' Join im_suppliers s '
		       . ' where supplier_id = ' . $this->SupplierID
		       . ' and s.id = pl.supplier_id '
		       . ' order by 1';

		$result = sql_query( $sql );

		$table_rows = array(
			array(
				gui_checkbox( "select_all", "", false,
					'onclick="select_all_toggle(\'select_all\', \'product_checkbox\')"' ),
				"קוד פריט",
				"שם פריט",
				"תאריך שינוי",
				"מחיר קנייה",
				"מחיר מחושב",
				"קטגוריות",
				"שם מוצר",
				"מחיר מכירה",
				"מחיר מבצע",
				"מזהה",
				"מנוהל מלאי",
				"יתרה במלאי",
				"כמות בהזמנות פתוחות",
				"מחירים נוספים"
			)
		);

		$show_fields = array( true, true, true, true, true, true, true, true, true, true, true, true );
		// Add new item fields
		while ( $row = mysqli_fetch_row( $result ) ) {
			$pl_id     = $row[3];
			$link_data = $catalog->GetProdID( $pl_id );
			$prod_id   = "";
			$map_id    = "";
			if ( $link_data ) {
				$prod_id = $link_data[0];
				$p       = new Product( $prod_id );
				$map_id  = null;

				if ( isset( $link_data[1] ) ) {
					$map_id = $link_data[1];
				}
			// print $prod_id . " " . $map_id . "<br/>";
				if ( $ordered_only and ! ( ( $needed_products[ $prod_id ][0] or $needed_products[ $prod_id ][1] ) ) ) {
					continue;
				}
				if ( $need_supply_only and ( $needed_products[ $prod_id ][0] < $p->getStock() ) ) {
					continue;
				}
			} else {
				// print "nso=" . $need_supply_only . "<br/>";
				if ( $need_supply_only or $ordered_only ) {
					continue;
				}
			}
			array_push( $table_rows, $this->Line( $row[0], $row[1], $row[2], $pl_id, $row[4], $row[5], $prod_id, true, $map_id, $needed_products ) );
			// $data .= $line;
		}

		$sum  = null;
		$data = gui_table( $table_rows, "pricelist", true, true, $sum, null, null, $show_fields );
		//( $rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null, $show_fields = null,
		// $links = null)
		// $data .= "<tr>";
		$data .= "<td>" . gui_button( "add", "add_item()", "הוסף" ) . "</td>";
		$data .= gui_cell( gui_input( "product_code", "" ) );
		$data .= gui_cell( gui_input( "product_name", "" ) );
		$data .= gui_cell( "" );

		$data .= gui_cell( gui_input( "price", "" ) );
		$data .= "</tr>";

		print $data;
	}

	private function Line( $product_name, $price, $date, $pl_id, $supplier_product_code, $factor, $linked_prod_id, $editable = true, $map_id, $needed = null ) {
		$calc_price = round( $price * ( 100 + $factor ) / 100, 1 );

		$line = array();
		array_push( $line, gui_checkbox( "chk" . $pl_id, "product_checkbox" ) );
		//$line .= "<td><input id=\"chk" . $pl_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
		array_push( $line, $supplier_product_code );
		// $line .= "<td>" . $supplier_product_code . "</td>";
		array_push( $line, $product_name );
		//$line .= "<td>" . $product_name . "</td>";
		array_push( $line, $date );
		// $line .= "<td>" . $date . "</td>";

		//$line .= "<td>";
		if ( $editable ) {
			array_push( $line, gui_input( "prc_" . $pl_id, $price, array( 'onchange="changed(this)"' ), "prc_" . $pl_id, "", 7 ) );
		} else {
			array_push( $line, $price );
		}
		// $line .= "</td>";
		// $line .= '<td><input type="text" value="' . $price . '"</td>';
		array_push( $line, $calc_price );
		// $line .= '<td>' . $calc_price . '</td>';
		$category = sql_query_single_scalar( "SELECT category FROM im_supplier_price_list WHERE id = " . $pl_id );
		array_push( $line, $category );
		if ( $linked_prod_id > 0 ) {
			$p = new Product( $linked_prod_id );
			array_push( $line, get_product_name( $linked_prod_id ) );
			array_push( $line, get_price( $linked_prod_id ) );
			array_push( $line, get_sale_price( $linked_prod_id ) );
			array_push( $line, $linked_prod_id );
			$stockManaged = $p->getStockManaged();
			array_push( $line, gui_checkbox( "chm_" . $linked_prod_id, "stock", $stockManaged, "onchange=\"change_managed(this)\")" ) );
			array_push( $line, gui_lable( "stk_" . $linked_prod_id, $p->getStock() ));
			$n = orders_per_item( $linked_prod_id, 1, true, true, true );
//			if (isset($needed[$linked_prod_id][0]))
//				$n .= $needed[$linked_prod_id][0];
//			if (isset($needed[$linked_prod_id][1]))
//				$n .= $needed[$linked_prod_id][1] . "יח";

			array_push( $line, gui_lable( "ord_" . $linked_prod_id,$n) );
			array_push( $line, product_other_suppliers( $linked_prod_id, $this->SupplierID ) );

			// $line .= '<td>' . get_product_name( $linked_prod_id ) . '</td>';
			// $line .= '<td>' . get_price( $linked_prod_id ) . '</td>';
			//		$line .= '<td>' . get_sale_price( $linked_prod_id ) . '</td>';
		} else {
			if ( $linked_prod_id == - 1 ) {
				array_push( $line, "לא למכירה", "", "" );
				// $line .= "<td>לא למכירה</td><td></td><td></td>";
			} else {
				//var_dump(Catalog::GetProdOptions($product_name));die (1);
				array_push( $line, gui_select( "prd" . $pl_id, "post_title", Catalog::GetProdOptions( $product_name ), "onchange=selected(this)", "" ) );
				// array_push( $line, "", "", "" );
				// $line .= "<td></td><td></td><td></td>";
			}
		}
		// $line     .= gui_cell( $category);
//		if ( $linked_prod_id > 0 ) {
//			array_push( $line, gui_cell( $linked_prod_id ) );
//			// $line         .= gui_cell( $linked_prod_id );
//		//		$line         .= gui_cell( $map_id );
//			array_push( $line, gui_cell( $map_id ) );
//			if ( $needed ) {
//		//			var_dump($needed);
//		//			die(1);
//
//			}
//
//		}
		// get_product_name()
		// $line .= "</tr>";

		return $line;
	}

	function GetUpdateDate() {
		$sql = 'SELECT max(date) FROM im_supplier_price_list'
		       . ' WHERE supplier_id = ' . $this->SupplierID;

		$result = sql_query( $sql );

		$row = mysqli_fetch_row( $result );

		return $row[0];
	}

	function GetFactor() {
		$sql = 'SELECT factor FROM im_suppliers'
		       . ' WHERE id = ' . $this->SupplierID;

		$result = sql_query( $sql );

		$row = mysqli_fetch_row( $result );

		return $row[0];
	}

	function AddOrUpdate(
		$regular_price, $sale_price, $product_name, $code = 10, $category, &$id, $parent_id = null,
		$picture_path = null
	) {
		$debug = true;
		print "start";
		print "AddOrUpdate: " . $product_name . " " . $regular_price . "<br/>";
		my_log( __METHOD__, __FILE__ );
		if ( mb_strlen( $product_name ) > 40 ) {
			$product_name = mb_substr( $product_name, 0, 40 );
		}
		if ( ! is_numeric( $sale_price ) ) {
			$sale_price = 0;
		}
		if ( ! is_numeric( $regular_price ) ) {
			print "Bad price: " . $regular_price;

			return UpdateResult::UsageError;
		}
		// print "Add: " . $product_name . ", " . $regular_price . " " . $category . "<br/>";
		global $conn;

		// Change if line exits.
		$sql = "select id, price " .
		       " from im_supplier_price_list " .
		       " where supplier_id = " . $this->SupplierID .
		       " and product_name = '" . $product_name . "'";

		$date = date( 'y/m/d' );

		$id     = 0;
		$result = mysqli_query( $conn, $sql );

		if ( $result ) {
			$row = mysqli_fetch_row( $result );
			$id  = $row[0];
		}
		if ( $id == - 1 ) { // Hide
			return UpdateResult::NotUsed;
		}
		if ( $id > 0 ) {
			if ( $debug ) {
				print "Exists, update.. ";
			}
			$old_price = $this->Get( $id )["price"];
			$sql       = "update im_supplier_price_list " .
			             " set line_status = 1, price = " . $regular_price . ", sale_price = " . $sale_price .
			             ", date = '" . $date . "' ";

			if ( isset( $category ) ) {
				$sql .= ", category = '" . $category . "'";
			}

			if ( isset( $picture_path ) ) {
				$sql .= ", picture_path = '" . mysqli_real_escape_string( $conn, $picture_path ) . "'";
			} else {
				$sql .= ", picture_path = null";
			}
			$sql .= " where product_name = '" . $product_name . "' and supplier_id = " . $this->SupplierID;

			//  print "<br/>"  . $product_name . "<br/>";
			// print "<p dir='ltr'>"  . $sql . "</p>";

			$result = mysqli_query( $conn, $sql );
			if ( ! $result ) {
				sql_error( $sql );

				return UpdateResult::SQLError;
			}

			if ( $regular_price > $old_price ) {
				$rc = UpdateResult::UpPrice;
			} else if ( $regular_price < $old_price ) {
				$rc = UpdateResult::DownPrice;
			} else {
				$rc = UpdateResult::NoChangPrice;
			}

		} else {
			if ( $code == "" ) {
				$code = "10";
			}

			$sql = "INSERT INTO im_supplier_price_list (product_name, supplier_id, "
			       . "date, price, sale_price, supplier_product_code, line_status";

			$values = "VALUES ('" . addslashes( $product_name ) . "', " . $this->SupplierID .
			          ", " . "'" . $date . "', " . $regular_price . ", " . $sale_price . ", " . $code . ", 1";

			if ( $parent_id > 0 ) {
				// Variation
				$sql    .= ", variation, parent_id";
				$values .= ", 1, " . $parent_id;
			} else {
				// Product
				$sql    .= ", variation";
				$values .= ", 0";
			}

			if ( isset( $category ) ) {
				$sql    .= ", category ";
				$values .= ", '" . $category . "'";
			}
			if ( isset( $picture_path ) ) {
				$sql    .= ", picture_path ";
				$values .= ", '" . mysqli_real_escape_string( $conn, $picture_path ) . "'";
			}
			// Complete the sql statement
			$sql .= ") " . $values . ")";

			// print "<p dir=ltr>" . $sql . "</p>";

			$result = mysqli_query( $conn, $sql );
			if ( ! $result ) {
				sql_error( $sql );

				return UpdateResult::SQLError;
			}
			// Output
			$id = mysqli_insert_id( $conn );
			$rc = UpdateResult::NewPrice;
		}
		// Update linked products
		$this->Update( $id, $regular_price, $sale_price );
		if ( $debug ) {
			print "<br/>";
		}

		return $rc;
	}

	// Also called when mapping is deleted

	static function Get( $pricelist_id ) {
		// my_log("Pricelist::Get" . $pricelist_id);
		$sql = " SELECT product_name, supplier_id, date, price, supplier_product_code, sale_price, category, picture_path FROM im_supplier_price_list " .
		       " WHERE id = " . $pricelist_id;

		$result = sql_query_single_assoc( $sql );

		return $result;
	}

	// Return code: 0 - usage error: error. 1:
	// ID: output the pricelist id

	function Update( $id, $price, $sale_price = 0 ) {
		global $conn;
		my_log( __METHOD__, "update line $id, price $price, sale price $sale_price" );
		$sql = "UPDATE im_supplier_price_list SET price = " . $price .
		       ", sale_price = " . $sale_price .
		       ", date = '" . date( 'y/m/d' ) . "' " .
		       " WHERE id = " . $id;
		mysqli_query( $conn, $sql );

		$this->UpdateCatalog( $id );

//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=update_in_slave&price=" . $price .           "&line_id=" . $line_id);

		//        $date = date('y/m/d');
//        my_log(__FILE__, __METHOD__);
//        my_log(__FILE__, "supplier: " . $this->SupplierID . ", price = " . $price . ", product name = " . $product_name_code);
//        if (! is_numeric($this->SupplierID)) {
//            die("bad supplier id: " . $this->SupplierID);
//        }
//        if (! is_numeric($price)) {
//            die ("Bad price: " . $price );
//        }
//        $link = $GLOBALS["glink"];
//        if (is_numeric($product_name_code) and $product_name_code != 10) {
//            $sql = "update im_supplier_price_list set date = '" . $date . "', price = " . $price
//                . " where supplier_product_code = '" . $product_name_code . "' and supplier_id = " . $this->SupplierID;
//        } else {
//            $sql = "update im_supplier_price_list set date = '" . $date . "', price = " . $price
//                . " where product_name = '" . addslashes($product_name_code) . "' and supplier_id = " . $this->SupplierID;
//        }
//
//        my_log($sql, "catalog_update_price");
//        if (! $export)
//            die ('Invalid query: ' . mysql_error());
		return;
	}

	static function UpdateCatalog( $pricelist_id ) {
		$debug    = true;
		$prod_ids = Catalog::GetProdID( $pricelist_id );
		$line     = "";
		if ( $debug ) {
			print "update";
		}
		if ( $prod_ids ) {
			foreach ( $prod_ids as $prod_id ) {
				if ( $debug ) {
					print $prod_id . " ";
				}
				my_log( __METHOD__, "update product $prod_id" );
				Catalog::UpdateProduct( $prod_id, $line );
				my_log( $line );
			}
		}

	}

	function GetByName( $product_name ) {
		$product_name = pricelist_strip_product_name( $product_name );

		$sql = "SELECT price FROM im_supplier_price_list "
		       . " WHERE product_name = '" . addslashes( $product_name ) . "' AND supplier_id = " . $this->SupplierID;

		return sql_query_single_scalar( $sql );
	}

//    function DraftRemoved()
//    {
//        global $conn;
//
//        $sql = "select id from ihstore.im_supplier_price_list where " .
//            " supplier_id = " . $this->SupplierID . " and line_status = 2";
//        $result = mysqli_result($sql);
//
//        if (!$result){
//            handle_sql_error($sql, $conn);
//        }
////        $table_name = "temp_supplier_" . $this->SupplierID;
////
////        $sql = "SELECT a.id " .
////            " FROM " . $table_name . " a " .
////            " LEFT JOIN im_supplier_price_list b " .
////            " ON a.product_name = b.product_name AND b.supplier_id = " . $this->SupplierID .
////            " WHERE b.id IS NULL ";
////
//////        print $sql . "<br/>";
////        $result = mysqli_query($conn, $sql);
////        if (! $result) die ($sql . mysqli_error($conn));
////
////        while ($row = mysqli_fetch_row($result))
////        {
////            print $row[0];
////        }
//    }

	function ChangeStatus( $status ) {
		// Act local
		global $conn;

		$sql = "UPDATE im_supplier_price_list SET line_status = " . $status . " WHERE supplier_id = " . $this->SupplierID;

//        print $sql;

		$result = mysqli_query( $conn, $sql );

		if ( ! $result ) {
			handle_sql_error( $sql );
		}

		// Act remote
//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=change_status" . "&status=" . $status);
	}

//    function ExecuteRemotes($url)
//    {
//        // print "ExecuteRemotes: " . $url . "<br/>";
//        // my_log("Execute " . $url);
//        global $conn;
//
//        $sql = "select remote_site_id, remote_supplier_id from im_multisite_pricelist " .
//            " where supplier_id = " . $this->SupplierID;
//
//        my_log($sql);
//
//        $result = mysqli_query($conn, $sql);
//
//        if ($result)
//        while ($row = mysqli_fetch_row($result)) {
//            $url .= "&supplier_id=" . $row[1];
//            my_log($url, $row[0]);
//            MultiSite::Execute($url, $row[0]);
//        }
//    }

	function RemoveLines( $status ) {
		$removed = Array();

		// print "Removing previous items...<br/>";
		global $conn;
		$sql = "select id, price, product_name " .
		       " from im_supplier_price_list " .
		       " where line_status = " . $status .
		       " and supplier_id = " . $this->SupplierID;

		print $sql;

		$result = mysqli_query( $conn, $sql );

		if ( ! $result ) {
			handle_sql_error( $sql );
		}

		while ( $row = mysqli_fetch_row( $result ) ) {
			$id = $row[0];
			print "removing " . $id . "<br/>";
			my_log( "Remove " . $id );
			$this->Delete( $id );
			$removed[] = array( $row[2], $row[1] );
			// var_dump($ids);
		}
//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=delete_price&params=" . implode(",", $ids));

//        print "Done<br/>";
		// var_dump($removed);
		return $removed;
	}

	function Delete( $pricelist_id ) {
		global $conn;
		my_log( __METHOD__ . $pricelist_id );

		// Check if this product linked.
		$prod_info = catalog::GetProdID( $pricelist_id );
		if ( $prod_info ) {
			$prod_id = $prod_info[0];
		} else {
			$prod_id = 0;
		}
		my_log( "Delete $pricelist_id $prod_id" );

		my_log( "Delete. id = " . $pricelist_id );
		my_log( "catalog_delete_price", "pricelist-post.php" );
		$sql = "DELETE FROM im_supplier_price_list  "
		       . " WHERE id = " . $pricelist_id;

		mysqli_query( $conn, $sql );

		// The mapping stays - in case supplier gets it back.

		// If no other option for this product - make it draft
		if ( $prod_id > 0 ) {
			$line = "";
			Catalog::UpdateProduct( $prod_id, $line );
		}
	}
}

function pricelist_get_price( $prod_id ) {
	// my_log("prod_id = " . $prod_id);
	if ( ! ( $prod_id > 0 ) ) {
		print "missing prod_id " . $prod_id . "<br/>";
		die ( 1 );
	}
	$supplier_id = get_supplier_id( get_postmeta_field( $prod_id, "supplier_name" ) );

	$sql = 'SELECT price FROM im_supplier_price_list WHERE supplier_id = \'' . $supplier_id . '\'' .
	       ' AND product_name IN (SELECT supplier_product_name FROM im_supplier_mapping WHERE product_id = ' . $prod_id . ')';

	return sql_query_single_scalar( $sql );
}



function pricelist_strip_product_name( $name ) {
	// trim sadot product name starting with * or **
	$name = str_replace( array( '.', ',', '*', '\'' ), '', $name );
	$name = str_replace( array( ')', '(', '-' ), ' ', $name );

	return $name;
}

function product_other_suppliers( $prod_id, $supplier_id ) {
	$result       = "";
	$alternatives = alternatives( $prod_id );
	foreach ( $alternatives as $alter ) {
		$a_supplier_id = $alter->getSupplierId();
		if ( $a_supplier_id != $supplier_id ) {
			$result .= get_supplier_name( $a_supplier_id ) . " " . $alter->getPrice() . ", ";
		}
	}

	return rtrim( $result, ", " );
}

?>