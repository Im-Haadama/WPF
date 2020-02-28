<?php


class Fresh_Packing {

	static function add_admin($menu)
	{
		$menu->AddMenu('Packing', 'Packing', 'show_manager', 'packing', 'Fresh_Packing::admin');
		$menu->AddSubMenu('packing', 'edit_shop_orders',
			array(array('page_title' => 'Needed products',
			            'menu_title' => 'Needed Products',
			            'menu_slug' => 'needed_products',
			            'function' => __CLASS__ . '::needed_products')
			));
	}

	static function admin()
	{
		$result = Core_Html::gui_header(1, "Packing");
		$url = AddToUrl(array("tab" => "needed", "page" => "packing"));
		$tabs = [];
		$args = [];
		$args["post_file"] = self::getPost();

		$tab = GetParam("tab", false, "needed");

		$tabs["needed"] = array(
			"needed",
			AddParamToUrl(GetUrl(1), array("page" => "settings","tab" => "needed")),
			self::NeededProducts()
		);

//		$tabs["missing_pictures"] = array(
//			"missing_pictures",
//			AddToUrl(array("page" => "settings","tab" => "missing_pictures")),
//			Fresh_Catalog::missing_pictures()
//		);

//		array_push( $tabs, array(
//			"workers",
//			"Workers",
//			self::company_workers( $company, $args )
//		) );

		$args["btn_class"] = "nav-tab";
		$args["tabs_load_all"] = true;
		$args["nav_tab_wrapper"] = "nav-tab-wrapper woo-nav-tab-wrapper";

		$result .= Core_Html::NavTabs($tabs, $args);
		$result .= $tabs[$tab][2];

		print $result;

	}

	static function needed_products()
	{
		$result = "";

		$result .= Core_Html::gui_header(1, "Needed products");
		$result .= Core_Html::gui_header(1, "הערות לקוח");
		$result .= Fresh_Order::GetAllComments();
		$result .= self::needed_totals(false);


		print $result;
	}

	static function get_total_orders_supplier( $supplier_id, $needed_products, $filter_zero, $filter_stock, $history )
	{
		$result = "";
		$inventory_managed = info_get( "inventory" );

		$data_lines = array();

		foreach ( $needed_products as $prod_id => $quantity_array ) {
			$P = new Fresh_Product( $prod_id );
			if ( ! $P ) {
				continue;
			}

			$row = array();

			if ( $filter_stock and $P->getStockManaged() and $P->getStock() > $quantity_array[0] ) {
				continue;
			}

			if ($P->isDraft()){
				$row[] = "טיוטא";
			} else {
				$row[] = gui_checkbox("chk" . $prod_id. '_' . $supplier_id, "product_checkbox". $supplier_id);
			}
			$row[] = get_product_name($prod_id);
			$row[] = Core_Html::GuiHyperlink(isset( $quantity_array[0] ) ? round( $quantity_array[0], 1 ) : 0,
				"get-orders-per-item.php?prod_id=" . $prod_id . ($history ? "&history" : ""));

			// Units. disabbled for now.
			//		if ( isset( $quantity_array[1] ) ) {
//			$line .= "<td>" . $quantity_array[1] . "</td>";
//		} else {
//			$line .= "<td></td>";
//		}
			$quantity = isset( $quantity_array[0] ) ? $quantity_array[0] : 0;

			$p     = new Fresh_Product( $prod_id );
			$q_inv = $p->getStock();

			if ( $inventory_managed ) {
				$row[] = gui_input( "inv_" . $prod_id, $q_inv, array(
					"onchange=\"change_inv(" . $prod_id . ")\"",
					"onkeyup=\"moveNext(" . $prod_id . ")\""
				) ) ;

				$numeric_quantity = ceil( $quantity - $q_inv );

				$row[] = gui_input( "qua_" . $prod_id, $numeric_quantity,
					"onchange=\"line_selected('" . $prod_id . '_' . $supplier_id . "')\"" );
			}

			$alternatives  = alternatives( $prod_id );
			$suppliers     = array( array( "id" => 0, "option" => "בחר" ) );
			foreach ( $alternatives as $alter ) {
				$option = $alter->getSupplierName() . " " . $alter->getPrice();

				array_push( $suppliers, array( "id" => $alter->getSupplierId(), "option" => $option ) );
			}

			// if ($prod_id == 1002) {print "XX"; var_dump($suppliers); }
			$supplier_name = gui_select( "sup_" . $prod_id, "option", $suppliers, "onchange=selectSupplier(this)", "" );

			$row[] = $supplier_name;

			$row [] = orders_per_item( $prod_id, 1, true, true, true );

			//print "loop5: " .  microtime() . "<br/>";
			if ( ! $filter_zero or ( $numeric_quantity > 0 ) ) {
				array_push( $data_lines, array( get_product_name( $prod_id ), $row ) );
			}
		}

		if ( count( $data_lines ) ) {
			if ($supplier_id)
				$supplier_name = get_supplier_name( $supplier_id );
			else $supplier_name = "מוצרים לא זמינים";

			$result .= Core_Html::gui_header( 2, $supplier_name );

			$header = array("בחר", "פריט", "כמות נדרשת");

			if ( $inventory_managed ) {
				array_push($header, "כמות במלאי");
				array_push($header, "כמות להזמיןי");
				array_push($header, "ספק");
				array_push($header, "לקוחות");
			}
			$table_rows = array();

			array_push($table_rows, $header);

			sort( $data_lines );

			global $total_buy;
			global $total_sale;

			for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
				array_push($table_rows, $data_lines[ $i ][1]);
			}
			array_push($table_rows, array( array( "", 'סה"כ', "", "", "", "", "", $total_buy, $total_sale )));

			// $result .= gui_table_args(  $table_rows );
			// debug_var($table_rows);
			$result .= gui_table_args($table_rows, "needed_" . $supplier_id);

			if (! $supplier_id) {
				$result .= "יש להפוך לטיוטא רק לאחר שמוצר אזל מהמלאי והוצע ללקוחות תחליף<br/>";
				$result .= Core_Html::GuiButton("btn_draft_products", "draft_products()", "הפוך לטיוטא");
			}
		}
		return $result;
	}

	static function needed_totals( $filter_zero, $history = false, $filter_stock = false, $supplier_id = null )
	{
		$result = "";
		$needed_products = array();

		Fresh_Order::CalculateNeeded( $needed_products );

		if (! count ($needed_products))
		{
			$result .= __("No needed products. Any orders in processing status?");
			return $result;
		}

		$suppliers       = array();
		$supplier_needed = array();

		// Find out which suppliers are relevant
		foreach ( $needed_products as $prod_id => $product_info ) {
			$found_supplier = false;
//			foreach ( alternatives( $prod_id ) as $alter ) {
			$prod = new Fresh_Product($prod_id);
			$supplier = $prod->getSupplierName();
			if ( ! in_array( $supplier, $suppliers ) ) {
				array_push( $suppliers, $supplier );
				$supplier_needed[ $supplier ] = array();
			}
			$supplier_needed[ $supplier ][ $prod_id ] = $product_info;
			$found_supplier = true;
//			}
			if (! $found_supplier){
				if (! isset($supplier_needed["missing"]))
					$supplier_needed["missing"] = array();

				$supplier_needed["missing"][$prod_id] = $product_info;
			}
		}

		if ($supplier_id) {
			if (! isset($supplier_needed[ $supplier_id ]))
			{
				print "אין מוצרים רלוונטים לספק " . get_supplier_name($supplier_id);
				return;
			}
			print get_total_orders_supplier( $supplier_id, $supplier_needed[ $supplier_id ], $filter_zero, $filter_stock, $history );

			print Core_Html::GuiButton( "btn_supplier_" . $supplier_id, "createSupply(" . $supplier_id . ")", "צור אספקה" );

			return;
		}

//		if ($supplier_needed["missing"]) {
////		var_dump($supplier_needed["missing"]);
//			print get_total_orders_supplier( $supplier_id, $supplier_needed["missing" ], $filter_zero, $filter_stock, $history );
//		}
		print "handle missing";
		$suppliers_ids = [];
		foreach ($suppliers as $supplier_name)
		{
			$suppliers_ids[] = sql_query_single_scalar("select id from im_suppliers where supplier_name = '" . $supplier_name . "'");
		}
		$sql = "SELECT id, supplier_priority FROM im_suppliers WHERE id IN (" . CommaImplode( $suppliers_ids ) . ")" .
//		       " AND active " .
		       " ORDER BY 2";

		$result = sql_query( $sql );

		while ( $row = sql_fetch_row( $result ) ) {
			$supplier_id = $row[0];
			print self::get_total_orders_supplier( $supplier_id, $supplier_needed[ $supplier_id ], $filter_zero, $filter_stock, $history );

			print Core_Html::GuiButton( "btn_supplier_" . $supplier_id, "createSupply(" . $supplier_id . ")", "צור אספקה" );
		}
	}

}