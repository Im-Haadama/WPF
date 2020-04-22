<?php


class Fresh_PriceList {
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
//				print "update " . $prod_id . get_product_name( $prod_id ) . "<br>";

				Catalog::UpdateProduct( $prod_id, $line );
			}
		}
	}

	function SiteId() {
		return sql_query_single_scalar( "SELECT site_id FROM im_suppliers WHERE id =" . $this->SupplierID );
	}

	function PrintCSV() {
		$sql = 'SELECT product_name, price, supplier_product_code' .
		       ' FROM im_supplier_price_list pl '
		       . ' where supplier_id = ' . $this->SupplierID;

		$result = sql_query( $sql );

		print "שם, מחיר, קוד\n";

		while ( $row = mysqli_fetch_row( $result ) ) {
			print $row[0] . ", " . $row[1] . ", " . $row[2] . "\n";
		}
	}

	function PrintHTML( $ordered_only = false, $need_supply_only = false, $args = null) {
		$create_option = false;
		if (isset($args) and isset($args["create_products"])){
			print Core_Html::gui_header(1, "יצירת מוצרים");
			print gui_datalist( "category", "im_categories", "name", 0 );
			$create_option = true;
		}
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
				0, // Instead of id.
				gui_checkbox( "select_all", "", false,
					'onclick="select_all_toggle(\'select_all\', \'product_checkbox\')"' ),
				"קוד פריט",
				"שם פריט",
				"תאריך שינוי",
				"מחיר קנייה",
				"מחיר מחושב",
			)
		);

		if ($create_option)
		{
			array_push($table_rows[0],
				"קטגוריה");

		} else	{
			array_push($table_rows[0],
				"קטגוריות",
				"שם מוצר",
				"מחיר מכירה",
				"מחיר מבצע",
				"מזהה",
				"מנוהל מלאי",
				"יתרה במלאי",
				"כמות בהזמנות פתוחות",
				"מחירים נוספים");
		}
		$col_ids = array(
			"id",
			"cod",
			"pli",
			"prn",
			"dat",
			"buy",
			"clp",
			"cat",
			"nam",
			"sal",
			"pid",
			"smn",
			"inv",
			"opn",
			"apr"
		);

		if ( ! $ordered_only and ! $need_supply_only ) {
			array_push( $table_rows[0], gui_label( "delete_row", "פעולה" ) );
		}

		$show_fields = array( true, true, true, true, true, true, true, true, true, true, true, true );
		// Add new item fields
		while ( $row = mysqli_fetch_row( $result ) ) {
			$pl_id     = $row[3];
			$link_data = $catalog->GetProdID( $pl_id );
			$prod_id   = "";
			$map_id    = "";
			if ( $link_data ) {
				$prod_id = $link_data[0];
				$p       = new Fresh_Product( $prod_id );
				$map_id  = null;

				if ( isset( $link_data[1] ) ) {
					$map_id = $link_data[1];
				}
				// print $prod_id . " " . $map_id . "<br/>";
				if ($create_option)
					continue; // Show only non linked products.

				if ( $ordered_only and ! isset( $needed_products[ $prod_id ][0] ) and ! isset( $needed_products[ $prod_id ][1] ) )
					continue;

				if ( $need_supply_only and ( $needed_products[ $prod_id ][0] <= $p->getStock() ) ) {
					continue;
				}
			} else {
				// print "nso=" . $need_supply_only . "<br/>";
				if ( $need_supply_only or $ordered_only ) {
					continue;
				}
			}
			// Line( $product_name, $price, $date, $pl_id, $supplier_product_code, $factor, $linked_prod_id, $editable = true, $args = null )
			$line = $this->Line( $row[0], $row[1], $row[2], $pl_id, $row[4], $row[5], $prod_id, true, $args );
			if ( ! $ordered_only and ! $need_supply_only ) {
				if ($create_option)
					array_push( $line, Core_Html::GuiButton( "cre_" . $pl_id, "create_product(" . $pl_id . ")", "צור מוצר", true ) );
				else
					array_push( $line, Core_Html::GuiButton( "del_" . $pl_id, "del_line(" . $pl_id . ")", "מחק" ) );
			}
			array_unshift( $line, $pl_id );

			array_push( $table_rows, $line );
			// $data .= $line;
		}

		$sum = null;
		$data = gui_table( $table_rows, "pricelist", true, true, $sum, null, null, $show_fields, null, $col_ids, true );
		//( $rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null, $show_fields = null,
		// $links = null)
		// $data .= "<tr>";
		$data .= "</tr>";

		$data .= gui_table_args( array(
			array( 'מק"ט - אופציונאלי', gui_input( "product_code", "" ) ),
			array( "שם מוצר", gui_input( "product_name", "" ) ),
			array( "מחיר", gui_input( "price", "" ) )
		) );
		$data .= "<td>" . Core_Html::GuiButton( "add", "add_item()", "הוסף" ) . "</td>";

		print $data;
	}

	private function Line( $product_name, $price, $date, $pl_id, $supplier_product_code, $factor, $linked_prod_id, $editable = true, $args = null ) {
		$create_option = false;
		if (isset($args) and isset($args["create_products"])){
			$create_option = true;
		}

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
		if ($create_option){
			array_push($line, gui_select_category($pl_id, false));

		} else
			if ( $linked_prod_id > 0 ) {
				$p = new Fresh_Product( $linked_prod_id );
				array_push( $line, get_product_name( $linked_prod_id ) );
				array_push( $line, get_price( $linked_prod_id ) );
				array_push( $line, get_sale_price( $linked_prod_id ) );
				array_push( $line, $linked_prod_id );
				$stockManaged = $p->getStockManaged();
				array_push( $line, gui_checkbox( "chm_" . $linked_prod_id, "stock", $stockManaged, "onchange=\"change_managed(this)\")" ) );
				array_push( $line, gui_label( "stk_" . $linked_prod_id, Core_Html::GuiHyperlink( $p->getStock(), "../orders/get-orders-per-item.php?prod_id=" . $linked_prod_id ) ) );
				$n = orders_per_item( $linked_prod_id, 1, true, true, true );
//			if (isset($needed[$linked_prod_id][0]))
//				$n .= $needed[$linked_prod_id][0];
//			if (isset($needed[$linked_prod_id][1]))
//				$n .= $needed[$linked_prod_id][1] . "יח";

				array_push( $line, gui_label( "ord_" . $linked_prod_id, $n ) );
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
		$picture_path = null, $debug = false
	) {

//		print "code: " . $code . "<br/>";
//		print "start";
//		print "AddOrUpdate: " . $product_name . " " . $regular_price . "<br/>";
		MyLog( __METHOD__, __FILE__ );
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
		// Change if line exits.
		$sql = "select id, price " .
		       " from im_supplier_price_list " .
		       " where supplier_id = " . $this->SupplierID .
		       " and product_name = '" . $product_name . "'";

		$date = date( 'y/m/d' );

		$id     = 0;
		$result = sql_query( $sql );

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
				$sql .= ", picture_path = '" . escape_string( $picture_path ) . "'";
			} else {
				$sql .= ", picture_path = null";
			}
			$sql .= " where product_name = '" . $product_name . "' and supplier_id = " . $this->SupplierID;

			//  print "<br/>"  . $product_name . "<br/>";
			// print "<p dir='ltr'>"  . $sql . "</p>";

			$result = sql_query( $sql );
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
			if ( $code === "" ) {
				print "setting default<br/>";
				$code = "10";
			}

			print "code: " . $code . "<br/>";
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
				$values .= ", '" . escape_string( $picture_path ) . "'";
			}
			// Complete the sql statement
			$sql .= ") " . $values . ")";

			// print "<p dir=ltr>" . $sql . "</p>";

			$result = sql_query( $sql );
			// Output
			$id = sql_insert_id( );
			$rc = UpdateResult::NewPrice;
		}
		// Update linked products
		$this->Update( $id, $regular_price, $sale_price );

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
		MyLog( __METHOD__, "update line $id, price $price, sale price $sale_price" );
		$sql = "UPDATE im_supplier_price_list SET price = " . $price .
		       ", sale_price = " . $sale_price .
		       ", date = '" . date( 'y/m/d' ) . "' " .
		       " WHERE id = " . $id;
		sql_query( $sql );

		$this->UpdateCatalog( $id );

		return;
	}

	static function UpdateCatalog( $pricelist_id, $debug = false ) {
//		print "pricelist_id: $pricelist_id<br/>";
		$prod_ids = Catalog::GetProdID( $pricelist_id );
//		print "prod_ids:"; var_dump($prod_ids); print "<br/>";
		$line     = "";
		if ( $debug ) {
			print "update";
		}
		if ( $prod_ids ) {
			foreach ( $prod_ids as $prod_id ) {
				if ( $debug ) {
					print $prod_id . " ";
				}
				MyLog( __METHOD__, "update product $prod_id" );
				Catalog::UpdateProduct( $prod_id, $line );
				MyLog( $line );
			}
		}
	}

	function GetByName( $product_name )
	{
		$product_name = Fresh_Pricelist::StripProductName( $product_name );

		$sql = "SELECT price FROM im_supplier_price_list "
		       . " WHERE product_name = '" . addslashes( $product_name ) . "' AND supplier_id = " . $this->SupplierID;

		return sql_query_single_scalar( $sql );
	}

//    function DraftRemoved()
//    {
//
//        $sql = "select id from ihstore.im_supplier_price_list where " .
//            " supplier_id = " . $this->SupplierID . " and line_status = 2";
//        $result = mysqli_result($sql);
//
//        if (!$result){
//            handle_sql_error($sql);
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
////        $result = mysqli_query(, $sql);
////        if (! $result) die ($sql . mysqli_error());
////
////        while ($row = mysqli_fetch_row($result))
////        {
////            print $row[0];
////        }
//    }

	function ChangeStatus( $status ) {
		// Act local
		sql_query("UPDATE im_supplier_price_list SET line_status = " . $status . " WHERE supplier_id = " . $this->SupplierID);
	}

//    function ExecuteRemotes($url)
//    {
//        // print "ExecuteRemotes: " . $url . "<br/>";
//        // my_log("Execute " . $url);
//
//        $sql = "select remote_site_id, remote_supplier_id from im_multisite_pricelist " .
//            " where supplier_id = " . $this->SupplierID;
//
//        my_log($sql);
//
//        $result = mysqli_query($sql);
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

		$items_to_remove = sql_query_array("select id, price, product_name " .
		                                   " from im_supplier_price_list " .
		                                   " where line_status = " . $status .
		                                   " and supplier_id = " . $this->SupplierID);

		foreach ($items_to_remove as $row) {
			$id = $row[0];
			print "removing " . $id . "<br/>";
			MyLog( "Remove " . $id );
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
		MyLog( __METHOD__ . $pricelist_id );

		// Check if this product linked.
		$prod_info = catalog::GetProdID( $pricelist_id );
		if ( $prod_info ) {
			$prod_id = $prod_info[0];
		} else {
			$prod_id = 0;
		}
		MyLog( "Delete $pricelist_id $prod_id" );

		MyLog( "Delete. id = " . $pricelist_id );
		MyLog( "catalog_delete_price", "pricelist-post.php" );
		$sql = "DELETE FROM im_supplier_price_list  "
		       . " WHERE id = " . $pricelist_id;

		sql_query( $sql );
		// The mapping stays - in case supplier gets it back.

		// If no other option for this product - make it draft
		if ( $prod_id > 0 ) {
			$line = "";
			Catalog::UpdateProduct( $prod_id, $line );
		}
	}

	static function StripProductName( $name ) {
		// trim sadot product name starting with * or **
		$name = str_replace( array( '.', ',', '*', '\'' ), '', $name );
		$name = str_replace( array( ')', '(', '-' ), ' ', $name );

		return $name;
	}
}

