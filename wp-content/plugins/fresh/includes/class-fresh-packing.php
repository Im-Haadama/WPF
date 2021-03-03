<?php

class Fresh_Packing {
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self("Fresh");
		}
		return self::$_instance;
	}

	static function needed_products() {
		$result = "";

		$filter_in_stock = GetParam("stock_filter", false, 0);
		$result                .= Core_Html::GuiHeader( 1, "Needed products" );
		if ($filter_in_stock)
			$result .= Core_Html::GuiHyperlink("unfiltered", AddToUrl("stock_filter", 0));
		else
			$result .= Core_Html::GuiHyperlink("filter in-stock", AddToUrl("stock_filter", 1));
		$result                .= Fresh_Order::GetAllComments();
		$args["tabs_load_all"] = true;
		$result                 .=  self::NeededProducts(false, false, $filter_in_stock );

		print $result;
	}

	function supplier_tabs($needed_prod_by_supplier)
	{
		$supplier_tabs   = [];
	}

	static function get_total_orders_supplier( $supplier_id, $needed_products, $filter_zero = false, $filter_stock = false, $history = false, $debug = false)
	{
		if ($debug) print __FUNCTION__ . $supplier_id . "<br/>";

		$post_file = Fresh::getPost();
		$result            = "";
		// InfoUpdate("inventory", 1);
		$inventory_managed = InfoGet( "inventory" );
		if ($supplier_id) $supplier          = new Fresh_Supplier( $supplier_id );
		else		$supplier = null;

		$checkbox_class = "product_checkbox" . $supplier_id;

		$data_lines = array();

		foreach ( $needed_products as $prod_id => $quantity_array ) {
			if ($debug) print "checking $prod_id<br/>";
			$P = new Fresh_Product( $prod_id );
			if ( ! $P ) {
				if ($debug) print "product $prod_id not found<br/>";
				continue;
			}

			$row = array();

//			if ( $filter_stock and $P->getStockManaged() and $P->getStock() > $quantity_array[0] ) continue;
			if ( $filter_stock and ($P->getStock() >= $quantity_array[0] )) continue;

			if ( $P->isDraft() ) $row[] = "טיוטא";
			else $row[] = Core_Html::GuiCheckbox( "chk" . $prod_id, 0, array("checkbox_class" => $checkbox_class ));
			$row[] = $P->getName();
//			$row[] = Core_Html::GuiHyperlink( isset( $quantity_array[0] ) ? round( $quantity_array[0], 1 ) : 0,
//				"get-orders-per-item.php?prod_id=" . $prod_id . ( $history ? "&history" : "" ) );
//			$row[] = (isset( $quantity_array[0] ) ? round( $quantity_array[0], 1 ) : 0);

			// Units. disabbled for now.
			//		if ( isset( $quantity_array[1] ) ) {
//			$line .= "<td>" . $quantity_array[1] . "</td>";
//		} else {
//			$line .= "<td></td>";
//		}
			$quantity = isset( $quantity_array[0] ) ? $quantity_array[0] : 0;
			$row[] = $quantity;

			$p     = new Fresh_Product( $prod_id );
			if ( $inventory_managed ) {
				$q_inv = $p->getStock();
				$row[] = Core_Html::GuiInput( "inv_" . $prod_id, $q_inv, array("events" => "onchange=\"inventory_change('$post_file', " . $prod_id . ")\" onkeyup=\"moveNext('$post_file'," . $prod_id . ")\""));

				$numeric_quantity = ceil( $quantity - $q_inv );

				$row[] = Core_Html::GuiInput( "qua_" . $prod_id, $numeric_quantity,
					"onchange=\"line_selected('" . $prod_id . "')\"" );
			} else {
				$row[] = Core_Html::GuiInput( "qua_" . $prod_id, $quantity,
					"onchange=\"line_selected('" . $prod_id . "')\"" );

			}

			$row [] = self::orders_per_item( $prod_id, 1, true, ! $p->is_basket(), true );

			if ($debug) var_dump($row);

			if ( ! $filter_zero or ( $numeric_quantity > 0 ) ) {
				if ($debug) print "Adding to data lines<br/>";
				array_push( $data_lines, array( $p->getName(), $row ) );
			}
		}

		if ( count( $data_lines ) >= 1) {
			if ( $supplier_id ) {
				$supplier_name = $supplier->getSupplierName();
			} else {
				$supplier_name = "מוצרים ללא ספק";
			}

			$result .= Core_Html::GuiHeader( 2, $supplier_name );

			$header = array(
				Core_Html::GuiCheckbox( "chk_toggle",
					"",
					array( "events" => 'onchange="select_all_toggle(this, \'' . $checkbox_class . '\')"' ) ),
				"פריט",
				"כמות נדרשת"
			);

			if ( $inventory_managed ) {
				array_push( $header, "כמות במלאי" );
				array_push( $header, "כמות להזמין" );
				array_push( $header, "לקוחות" );
			}
			$table_rows = array();

			array_push( $table_rows, $header );

			sort( $data_lines );

			for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
				array_push( $table_rows, $data_lines[ $i ][1] );
			}
			//array_push($table_rows, array( array( "", 'סה"כ', "", "", "", "", "", $total_buy, $total_sale )));
//			$args = [];
//			$post_file = Fresh::getPost();
//			$args["events"] = array(3 => 'onchange="inventory_update(\'' . $post_file . '\')');
			$args["checkbox_class"] = $checkbox_class;
//			var_dump($table_rows[1]); print "<br/>";

			 $result .= Core_Html::gui_table_args( $table_rows, "needed_" . $supplier_id, $args);

			if ( ! $supplier_id ) {
				$result .= "יש להפוך לטיוטא רק לאחר שמוצר אזל מהמלאי והוצע ללקוחות תחליף<br/>";
				$result .= Core_Html::GuiButton( "btn_draft_products", "הפוך לטיוטא", array("action" => "draft_products()"));
			}
		}
		if ($debug) var_dump($result);

		return $result;
	}

	static function NeededProducts( $filter_zero = false, $history = false, $filter_stock = false, $limit_to_supplier_id = null )
	{
		$debug_product = null; $debug_supplier = null;

		$result          = "";
		if ($filter_stock) $result .= Core_Html::GuiHeader(2, "Stock filtered");
		if ($debug_product) {
			$p = new Fresh_Product($debug_product);
			$result .= "Debugging product " . $p->getName();
		}
		$needed_products = array();
		$supplier_tabs = array();

		$user_table = null;
		Fresh_Order::CalculateNeeded( $needed_products, 0, $user_table, $debug_product );

		if ( ! count( $needed_products ) ) {
			$result .= __( "No needed products. Any orders in processing status?" );

			return $result;
		}

		$suppliers       = array();
		$supplier_needed = array();

		// Find out which suppliers are relevant
		foreach ( $needed_products as $prod_id => $product_info ) {
			$prod        = new Fresh_Product( $prod_id );
			$supplier_id = $prod->getSupplierId();

			if ( ! in_array( $supplier_id, $suppliers ) and $supplier_id ) {
				array_push( $suppliers, $supplier_id );
				$supplier_needed[ $supplier_id ] = array();
			}
			$supplier_needed[ $supplier_id ][ $prod_id ] = $product_info;
			if ( ! $supplier_id ) {
				if ( ! isset( $supplier_needed["missing"] ) ) {
					$supplier_needed["missing"] = array();
				}

				$supplier_needed["missing"][ $prod_id ] = $product_info;
			}
			if ($debug_product == $prod_id) {
				$result         .= "(debug) needed. supplier $supplier_id $product_info[0]<br/>";
				$debug_supplier = $supplier_id;
			}
		}

		if ( $limit_to_supplier_id ) {
			if ( ! isset( $supplier_needed[ $limit_to_supplier_id ] ) ) {
				print "אין מוצרים רלוונטים לספק " . get_supplier_name( $limit_to_supplier_id );

				return null;
			}

			$result .= self::get_total_orders_supplier( $limit_to_supplier_id, $supplier_needed[ $limit_to_supplier_id ], $filter_zero, $filter_stock, $history ) .
			       Core_Html::GuiButton( "btn_create_supply_" . $supplier_id, "createSupply(" . $supplier_id . ")", "צור אספקה" );
		} else {
			if (! $supplier_needed) return "Nothing needed";

			if (strlen(CommaImplode($suppliers))) {
				$sql = "SELECT id, supplier_priority FROM im_suppliers WHERE id IN (" . CommaImplode( $suppliers ) . ")" .
				       " ORDER BY 2";

				$row_result = SqlQueryArray( $sql );

				foreach ( $row_result as $row ) {
					$supplier_id = $row[0]; // Or null for missing supplier
					if ( $supplier_id ) {
						if ($supplier_id == $debug_supplier) {
							$result .= "Checking debug supplier $debug_supplier<br/>";
						}
						$supplier = new Fresh_Supplier( $row[0] );
					} else {
						$supplier = null;
					}

					$tab_content =
						self::get_total_orders_supplier( $supplier->getId(), $supplier_needed[ $supplier->getId() ], $filter_zero, $filter_stock, $history, $supplier_id == $debug_supplier );

					if ($supplier->getId() == $debug_supplier) {
						$result .= "supplier tab: $tab_content<br/>";
					}
					if (! strlen ($tab_content)) continue;

					if ( $supply_id = Fresh_Suppliers::TodaySupply( $supplier->getId() ) ) {
						$tab_content .= Core_Html::GuiHyperlink( "Supply " . $supply_id, Fresh_Supply::getLink($supply_id ) ). "<br/>";
					}

					$tab_content .= Core_Html::GuiButton( "btn_create_supply_" . $supplier->getId(), "Create a supply", array( "action" => "needed_create_supplies('" . Fresh::getPost() . "', " .$supplier->getId() . ")" ) );

					$supplier_tabs[ $supplier->getId() ] =
						array(
							$supplier->getId(),
							$supplier->getSupplierName(),
							$tab_content
						);
				}
			}
		}

		array_push($supplier_tabs,
			array('missing',
				'Missing supplier info',
				self::get_total_orders_supplier(0, $supplier_needed["missing"])));

		$args["selected_tab"] = 0; // array_key_first( $totals );
		$args["tabs_load_all"] = true;
		$result               .= Core_Html::GuiTabs( "products", $supplier_tabs, $args );

		return $result;
	}

	function init_hooks($loader) {
		$loader->AddAction("mission_labels", $this);
		$loader->AddFilter("mission_actions", $this);
		$loader->AddAction("packing_download", $this);

		$loader->AddAction('inventory_save', $this);
	}

	static function inventory_save()
	{
		$data = GetParamArray("data");
		$prod_id = $data[0];
		$q = $data[1];

		$p = new Fresh_Product($prod_id);
		return $p->setStock($q);
	}

	static function orders_per_item( $prod_id, $multiply, $short = false, $include_basket = false, $include_bundle = false, $just_total = false, $month = null )
	{
		$debug = false; // ($prod_id == 288);
		$prod = new Fresh_Product($prod_id);
		$sql = 'select woi.order_item_id, order_id'
		       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
		       . ' where order_id in';

		if ( ! $month ) {
			$sql .= '(select order_id from im_need_orders) ';
		} else {
			$year = date( 'Y' );
			if ( $month >= date( 'n' ) ) {
				$year --;
			}
			$sql .= "(SELECT id FROM wp_posts WHERE post_date like '" . $year . "-" . sprintf( "%02s", $month ) . "-%'" .
			        " and post_status = 'wc-completed')";
		}


		$baskets = null;
		if ( $include_basket ) {
			$baskets = $prod->get_baskets();

//			$sql1    = "select basket_id from im_baskets where product_id = $prod_id";
//			$baskets = SqlQueryArrayScalar( $sql1 );
			if ($debug) var_dump($baskets);
		}
		$bundles = null;
		if ( $include_bundle ) {
			$sql2    = "select bundle_prod_id from im_bundles where prod_id = " . $prod_id;
			$bundles = SqlQueryArrayScalar( $sql2 );
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

		if ($debug) var_dump($sql);

		$result         = SqlQuery( $sql );
		$lines          = "";
		$total_quantity = 0;

		while ( $row = mysqli_fetch_row( $result ) ) {
			$order_item_id = $row[0];
			$order_id      = $row[1];
			if ($debug) var_dump ($order_id);
			$o = new Fresh_Order($order_id);
			$quantity      = Finance_Delivery::get_order_itemmeta( $order_item_id, '_qty' );

			// consider quantity in the basket or bundle
			$pid = Finance_Delivery::get_order_itemmeta( $order_item_id, '_product_id' );
			$p   = new Fresh_Product( $pid );
			if ( $p->is_bundle() ) {
				$b        = Fresh_Bundle::CreateFromBundleProd( $pid );
				$quantity *= $b->GetQuantity();
			}
			else if ( $p->is_basket() ) {
				$b        = new Fresh_Basket( $pid );
				if ($debug) var_dump($b->get_basket_content($prod_id));

				$quantity *= $b->GetQuantity( $prod_id );
			}
			$first_name = get_postmeta_field( $order_id, '_shipping_first_name' );
			$last_name  = get_postmeta_field( $order_id, '_shipping_last_name' );

			$total_quantity += $quantity;

			if ( $short ) {
					$lines .= $quantity . " " . $last_name . ", ";
			} else {
					$line  = "<tr>" . "<td> " . $o->getLink($order_id) . "</td>";
					$line  .= "<td>" . $quantity * $multiply . "</td><td>" . $first_name . "</td><td>" . $last_name . "</td></tr>";
					$lines .= $line;
			}
		}
		if ( $just_total ) {
			return $total_quantity;
		}
		if ( $short and $total_quantity ) {
			$lines = $total_quantity . ": " . rtrim( $lines, ", " );
		}

		return $lines;
	}

	static function set_order_itemmeta( $order_item_id, $meta_key, $meta_value ) {
		MyLog("update $order_item_id, $meta_key, $meta_value");
		$value = $meta_value;

		if ( is_array( $meta_value ) ) {
			$value = implode( ",", $meta_value );
		}

		if ( SqlQuerySingleScalar( "SELECT count(*) FROM wp_woocommerce_order_itemmeta " .
		                           " WHERE order_item_id = " . $order_item_id .
		                           " AND meta_key = '" . $meta_key . "'" ) >= 1
		) {
			$sql = "update wp_woocommerce_order_itemmeta " .
			       " set meta_value = '" . $value . "'" .
			       " where order_item_id = " . $order_item_id .
			       " and meta_key = '" . $meta_key . "'";
		} else {
			$sql = "INSERT INTO wp_woocommerce_order_itemmeta " .
			       " (order_item_id, meta_key, meta_value) " .
			       " VALUES (" . $order_item_id . ", '" . $meta_key . "', '" . $value . "')";
		}
		return SqlQuery( $sql );
	}

	static function OrdersToHandle() {
		$operation = GetParam("operation", false, null);
		if ($operation) self::handle_operation($operation);

		$result = Core_Html::GuiHeader( 1, "Orders to handle" );

		$pending = self::orders_table( array( "wc-pending", "wc-on-hold" ) );
		if ( strlen( $pending ) > 4 ) {
			$result .= $pending;
			$result .= Core_Html::GuiButton( "btn_start", "Start handle", array( "action" => "start_handle()" ) );
			$result .= Core_Html::GuiButton( "btn_cancel", "Cancel order", array( "action" => "cancel_order()" ) ) . "<br/>";
		}

		$result .= self::orders_table( "wc-processing" );

		$shipment = self::orders_table( "wc-awaiting-shipment" );

		if ( strlen( $shipment ) > 5 ) {
			$result .= $shipment;
			$result .= Core_Html::GuiButton( "btn_delivered", "delivered_table()", "Delivered" ) . "<br/>";
		}

		print $result;
	}

	static function handle_operation($operation)
	{
		switch ($operation){
			case "cancel_order":
			$id = GetParam("id",true);
			$o  = new Fresh_Order( $id );
			$o->setStatus( "wc-cancelled" );
			break;
		}
	}

	static function orders_table( $statuses = array( 'wc-processing' ), $build_path = true, $user_id = 0, $week = null ) {
		$order_header_fields = array(
			"בחר", // replaced in loop
			"סוג משלוח",
			"משימה",
			"מספר הזמנה",
			"שם המזמין",
			"עבור",
//			"סכום הזמנה",
//			"עלות מוצרים",
//			"מרווח",
//			"דמי משלוח",
			"ישוב",
			"אמצעי תשלום",
			"תעודת משלוח"
		);

		$show_fields = array();
		$empty_line  = array();
		for ( $i = 0; $i < Fresh_OrderFields::field_count; $i ++ ) {
			$empty_line[ $i ]  = "";
			$show_fields[ $i ] = true;
		}

		$status_names = wc_get_order_statuses();
		$all_tables   = "";
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}

		foreach ( $statuses as $status ) {
			$order_header_fields[0] = gui_checkbox( "select_all_" . $status, "table", 0, "onclick=\"select_all_toggle('select_all_" . $status . "', 'select_order_" . $status . "')\"" );
			$rows                   = array( $order_header_fields );
			$sql                    = 'SELECT posts.id'
			                          . ' FROM `wp_posts` posts'
			                          . " WHERE post_status = '" . $status . "'";

			if ( $week ) {
				$sql = "select order_id from im_delivery where FIRST_DAY_OF_WEEK(date) = '" . $week . "'";
			}

			if ( $user_id ) {
				$sql .= " and order_user(id) = " . $user_id;
			}
			$sql .= " order by 1";

			$order_ids = SqlQueryArrayScalar( $sql );

			// If no orders in this status, move on.
			if ( sizeof( $order_ids ) < 1 ) {
				continue;
			}

			$result = SqlQuery( $sql );
			if ( ! $result ) {
				continue;
			}

			$count = 0;

			while ( $row = mysqli_fetch_row( $result ) ) {
				$count ++;
				$order_id = $row[0];
				$order    = new Fresh_Order( $order_id );

				$customer_id = $order->getCustomerId( $order_id );
				$customer = new Fresh_Client($customer_id);

				$line            = $empty_line;
				$invoice_user_id = get_user_meta( $customer_id, 'invoice_id', 1 );

				if ( $invoice_user_id ) {
					$line [ Fresh_OrderFields::line_select ] = gui_checkbox( "chk_" . $order_id, "select_order_" . $status );
				} else {
					$line [ Fresh_OrderFields::line_select ] = Core_Html::GuiHyperlink( "לקוח חדש", "/fresh/account/new-customer.php?order_id=" . $order_id );
				}

				$line[ Fresh_OrderFields::type ] = $order->getShipping();

				// display order_id with link to display it.
				// 1) order ID with link to the order
				$mission_id = $order->getMission();

				$args                                = array();
				$args["events"]                      = "onchange=\"order_mission_changed('" . Fresh::getPost() . "', " . $order_id . ")\"";
				$line[ Fresh_OrderFields::mission ]  = Flavor_Mission::gui_select_mission( "mis_" . $order_id, $mission_id, $args );
				$line[ Fresh_OrderFields::order_id ] = Core_Html::GuiHyperlink( $order_id, get_site_url() . "/wp-admin/post.php?post=$order_id&action=edit");

				// 2) Customer name with link to his deliveries
				$line[ Fresh_OrderFields::customer ] = Core_Html::GuiHyperlink( $customer->getName(), get_site_url() .
				                                                                                                   "/fresh/account/get-customer-account.php?customer_id=" . $customer_id );


				$line[ Fresh_OrderFields::recipient ] = get_postmeta_field( $order_id, '_shipping_first_name' ) . ' ' .
				                                        get_postmeta_field( $order_id, '_shipping_last_name' );


				// 4) Delivery note
				$delivery_id = $order->getDeliveryId();

				if ( $delivery_id > 0 ) {
					$delivery                                 = new Fresh_Delivery( $delivery_id );
					$line[ Fresh_OrderFields::delivery_note ] = Core_Html::GuiHyperlink( $delivery_id, "/fresh/delivery/get-delivery.php?id=" . $delivery_id );
					if ( $delivery->isDraft() ) {
						$line [ Fresh_OrderFields::line_select ] = "טיוטא";
					}
				} else {
					if ( $order->getStatus() == 'wc-processing' ) {
						$line[ Fresh_OrderFields::delivery_note ] = Core_Html::GuiHyperlink( "צור", "/fresh/delivery/create-delivery.php?order_id=" . $order_id, "_blank" ) . " " .
						                                            Core_Html::GuiHyperlink( "בטל", GetUrl() . "&operation=cancel_order&id=" . $order_id );
						;
					}
				}
				$line[ Fresh_OrderFields::city ]         = $order->getOrderInfo( '_shipping_city' );
				$customer                                = new Fresh_Client( $customer_id );
				$line[ Fresh_OrderFields::payment_type ] = $customer->get_payment_method_name();
				array_push( $rows, $line );
			}
			$data       = Core_Html::GuiHeader( 2, $status_names[ $status ] );
			$data       .= Core_Html::gui_table_args( $rows, $status ); // , true, true, $sums, null, null, $show_fields );
			$all_tables .= $data;
		}

		return $all_tables;
	}

	static function table() {
		$category_pages = array();

		foreach (Fresh_Category::GetTopLevel() as $term) {
			$term = new Fresh_Category($term->term_id);
			$table = self::PackingTable($term);

			if ($table) {
				array_push($category_pages,
				array($term->getName(),
					$term->getName(),
				$table));
			}
		}
		array_push($category_pages,
				array("Comments",
					"comments",
					Fresh_Order::GetAllComments()));
		$args = array("selected_tab" => 0,
			"tabs_load_all" => true);
		print Core_Html::GuiTabs("packing", $category_pages, $args);
	}

	static function PackingTable(Fresh_Category $term, $args = null)
	{
		$download_url = !GetArg($args, "printing", false);

		$Table = new Core_Sparse_Table(array("name" => "סיכום הזמנות"), true);

		$sql  = "SELECT id, post_status FROM wp_posts " .
		        " WHERE (post_status LIKE '%wc-processing%' ) 
		         order by 1 asc"; // OR post_status = 'wc-awaiting-shipment'

		// Add cols of baskets so they appear first.
		$sql_result = SqlQuery( $sql );
		foreach (Fresh_Basket::getAll() as $basket_id) {
			$b = new Fresh_Basket($basket_id);
			if ($term->in($b->getTerms()))
				$Table->addColumn($basket_id, $b->getName());
		}

		while ( $row = mysqli_fetch_assoc( $sql_result ) ) {
			$order_id = $row['id'];
			$O = new Fresh_Order($order_id);
			$C = new Fresh_Client($O->getCustomerId());
			$Table->AddRow($order_id, $C->getName(), "/wp-admin/post.php?post=%d&action=edit");

			foreach ($O->getProducts() as $prod_id => $prod_q) {
				$p = new Fresh_Product($prod_id);
				if (! $term->in($p->getTerms())) continue;
				$Table->AddItem($order_id, $prod_id, $prod_q, $p->getName());
			}
		}
		$result = $Table->GetTable($args);
		if ($download_url) $result .= Core_Html::GuiHyperlink("download", Flavor::getPost("packing_download") . '&category=' . $term->getId());
		return $result;
	}

	function mission_actions($actions)
	{
		$actions['labels'] = Core_Html::GuiHyperlink("labels", Flavor::getPost() . '?operation=mission_labels&id=%d');

		return $actions;
	}

	function mission_labels()
	{
		$mission_id = GetParam("id");
		require FRESH_INCLUDES . "label.php";
	}

	function packing_download()
	{
		$term_id = GetParam("category", true);

		$term = new Fresh_Category($term_id);

		print Core_Html::HeaderText();
		$args = array("transpose"=>true, "print"=>true);

		print self::PackingTable($term,  $args);
		// print
	}
}
/*
 *
 * 	if ( ! current_user_can( "show_business_info" ) ) {
		$show_fields[ Fresh_OrderFields::total_order ] = false; // current_user_can("show_business_info");
		$show_fields[ Fresh_OrderFields::margin ]      = false;
	}
		$i = count( $order_ids ) - 1;
		$total_delivery_total  = 0;
		$total_order_total     = 0;
		$total_order_delivered = 0;
 		if ( $count > 0 ) {
			$sums = null;

			if ( current_user_can( "show_business_info" ) ) {
				$sums = array(
					"סה\"כ",
					'',
					'',
					'',
					'',
					'',
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					0
				);
			}
		}

			$order_total = 0;
			// 3) Order total
			if ( $show_fields[ Fresh_OrderFields::total_order ] ) {
				$order_total = $order->GetTotal();
				// get_postmeta_field( $order_id, '_order_total' );
				$line[ Fresh_OrderFields::total_order ] = $order_total;
				$total_order_total                += $order_total;
			}

				$line[ Fresh_OrderFields::percentage ]    = Core_Html::GuiHyperlink( "בטל", $_SERVER['PHP_SELF'] . "?operation=cancel_order&id=" . $order_id );

		$show_fields[ Fresh_OrderFields::good_costs ]  = false;
			if ( current_user_can( "show_business_info" ) ) {
				$line[ Fresh_OrderFields::good_costs ] = $order->GetBuyTotal();
				$line[ Fresh_OrderFields::margin ]     = round( ( $line[ Fresh_OrderFields::total_order ] - $line[ Fresh_OrderFields::good_costs ] ), 0 );
			}

				//if ( $delivery_id > 0 ) {
				$total_delivery_fee                  = $delivery->DeliveryFee();
//				$total_order_delivered           += $order_total;

			$line[ Fresh_OrderFields::delivery_fee ] = $total_delivery_fee; //

				if ( isset( $orders_total ) ) {
					$line[ Fresh_OrderFields::total_order ] = $order_total;
				} // $delivery->Price();
				$line[ Fresh_OrderFields::delivery_fee ] = $delivery->DeliveryFee();
				$percent                           = "";
				if ( ( $order_total - $delivery->DeliveryFee() ) > 0 ) {
					$percent = round( 100 * ( $delivery->Price() - $delivery->DeliveryFee() ) / ( $order_total - $delivery->DeliveryFee() ), 0 ) . "%";
				}
				$line[ Fresh_OrderFields::percentage ] = $percent;
				$total_delivery_total            += $delivery->Price();

				$total_delivery_fee                 = $order->getShipping();

*/