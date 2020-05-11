<?php


class Fresh_Packing {

//	static function add_admin( $menu ) {
////		$menu->AddMenu( 'Fresh Packing', 'Packing', 'show_manager', 'packing', 'Fresh_Packing::admin' );
//		$menu->AddSubMenu( 'packing', 'edit_shop_orders',
//			array(
//				array(
//					'page_title' => 'Needed products',
//					'menu_title' => 'Needed Products',
//					'menu_slug'  => 'needed_products',
//					'function'   => __CLASS__ . '::needed_products'
//				),
//				array(
//					'page_title' => 'Orders to handle',
//					'function'   => __CLASS__ . '::OrdersToHandle'
//				)
//			) );
//	}

	static function needed_products() {
		$result = "";

		$result                .= Core_Html::GuiHeader( 1, "Needed products" );
		$result                .= Fresh_Order::GetAllComments();
		$args["tabs_load_all"] = true;
		$selected_tab= GetParam("selected_tab", false, null);

		//( $filter_zero, $history = false, $filter_stock = false, $limit_to_supplier_id = null )

		// Calculate needed (all suppliers including no-supplier
//		$needed_prods_by_supplier = self::needed_products_by_supplier();

//		// Show the tabs
//		$result                 .= self::supplier_tabs($needed_prods_by_supplier);

//		// Show delected tab
		$result                 .=  self::NeededProducts( );

		print $result;
	}

	function supplier_tabs($needed_prod_by_supplier)
	{
		$supplier_tabs   = [];

	}

	static function get_total_orders_supplier( $supplier_id, $needed_products, $filter_zero = false, $filter_stock = false, $history = false) {
		$result            = "";
		$inventory_managed = InfoGet( "inventory" );
		if ($supplier_id) $supplier          = new Fresh_Supplier( $supplier_id );
		else		$supplier = null;

		$checkbox_class = "product_checkbox" . $supplier_id;

		$data_lines = array();

		foreach ( $needed_products as $prod_id => $quantity_array ) {
			$P = new Fresh_Product( $prod_id );
			if ( ! $P ) continue;

			$row = array();

			if ( $filter_stock and $P->getStockManaged() and $P->getStock() > $quantity_array[0] ) continue;

			if ( $P->isDraft() ) $row[] = "טיוטא";
			else $row[] = gui_checkbox( "chk" . $prod_id, $checkbox_class );
			$row[] = $P->getName();
			$row[] = Core_Html::GuiHyperlink( isset( $quantity_array[0] ) ? round( $quantity_array[0], 1 ) : 0,
				"get-orders-per-item.php?prod_id=" . $prod_id . ( $history ? "&history" : "" ) );

			// Units. disabbled for now.
			//		if ( isset( $quantity_array[1] ) ) {
//			$line .= "<td>" . $quantity_array[1] . "</td>";
//		} else {
//			$line .= "<td></td>";
//		}
			$quantity = isset( $quantity_array[0] ) ? $quantity_array[0] : 0;

			$p     = new Fresh_Product( $prod_id );
			if ( $inventory_managed ) {
				$q_inv = $p->getStock();
				$row[] = Core_Html::GuiInput( "inv_" . $prod_id, $q_inv, array(
					"onchange=\"change_inv(" . $prod_id . ")\"",
					"onkeyup=\"moveNext(" . $prod_id . ")\""
				) );

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

			$result .= Core_Html::gui_table_args( $table_rows, "needed_" . $supplier_id );

			if ( ! $supplier_id ) {
				$result .= "יש להפוך לטיוטא רק לאחר שמוצר אזל מהמלאי והוצע ללקוחות תחליף<br/>";
				$result .= Core_Html::GuiButton( "btn_draft_products", "הפוך לטיוטא", array("action" => "draft_products()"));
			}
		}

		return $result;
	}

	static function NeededProducts( $filter_zero = false, $history = false, $filter_stock = false, $limit_to_supplier_id = null ) {
		$result          = "";
		$needed_products = array();
		$supplier_tabs = array();

		Fresh_Order::CalculateNeeded( $needed_products );

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
//			if (! $supplier_id) $supplier_id = 100000000;
//			print "sup=$supplier_id<br/>";

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
						$supplier = new Fresh_Supplier( $row[0] );
					} else {
						$supplier = null;
					}

					$tab_content =
						self::get_total_orders_supplier( $supplier->getId(), $supplier_needed[ $supplier->getId() ], $filter_zero, $filter_stock, $history );

					if ( $supply_id = Fresh_Suppliers::TodaySupply( $supplier->getId() ) ) {
						$tab_content .= Core_Html::GuiHyperlink( "Supply " . $supply_id, "/fresh/supplies/supplies-page.php?operation=show&id=" . $supply_id ) . "<br/>";
					}

					$tab_content .= Core_Html::GuiButton( "btn_create_supply_" . $supplier->getId(), "Create a supply", array( "action" => "needed_create_supplies(" . $supplier->getId() . ")" ) );

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
		$result               .= Core_Html::GuiTabs( $supplier_tabs, $args );

		return $result;
	}

	static function init_hooks() {
		add_filter( 'manage_edit-shop_order_columns', array(__CLASS__, 'wc_new_order_column' ));
		add_action( 'manage_shop_order_posts_custom_column', array(__CLASS__, 'add_freight' ));
	}

	static function add_freight($col)
	{
		global $post;
		$m = new Fresh_Order($post->ID);
		switch ($col) {
			case "freight":
			    print self::gui_select_mission("mis_" . $post->ID, $m->getMission(), array("events" => 'onclick="event.stopPropagation();mission_changed(' . $post->ID .')"'));
			    break;
			case 'city':
				print $m->getOrderInfo( '_shipping_city' );
				break;

		}
	}

	static function wc_new_order_column( $columns ) {
		$columns['city'] = __("City");
		 $columns['freight'] = __("Freight");

		return $columns;
	}

	static function orders_per_item( $prod_id, $multiply, $short = false, $include_basket = false, $include_bundle = false, $just_total = false, $month = null ) {
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
			$sql1    = "select basket_id from im_baskets where product_id = $prod_id";
			$baskets = SqlQueryArrayScalar( $sql1 );
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

		$result         = SqlQuery( $sql );
		$lines          = "";
		$total_quantity = 0;

		while ( $row = mysqli_fetch_row( $result ) ) {
			$order_item_id = $row[0];
			$order_id      = $row[1];
			$o = new Fresh_Order($order_id);
			$quantity      = self::get_order_itemmeta( $order_item_id, '_qty' );

			// consider quantity in the basket or bundle
			$pid = self::get_order_itemmeta( $order_item_id, '_product_id' );
			$p   = new Fresh_Product( $pid );
			if ( $p->is_bundle() ) {
				$b        = Fresh_Bundle::CreateFromBundleProd( $pid );
				$quantity *= $b->GetQuantity();
			}
//			else if ( $p->is_basket() ) {
//				$b        = new Fresh_Basket( $pid );
//				$quantity *= $b->GetQuantity( $prod_id );
//			}
			$first_name = get_postmeta_field( $order_id, '_shipping_first_name' );
			$last_name  = get_postmeta_field( $order_id, '_shipping_last_name' );

			$total_quantity += $quantity;

			if ( $short ) {
					$lines .= $quantity . " " . $last_name . ", ";
			} else {
					$line  = "<tr>" . "<td> " . $o->getLink() . "</td>";
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

	static function get_order_itemmeta( $order_item_id, $meta_key ) {
		if ( is_array( $order_item_id ) ) {
			$sql = "SELECT sum(meta_value) FROM wp_woocommerce_order_itemmeta "
			       . ' WHERE order_item_id IN ( ' . CommaImplode( $order_item_id ) . ") "
			       . ' AND meta_key = \'' . EscapeString( $meta_key ) . '\'';

			return SqlQuerySingleScalar( $sql );
		}
		if ( is_numeric( $order_item_id ) ) {
			$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
			        . ' WHERE order_item_id = ' . $order_item_id
			        . ' AND meta_key = \'' . EscapeString( $meta_key ) . '\''
			        . ' ';

			return SqlQuerySingleScalar( $sql2 );
		}

		return - 1;
	}

	static function set_order_itemmeta( $order_item_id, $meta_key, $meta_value ) {
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
		SqlQuery( $sql );
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
				$args["events"]                      = "onchange=\"mission_changed(" . $order_id . ")\"";
				$line[ Fresh_OrderFields::mission ]  = self::gui_select_mission( "mis_" . $order_id, $mission_id, $args );
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

	static function gui_select_mission( $id, $selected = 0, $args = null ) {
		$events = GetArg( $args, "events", null );

		$query = " date >= curdate() or date is null";
		if ($selected)
			$query .= " or (id = $selected)";

		$args = array(
			"events"   => $events,
			"selected" => $selected,
			"query"    => $query
		);

		// "ifnull(concat (name, ' ', DAYOFMONTH(date), '/', month(date)), name)");

		return Core_Html::GuiSelectTable( $id, "missions", $args );
	}

	static function table() {
		foreach (Fresh_Category::GetTopLevel() as $term) {
			$term = new Fresh_Category($term->term_id);
			$table = self::PackingTable( $term);
			if ($table) {
				print Core_Html::GuiHeader(2, $term->getName());

				print $table;
			}
		}
	}

	static function PackingTable($term)
	{
		$rows = [];
		$sql  = "SELECT id, post_status FROM wp_posts " .
		        " WHERE (post_status LIKE '%wc-processing%' ) 
		         order by 1 asc"; // OR post_status = 'wc-awaiting-shipment'

		$rows["header"] = array("name" => "סיכום הזמנות");

		$sql_result = SqlQuery( $sql );
		$empty_line = array("name" => 'here');
		foreach (Fresh_Basket::getAll() as $basket_id) {
			$b = new Fresh_Basket($basket_id);
			$empty_line[ $basket_id ] = '';
			if ($term->in($b->getTerms()))
				$rows["header"][$basket_id] = $b->getName();
		}

		while ( $row = mysqli_fetch_assoc( $sql_result ) ) {
			$order_id = $row['id'];
			$rows[$order_id] = array();
			$O = new Fresh_Order($order_id);
			$C = new Fresh_Client($O->getCustomerId());

			$rows[$order_id] = $empty_line;
			$rows[$order_id]["name"] = Core_Html::GuiHyperlink($C->getName(), "/wp-admin/post.php?post=$order_id&action=edit");

			foreach ($O->getProducts() as $prod_info) {
				$prod_id                       = $prod_info['prod_id'];
				$p = new Fresh_Product($prod_id);
				if (! $term->in($p->getTerms())) continue;
				$prod_q                        = $prod_info['quantity'];
				$rows[ $order_id ][ $prod_id ] = $prod_q;
				if (! isset($rows["total"][$prod_id]))
					$rows["total"][$prod_id] = 0;
				$rows["total"][$prod_id] += $prod_q;
//				print $rows["total"][$prod_id] . "<br/>";
				if (! isset($rows["header"][$prod_id])) {
					$p                          = new Fresh_Product( $prod_id );
					$rows["header"][ $prod_id ] = $p->getName();
				}
			}
		}
		if (! isset($rows["total"]) or ! count($rows["total"])) return "";
		foreach ($rows as $order_id => $row){
			$table[$order_id] = array();
			$has_items = false;
			foreach ($rows["header"] as $prod_id => $c) {
				if (isset($rows["total"][$prod_id]) and ($prod_id > 0)) {
					$table["total"][$prod_id] = $rows["total"][$prod_id];
					if (isset($rows[$order_id][$prod_id])) $has_items = true;
				}
				$table[ $order_id ][ $prod_id ] = ( isset( $rows[ $order_id ][ $prod_id ] ) ? $rows[ $order_id ][ $prod_id ] : '' );
			}
			if ($order_id > 0 and ! $has_items) unset ($table[$order_id]);
		}
		$table["total"]["name"] = 'סה"כ';
							//		$args = array("transpose" => 1);
		$args = array("class" => "widefat", "line_styles" => array('background: #DDD','background: #EEE', 'background: #FFF') );

		return Core_Html::gui_table_args($table, "table", $args);
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