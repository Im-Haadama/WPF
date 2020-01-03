<?php


class Fresh_Order_Management {
	private $plugin_name;
	private $version;

	/**
	 * Fresh_Orders constructor.
	 *
	 * @param $plugin_name
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = '1.0';
	}

	static function handle()
	{
		$operation = get_param("operation", false, "show_orders");

		print self::handle_operation($operation);
	}

	static function handle_operation($operation)
	{
		switch ($operation) {
			case "show_orders":
				return self::OrdersTable1(array('wc-processing', 'wc-awaiting-shipment'));
		}
	}

	public function enqueue_scripts() {
//		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'orders/orders.js', array( 'jquery' ), $this->version, false );
//		wp_localize_script( $this->plugin_name, 'WPaAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
//		if (! file_exists($file) and get_user_id() == 1) print $file . " not exists <br/>";
		$file = plugin_dir_url( __FILE__ ) . 'core/data/data.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = plugin_dir_url( __FILE__ ) . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

	}

	static function OrdersTable1($statuses = array('wc-processing'), $build_path = true, $user_id = 0, $week = null)
	{
		$order_header_fields = self::OrderHeaderFields();

		$show_fields = array();
		$empty_line  = array();
		for ( $i = 0; $i < OrderFields::field_count; $i ++ ) {
			$empty_line[ $i ]  = "";
			$show_fields[ $i ] = true;
		}
		if ( ! current_user_can( "show_business_info" ) ) {
			$show_fields[ OrderFields::total_order ] = false; // current_user_can("show_business_info");
			$show_fields[ OrderFields::margin ]      = false;
			$show_fields[ OrderFields::good_costs ]  = false;
		}

		$all_tables = ""; // load_scripts(array("/wp-content/plugins/fresh/includes/orders/orders.js", "/wp-content/plugins/fresh/includes/core/gui/client_tools.js"));
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}
		debug_time_log( "1" );

		foreach ( $statuses as $status ) {
			$order_header_fields[0] = Core_Html::gui_checkbox( "select_all_" . $status, "table", 0, "onclick=\"select_all_toggle('select_all_" . $status . "', 'select_order_" . $status . "')\"" );
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

			// print $sql;
			// Build path
			$order_ids = sql_query_array_scalar( $sql );

			// If no orders in this status, move on.
			if ( sizeof( $order_ids ) < 1 ) {
				continue;
			}

			$result                = sql_query( $sql );
			$total_delivery_total  = 0;
			$total_order_total     = 0;
			$total_order_delivered = 0;

			if ( ! $result ) {
				continue;
			}

			$count = 0;

			debug_time_log( "before loop" );
			while ( $row = mysqli_fetch_row( $result ) ) {
				debug_time_log( "after fetch" );
				$count ++;
				$order_id = $row[0];
				$order    = new Fresh_Order( $order_id );

				$customer_id = $order->getCustomerId( $order_id );
				$customer = new Fresh_Client($customer_id);

				$line            = $empty_line;
				$invoice_user_id = get_user_meta( $customer_id, 'invoice_id', 1 );

				if ( $invoice_user_id ) {
					$line [ OrderFields::line_select ] = Core_Html::gui_checkbox( "chk_" . $order_id, "select_order_" . $status );
				} else {
					$line [ OrderFields::line_select ] = Core_Html::GuiHyperlink( "לקוח חדש", "/fresh/?operation=new_customer&order_id=" . $order_id );
				}

				debug_time_log( "a1" );
				$line[ OrderFields::type ]         = $order->GetShipping(  );

				// display order_id with link to display it.
				// 1) order ID with link to the order
				$mission_id = $order->getMission();
				// print $order_id. " ". $mission . "<br/>";

				$args = array();
				$args["events"] = "onchange=\"mission_changed(" . $order_id . ")\"";
				$line[ OrderFields::mission ]  = gui_select_mission( "mis_" . $order_id, $mission_id,  $args);
				$line[ OrderFields::order_id ] = Core_Html::GuiHyperlink( $order_id, "/fresh/orders/get-order.php?order_id=" . $order_id );

				// 2) Customer name with link to his deliveries
				$line[ OrderFields::customer ] = Core_Html::GuiHyperlink( get_customer_name( $customer_id ), Core_Db_MultiSite::LocalSiteTools() .
				                                                                                   "/fresh/account/get-customer-account.php?customer_id=" . $customer_id );


				$line[ OrderFields::recipient ] = get_postmeta_field( $order_id, '_shipping_first_name' ) . ' ' .
				                                  get_postmeta_field( $order_id, '_shipping_last_name' );

				debug_time_log( "middle" );

				$order_total = 0;
				// 3) Order total
				if ( $show_fields[ OrderFields::total_order ] ) {
					$order_total = $order->GetTotal();
					// get_postmeta_field( $order_id, '_order_total' );
					$line[ OrderFields::total_order ] = $order_total;
					$total_order_total                += $order_total;
					debug_time_log( "total" );
				}

				// 4) Delivery note
				$delivery_id = $order->getDeliveryId(); // get_delivery_id( $order_id );

				if ( $delivery_id > 0 ) {
					$delivery                           = new Fresh_Delivery( $delivery_id );
					$line[ OrderFields::delivery_note ] = Core_Html::GuiHyperlink( $delivery_id, "/fresh/delivery/get-delivery.php?id=" . $delivery_id );
					//if ( $delivery_id > 0 ) {
					if ( isset( $orders_total ) ) {
						$line[ OrderFields::total_order ] = $order_total;
					} // $delivery->Price();
					$line[ OrderFields::delivery_fee ] = $delivery->DeliveryFee();
					$percent                           = "";
					if ( ( $order_total - $delivery->DeliveryFee() ) > 0 ) {
						$percent = round( 100 * ( $delivery->Price() - $delivery->DeliveryFee() ) / ( $order_total - $delivery->DeliveryFee() ), 0 ) . "%";
					}
					$line[ OrderFields::percentage ] = $percent;
					$total_delivery_total            += $delivery->Price();
					$total_delivery_fee                  = $delivery->DeliveryFee();
					$total_order_delivered           += $order_total;
					if ( $delivery->isDraft() ) {
						$line [ OrderFields::line_select ] = "טיוטא";
					}
					//	}
				} else {
					// print "status = " . $order->getStatus() . "<br/>";
					if ($order -> getStatus() == 'wc-processing')
						$line[ OrderFields::delivery_note ] = Core_Html::GuiHyperlink( "צור",  "/fresh/delivery/create-delivery.php?order_id=" . $order_id, "_blank" );
					$line[ OrderFields::percentage ]    = Core_Html::GuiHyperlink( "בטל", $_SERVER['PHP_SELF'] . "?operation=cancel_order&id=" . $order_id );
					$total_delivery_fee                 = $order->getShipping();
				}
				$line[ OrderFields::city ]         = $order->getOrderInfo( '_shipping_city' );
				$line[ OrderFields::payment_type ] = $customer->get_payment_method_name( );
				if ( current_user_can( "show_business_info" ) ) {
					$line[ OrderFields::good_costs ] = $order->GetBuyTotal();
					$line[ OrderFields::margin ]     = round( ( $line[ OrderFields::total_order ] - $line[ OrderFields::good_costs ] ), 0 );
				}
				$line[ OrderFields::delivery_fee ] = $total_delivery_fee; //

				array_push( $rows, $line );
				debug_time_log( "loop end" );
			}

			//   $data .= "<tr> " . trim($line) . "</tr>";

			debug_time_log( "before sort" );

			// sort( $lines );

			debug_time_log( "2" );

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
				$data       = Core_Html::gui_header( 2, Fresh_Order::get_status_name($status) );
				// gui_table( $rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null, $links = null)
				$data       .= gui_table( $rows, $status, true, true, $sums, null, null, $show_fields );
				$all_tables .= $data;
			}
		}

		debug_time_log( "end" );

		return $all_tables;
	}

	static function OrderHeaderFields() {
		return array(
			"בחר", // replaced in loop
			"סוג משלוח",
			"משימה",
			"מספר הזמנה",
			"שם המזמין",
			"עבור",
			"סכום הזמנה",
			"עלות מוצרים",
			"מרווח",
			"דמי משלוח",
			"ישוב",
			"אמצעי תשלום",
			"תעודת משלוח",
			"אחוז סופק"
		);
	}
}

class OrderFields {
	const
		/// User interface
		line_select = 0,
		type = 1,
		mission = 2,
		order_id = 3,
		customer = 4,
		recipient = 5,
		total_order = 6,
		good_costs = 7,
		margin = 8,
		delivery_fee = 9,
		city = 10,
		payment_type = 11,
		delivery_note = 12,
		percentage = 13,
		field_count = 14;
}
