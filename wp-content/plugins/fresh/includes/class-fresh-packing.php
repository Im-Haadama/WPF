<?php


class Fresh_Packing {

	static function add_admin($menu)
	{
		$menu->AddMenu('Fresh Packing', 'Packing', 'show_manager', 'packing', 'Fresh_Packing::admin');
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
		$args["tabs_load_all"] = true;
		$totals = self::needed_totals(false);
		if (! is_array($totals)){
			$result .= $totals;
		} else {
			$args["selected_tab"] = array_key_first( $totals );
			$result               .= Core_Html::GuiTabs( $totals, $args );
		}

		print $result;
	}

	static function get_total_orders_supplier( $supplier_id, $needed_products, $filter_zero, $filter_stock, $history )
	{
		$result = "";
		$inventory_managed = InfoGet( "inventory" );
		$supplier = new Fresh_Supplier($supplier_id);

		$data_lines = array();

		foreach ( $needed_products as $prod_id => $quantity_array ) {
			$P = new Fresh_Product( $prod_id );
			if ( ! $P ) continue;

			$row = array();

			if ( $filter_stock and $P->getStockManaged() and $P->getStock() > $quantity_array[0] ) {
				continue;
			}

			if ($P->isDraft()){
				$row[] = "טיוטא";
			} else {
				$row[] = gui_checkbox("chk" . $prod_id, "product_checkbox". $supplier_id);
			}
			$row[] = $P->getName();
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
				$row[] = Core_Html::GuiInput( "inv_" . $prod_id, $q_inv, array(
					"onchange=\"change_inv(" . $prod_id . ")\"",
					"onkeyup=\"moveNext(" . $prod_id . ")\""
				) ) ;

				$numeric_quantity = ceil( $quantity - $q_inv );

				$row[] = Core_Html::GuiInput( "qua_" . $prod_id, $numeric_quantity,
					"onchange=\"line_selected('" . $prod_id . "')\"" );
			}

			$row [] = self::orders_per_item( $prod_id, 1, true, true, true );

			if ( ! $filter_zero or ( $numeric_quantity > 0 ) ) {
				array_push( $data_lines, array( $p->getName(), $row ) );
			}
		}

		if ( count( $data_lines ) ) {
			if ($supplier_id)
				$supplier_name = $supplier->getSupplierName();
			else $supplier_name = "מוצרים לא זמינים";

			$result .= Core_Html::gui_header( 2, $supplier_name );

			$header = array("בחר", "פריט", "כמות נדרשת");

			if ( $inventory_managed ) {
				array_push($header, "כמות במלאי");
				array_push($header, "כמות להזמין");
				array_push($header, "לקוחות");
			}
			$table_rows = array();

			array_push($table_rows, $header);

			sort( $data_lines );

			for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
				array_push($table_rows, $data_lines[ $i ][1]);
			}
			//array_push($table_rows, array( array( "", 'סה"כ', "", "", "", "", "", $total_buy, $total_sale )));

			$result .= Core_Html::gui_table_args($table_rows, "needed_" . $supplier_id);

			if (! $supplier_id) {
				$result .= "יש להפוך לטיוטא רק לאחר שמוצר אזל מהמלאי והוצע ללקוחות תחליף<br/>";
				$result .= Core_Html::GuiButton("btn_draft_products", "draft_products()", "הפוך לטיוטא");
			}
		}
		return $result;
	}

	static function needed_totals( $filter_zero, $history = false, $filter_stock = false, $limit_to_supplier_id = null )
	{
		$result = "";
		$needed_products = array();

		Fresh_Order::CalculateNeeded( $needed_products );

		if (! count ($needed_products))
		{
			$result .= __("No needed products. Any orders in processing status?");
			return $result;
		}

		$supplier_tabs = [];
		$suppliers       = array();
		$supplier_needed = array();

		// Find out which suppliers are relevant
		foreach ( $needed_products as $prod_id => $product_info ) {
			$prod = new Fresh_Product($prod_id);
			$supplier_id = $prod->getSupplierId();
			if ( ! in_array( $supplier_id, $suppliers ) ) {
				array_push( $suppliers, $supplier_id );
				$supplier_needed[ $supplier_id ] = array();
			}
			$supplier_needed[ $supplier_id ][ $prod_id ] = $product_info;
			if (! $supplier_id){
				if (! isset($supplier_needed["missing"]))
					$supplier_needed["missing"] = array();

				$supplier_needed["missing"][$prod_id] = $product_info;
			}
		}

		if ($limit_to_supplier_id) {
			if (! isset($supplier_needed[ $limit_to_supplier_id ]))
			{
				print "אין מוצרים רלוונטים לספק " . get_supplier_name($limit_to_supplier_id);
				return;
			}
			print self::get_total_orders_supplier( $limit_to_supplier_id, $supplier_needed[ $supplier_id ], $filter_zero, $filter_stock, $history );

			print Core_Html::GuiButton( "btn_create_supply_" . $supplier_id, "createSupply(" . $supplier_id . ")", "צור אספקה" );

			return;
		}

		$sql = "SELECT id, supplier_priority FROM im_suppliers WHERE id IN (" . CommaImplode( $suppliers ) . ")" .
		       " ORDER BY 2";

		$result = sql_query( $sql );

		while ( $row = sql_fetch_row( $result ) ) {
			$supplier = new Fresh_Supplier($row[0]);
			$tab_content =
				self::get_total_orders_supplier( $supplier->getId(), $supplier_needed[ $supplier->getId() ], $filter_zero, $filter_stock, $history );

			if ($supply_id = Fresh_Suppliers::TodaySupply($supplier->getId()))
				$tab_content .= Core_Html::GuiHyperlink("Supply " . $supply_id, "get") . "<br/>";

//

			$tab_content .= Core_Html::GuiButton( "btn_create_supply_" . $supplier->getId(), "Create a supply", array("action" => "needed_create_supplies(" . $supplier->getId() . ")") );

			$supplier_tabs[$supplier->getId()] =
				array($supplier->getId(),
					$supplier->getSupplierName(), $tab_content);

		}
		return $supplier_tabs;
	}

	static function init_hooks()
	{
	}

	static function orders_per_item( $prod_id, $multiply, $short = false, $include_basket = false, $include_bundle = false, $just_total = false, $month = null ) {
	// my_log( "prod_id=" . $prod_id, __METHOD__ );

	$sql = 'select woi.order_item_id, order_id'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
	       . ' where order_id in';

	if ( ! $month )
		$sql .= '(select order_id from im_need_orders) ';
	else {
		$year = date( 'Y' );
		if ( $month >= date( 'n' ) ) {
			$year --;
		}
		$sql .= "(SELECT id FROM wp_posts WHERE post_date like '" . $year . "-" . sprintf( "%02s", $month ) . "-%'" .
		        " and post_status = 'wc-completed')";
//		print $sql;
//		die (1);
	}

	$baskets = null;
	if ( $include_basket ) {
		$sql1    = "select basket_id from im_baskets where product_id = $prod_id";
		$baskets = sql_query_array_scalar( $sql1 );
	}
	$bundles = null;
	if ( $include_bundle ) {
		$sql2    = "select bundle_prod_id from im_bundles where prod_id = " . $prod_id;
		$bundles = sql_query_array_scalar( $sql2 );
		// if ($bundles) var_dump($bundles);
	}
	$sql .= ' and woi.order_item_id = woim.order_item_id '
	        . ' and (woim.meta_key = \'_product_id\' or woim.meta_key = \'_variation_id\')
	         and woim.meta_value in (' . $prod_id;
	if ( $baskets ) {
		$sql .= ", " . CommaImplode( $baskets );
	}
	if ( $bundles ) {
		$sql .= ", " . CommaImplode( $bundles );
	}
	$sql .= ")";

//	print $sql . "<br/>";

	// my_log( $sql, "get-orders-per-item.php" );

	$result = sql_query( $sql);
	$lines = "";
	$total_quantity = 0;

	while ( $row = mysqli_fetch_row( $result ) ) {
		$order_item_id = $row[0];
		$order_id      = $row[1];
		$quantity      = self::get_order_itemmeta( $order_item_id, '_qty' );
		// consider quantity in the basket or bundle
		$pid = self::get_order_itemmeta( $order_item_id, '_product_id' );
		$p = new Fresh_Product($pid);
		if ( $p->is_bundle( ) ) {
			$b        = Fresh_Bundle::CreateFromBundleProd( $pid );
			$quantity *= $b->GetQuantity();
		} else
			if ( $p->is_basket( ) ) {
				$b        = new Fresh_Basket( $pid );
				$quantity *= $b->GetQuantity( $prod_id );
			}
		$first_name    = get_postmeta_field( $order_id, '_shipping_first_name' );
		$last_name     = get_postmeta_field( $order_id, '_shipping_last_name' );

		$total_quantity += $quantity;

		if ( $short ) {
//			print "short $first_name<br/>";
			$lines .= $quantity . " " . $last_name . ", ";
		} else {
//			print "long<br/>";
			$line  = "<tr>" . "<td> " . Core_Html::GuiHyperlink( $order_id, "get-order.php?order_id=" . $order_id ) . "</td>";
			$line .= "<td>" . $quantity * $multiply . "</td><td>" . $first_name . "</td><td>" . $last_name . "</td></tr>";
			$lines .= $line;
		}
	}
	if ( $just_total ) {
		return $total_quantity;
	}
	if ( $short and $total_quantity ) {
		$lines = $total_quantity . ": " . rtrim( $lines, ", ");
	}
	return $lines;
}

	static function get_order_itemmeta( $order_item_id, $meta_key ) {
		if ( is_array( $order_item_id ) ) {
			$sql = "SELECT sum(meta_value) FROM wp_woocommerce_order_itemmeta "
			       . ' WHERE order_item_id IN ( ' . comma_implode( $order_item_id ) . ") "
			       . ' AND meta_key = \'' . escape_string( $meta_key ) . '\'';

			return sql_query_single_scalar( $sql );
		}
		if ( is_numeric( $order_item_id ) ) {
			$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
			        . ' WHERE order_item_id = ' . $order_item_id
			        . ' AND meta_key = \'' . escape_string( $meta_key ) . '\''
			        . ' ';

			return sql_query_single_scalar( $sql2 );
		}

		return - 1;
	}

}


