<?php

my_show_errors();
class Finance_Order_Management {
	static private $_instance;
	private $orders;

	/**
	 * Finance_Order_Management constructor.
	 *
	 * @param $orders
	 */
	public function __construct( ) {
		$this->orders = null;
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init_hooks(Core_Hook_Handler $loader) {
		add_filter('order_complete', array($this, 'order_complete_wrap'));
		add_filter('woocommerce_admin_order_actions', array(__CLASS__, 'add_order_action'), 10, 2);
		// Set Here the WooCommerce icon for your action button
		add_action( 'admin_head', array($this, 'add_custom_order_actions_button_css' ));

		$loader->AddAction('mission_print', $this, 'mission_print_wrap');
		$loader->AddAction('order_set_mission', $this);
		$loader->AddAction('order_add_product', $this);
		$loader->AddAction('wp_trash_post', $this);
		$loader->AddAction( 'woocommerce_proceed_to_checkout', $this, 'disable_checkout_button_no_shipping', 1 );
		$loader->AddFilter( 'woocommerce_cart_no_shipping_available_html', $this, 'no_shipping_message' );
		$loader->AddFilter( 'woocommerce_no_shipping_available_html', $this, 'no_shipping_message' );
		add_filter( 'manage_edit-shop_order_columns', array(__CLASS__, 'wc_new_order_column' ));
		add_action( 'manage_shop_order_posts_custom_column', array(__CLASS__, 'add_freight' ));

//		FinanceLog("before_delete_post added");
	}

	static public function add_order_action($actions, WC_Order $order)
	{
		$O = new Finance_Order($order->get_id());
		switch ($order->get_status())
		{
			case "processing":
				if (! $O->getShippingFee())
					$actions['add_delivery_note'] = array(
						'url' => '/wp-admin/post.php?post=' . $order->get_id() .'&action=edit',
						'name' => __('Add delivery fee', 'e-fresh'),
						'action' => 'fee'
					);

				$actions['delivery_note'] = array(
//						'url'    => wp_nonce_url( admin_url( 'admin-post.php?post=' . $order->get_id() . '&action=delivery' ), 'woocommerce-mark-order-status' ),
					'url' => AddParamToUrl("/wp-admin/admin.php?page=deliveries", array("operation"=> "delivery_show_create", "order_id" => $order->get_id())),
					'name'   => __( 'Create delivery note', 'e-fresh' ),
					'action' => 'delivery'
				);
				break;
			case "awaiting-shipment":
			case "completed":
				$actions['delivery_note'] = array(
						'url'    => wp_nonce_url( admin_url( 'admin.php?page=deliveries&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
//					'url' => $order->get_url(),
					'name'   => __( 'Delivery', 'woocommerce' ),
					'action' => 'delivery'
				);
				break;
		}
		return $actions;
	}

	function add_custom_order_actions_button_css() {
		// https://rawgit.com/woothemes/woocommerce-icons/master/demo.html
		// The key slug defined for your action button
		$action_slug = "delivery";

		echo '<style>.wc-action-button-'.$action_slug.'::after { font-family: woocommerce !important; content: "\e028" !important; }</style>';

		$action_slug = "fee";

		echo '<style>.wc-action-button-'.$action_slug.'::after { font-family: woocommerce !important; content: "\e016" !important; }</style>';
	}

	function order_set_mission()
	{
		$order_id = GetParam("order_id", true);
		$mission_id = GetParam("mission_id", true);
		$order = new Finance_Order($order_id);
		$order->setMissionID($mission_id);
		return true;
	}

	function mission_print_wrap()
	{
		$id = GetParam("mission_id", true);
		$this->mission_print($id);
	}

	function mission_print( $mission_id_filter = null )
	{
		$baskets = array();

		$sql = 'SELECT posts.id as id, order_user(id) as user_id' // , order_is_group(id) as is_grouped
		       . ' FROM `wp_posts` posts'
		       . " WHERE post_status LIKE '%wc-processing%' order by 1";

		$grouped_orders = array();
		$result         = SqlQuery( $sql );
		print Core_Html::HeaderText();
		print "<style>";
		print "@media print {";
		print "h1 {page-break-before: always;}";
		print "}";
		print "</style>";

		$orders = array();
		$start  = null;
		$end    = null;

		while ( $row = SqlFetchAssoc( $result ) ) {
			$id         = $row["id"];
			$is_grouped = false; // $row["is_grouped"];
			$user_id    = $row["user_id"];
			$o = new Fresh_Order($id);

			$mission_id = $o->getMissionId();
			if ( $mission_id ) {
				try {
					$mission = Mission::getMission( $mission_id );
					$start   = $mission->getStartAddress();
					$end     = $mission->getEndAddress();
				} catch (Exception $e)
				{
					print "Mission $mission_id for order $id not found<br/>";
					continue;
				}
			}
			if ( isset( $mission_id_filter ) and $mission_id != $mission_id_filter ) {
				continue;
			}
			if ( $is_grouped ) {
				if ( ! array_key_exists( $user_id, $grouped_orders ) ) {
					$grouped_orders[ $user_id ] = array();
					array_push( $orders, $id );
				}
				array_push( $grouped_orders[ $user_id ], $id );
			} else {
				array_push( $orders, $id );
			}
		}
//	$path_orders = array();
		// find_route_1( $node, $rest, &$path, $print = false, $end ) {

		// find_route_1( $start, $orders, $path_orders, false, $end );
		foreach ( $orders as $order_id ) {
			if (class_exists('Fresh_ProductIterator'))
				self::collect_baskets($baskets, $order_id);
			update_post_meta( $order_id, "printed", 1 );
			$O       = new Fresh_Order( $order_id );
			$user_id = $O->getCustomerId();
			if ( array_key_exists( $user_id, $grouped_orders ) ) {
				print $O->infoBox( true, null, $grouped_orders[ $user_id ][0] );
				$d = Delivery::CreateFromOrder( $grouped_orders[ $user_id ] );
				$d->PrintDeliveries( ImDocumentType::delivery, ImDocumentOperation::collect );

			} else {
//				print $O->infoBox( false );
				$D = new Finance_Delivery( $order_id );
				print $D->ShowCreate(array("packing"=>true));
//				PrintDeliveries( Finance_DocumentType::delivery, Finance_DocumentOperation::collect, 0);
			}
		}
		foreach($baskets as $basket_id)
		{
			$b = new Fresh_Basket($basket_id);
			print Core_Html::GuiHeader(2, $b->getName());
			print $b->get_basket_content();
		}

	}

	private function collect_baskets(&$baskets, $order_id)
	{
		$o = new Fresh_Order($order_id);
		$iter = $o->productIter();

		foreach ($iter as $product)
		{
			$prod_id = $product->get_product_id();
			$p = new Fresh_Product($prod_id);
			if ($p->is_basket())
			{
				if (! in_array($prod_id, $baskets))
					array_push($baskets, $prod_id);
			}
		}
	}

	public function order_add_product()
	{
		MyLog(__FUNCTION__);
		$prod_id = GetParam("prod", true);
		$order_id = GetParam("order_id", true);
		$q = GetParam("quantity", false, 1);
		if ( ! is_numeric( $q ) ) {
			die ( "no quantity" );
		}
		$units = GetParam("units", false, null);

		$o = new Finance_Order( $order_id );
		$oid = 0;
		$o->AddProduct( $prod_id, $q, false, - 1, $units, null, null, $oid );
		MyLog("ooid= $oid");
		if (($o->getStatus() == 'wc-processing') and ($oid > 0)){
			$o->updateComment($oid, 'התווסף לאחר העברת ההזמנה לטיפול');
		}
		return $oid;
	}

	function wp_trash_post($order_id) {
		global $post_type;

		if($post_type !== 'shop_order') {
			return;
		}

		$del = new Finance_Delivery($order_id);
		$del->delete();
	}

	function disable_checkout_button_no_shipping() {
		$package_counts = array();

		// get shipping packages and their rate counts
		$packages = WC()->shipping->get_packages();
		foreach( $packages as $key => $pkg )
			$package_counts[ $key ] = count( $pkg[ 'rates' ] );

		// remove button if any packages are missing shipping options
		if( in_array( 0, $package_counts ) )
			remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
	}

	function no_shipping_message( $message ) {
		return __( 'Check with us availability of deliveries to your area.' );
	}

	static function wc_new_order_column( $columns ) {
		$columns['city'] = __("City");
		$columns['fee'] = __("fee");
		$columns['freight'] = __("Freight");

		return $columns;
	}

	static function add_freight($col)
	{
		global $post;
		$O = new Finance_Order($post->ID);
		switch ($col) {
			case "freight":
				print Flavor_Mission_Views::gui_select_mission("mis_" . $post->ID, $O->getMissionId(),
					array("events" => 'onclick="event.stopPropagation();order_mission_changed(\'' . Finance::getPost() . "', " . $post->ID .')"'));
				break;
			case 'city':
				print $O->getOrderInfo( '_shipping_city' );
				break;
			case 'fee':
				print $O->getShippingFee();
				break;
		}
	}

	public function CalculateNeeded( &$needed_products, $user_id = 0, &$user_table = null, $mission_id = null) {
		$this->orders = array();
		$db_prefix = GetTablePrefix("delivery_lines");

		$include_shipment = true;
		/// print "user id " . $user_id . "<br/>";
		$debug_product = 0; // 141;
		if ( ! $user_id ) {
			if ( self::check_cache_validity() ) {
//				if (get_user_id() == 1) print "cache valid<br/>";
				$needed_products = array();

				$sql = " SELECT prod_id, need_q, need_u, prod_get_name(prod_id) FROM im_need ORDER BY 4 ";

				$result = SqlQuery( $sql );

				while ( $row = mysqli_fetch_row( $result ) ) {
					$prod_or_var = $row[0];
					$q           = $row[1];
					$u           = $row[2];

					$needed_products[ $prod_or_var ][0] = $q;
					$needed_products[ $prod_or_var ][1] = $u;
				}

				return $needed_products;
			}

//			print "cache not valid<br/>";
			// Cache not vaild.
			// Clean the im_need_orders, im_need table
			$sql = "truncate table im_need_orders";
			SqlQuery( $sql );

			$sql = "truncate table im_need";
			SqlQuery( $sql );
		}
		// Do the calculation
		$query = "Where (post_status LIKE '%wc-processing%' " . ($include_shipment ? " OR post_status = 'wc-awaiting-shipment'" : "") . ")";
		if ($mission_id){
			$query = "Where (id in (select post_id from wp_postmeta where meta_key = 'mission_id' and meta_value = $mission_id)) ";
		}

		$sql = "SELECT id, post_status FROM wp_posts " . $query;

		if ( $user_id ) {
			$sql .= " and order_user(id) = " . $user_id;
		}

//		 print $sql;
		$result = SqlQuery( $sql );

		// Loop open orders.
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$id     = $row["id"];

			array_push($this->orders, $id);
			$O = new Finance_Order($id);
			if (is_array($user_table)) array_push($user_table, $O->getCustomerId());
			$status = $row["post_status"];
			$del_id = 0;

			if ( $status == 'wc-awaiting-shipment' or $status == 'wc-completed' ) $del_id = SqlQuerySingleScalar( "SELECT id FROM im_delivery WHERE order_id = " . $id );

			// Update im_need_orders table
			if ( ! $user_id ) SqlQuery("INSERT INTO im_need_orders (order_id) VALUE (" . $id . ") ");

			$order       = new WC_Order( $id );
			$order_items = $order->get_items();

			foreach ( $order_items as $item ) {
				$prod_or_var = $item['product_id'];

				if ($prod_or_var == $debug_product) MyLog($prod_or_var, __FUNCTION__);

				$variation = null;
				if ( isset( $item["variation_id"] ) && $item["variation_id"] > 0 ) $prod_or_var = $item["variation_id"];
				$qty  = $item['qty'];
				$unit = $item['unit'];

				if ( $unit ) {
					$unit_array = explode( ",", $unit );
					$unit_t     = $unit_array[0];
					$key        = array( $prod_or_var, $unit_t );
					$qty        = $unit_array[1];
				} else {
					$key = array( $prod_or_var, '' );
				}

				if ( $prod_or_var == $debug_product ) {
					print "<br/>" . get_product_name( $prod_or_var ) . " ";
				}
				// print "status: " . $status . "<br/>";
				if ( $status == 'wc-processing' ) {
					// print "prc" . $order->get_id() . "<br/>";
					if ( $debug_product == $prod_or_var ) {
						print "proccessing " . $qty . " ";
					}
					// Adds with bundle consideration
					self::AddProducts( $key, $qty, $needed_products, $item->get_id() );
				} else {
					// Check if order line supplied.
					$sql = "SELECT sum(quantity) FROM ${db_prefix}delivery_lines WHERE prod_id = " . $prod_or_var .
					       " AND quantity > 0 AND delivery_id = " . $del_id;

					// print $sql . "<br/>";

					$supplied = SqlQuerySingleScalar( $sql );
					// if ($prod_or_var == $debug_product) print " c= " . $c. " " ;
					// 	print get_product_name($prod_or_var) . " " . $supplied . " " . $qty . "<br/>";
					if ( round( $supplied, 1 ) < round( $qty, 1 ) ) {
						// print get_product_name($prod_or_var) . " " . $qty . " " . $needed_products[0] . "<br/>";
						// if ($prod_or_var == $debug_product) print " adding " . $qty;
						self::AddProducts( $key, $qty - $supplied, $needed_products );
					}
					// print $prod_or_var . " " . $c . "<br/>";
				}

				//   print $item['product_id'] . " " . $item['qty'] . "<br/>";
			}
		}
		// Update im_need table
		foreach ( $needed_products as $prod_or_var => $v1 ) {
			$q = 0;
			$u = 0;
			if ( isset( $needed_products[ $prod_or_var ][0] ) ) {
				$q = $needed_products[ $prod_or_var ][0];
			}
			if ( isset( $needed_products[ $prod_or_var ][1] ) ) {
				$u = $needed_products[ $prod_or_var ][1];
			}
			if ( ! $user_id ) {
				$sql = "INSERT INTO im_need (prod_id, need_q, need_u) " .
				       " VALUES (" . $prod_or_var . "," . $q . "," . $u . ")";

				SqlQuery( $sql );
			}
		}
	}

	private static function AddProducts( $prod_key, $qty, &$needed_products, $item_id = 0 ) {

		// var_dump($prod_key); print "<br/>";
		// Prod key is array(prod_id or var_id, unit)

		// Handle baskets recursively
		$prod_or_var = $prod_key[0];
		$p = new Fresh_Product($prod_or_var);
//		if ($item_id == 369) print "---------------------------" . $p->getName() . "<br/>";

		if ( $p->is_basket( $prod_or_var ) ) {
			// Add basket content.
			foreach (Fresh_Basket::get_basket_content_array( $prod_or_var,  $item_id ) as $basket_prod => $basket_q) {
				$bp = new Fresh_Product($basket_prod);
				if (! $bp->is_basket())
					self::AddProducts( array( $basket_prod, '' ), $qty * $basket_q, $needed_products );
			}
			// Add also the basket
			if ( ! isset( $needed_products[ $prod_or_var ][ 0 ] ) ) $needed_products[ $prod_or_var ][ 0 ] = 0;
			$needed_products[$prod_or_var][0] += $qty;
		} else {
			$unit_key = '';
			// Handle single product:
			$unit_str = $prod_key[1];

			switch ( $unit_str ) {
				case 'קג':
				case '':
					$unit_key = 0;
					break;
				case 'יח':
					$unit_key = 1;
					break;
				default:
					print "error: new unit ignored - " . $unit_str;
			}

			// Check if this product
			if ( $p->is_bundle( ) ) {
				// print get_product_name($prod_or_var) . " is bundle " . "<br/>";
				$b = Fresh_Bundle::CreateFromBundleProd( $prod_or_var );
				$p = $b->GetProdId();
				if ( ! ( $p > 0 ) ) {
					print "bad prod id for $prod_or_var<br/>";

					return;
				}
				// print $b->getName() . "q=$qty bq=" . $b->GetQuantity() . "<br/>";
				$qty         = $qty * $b->GetQuantity();
				$prod_or_var = $p;
			}

			if ( ! isset( $needed_products[ $prod_or_var ][ $unit_key ] ) ) $needed_products[ $prod_or_var ][ $unit_key ] = 0;

//			print "QQQQ adding $qty to " . $prod_or_var . "<br/>";
//			print "before: " . $needed_products[ $prod_or_var ][ $unit_key ] . "<br/>";
			$needed_products[ $prod_or_var ][ $unit_key ] += $qty;
//			print "after: " . $needed_products[ $prod_or_var ][ $unit_key ] . "<br/>";
			//if ($key == 354) { print "array:"; var_dump($needed_products[$prod_or_var]); print "<br/>";}
		}
	}

	static function check_cache_validity() {
		return false;
//	$sql = "SELECT count(p.id)
//	FROM wp_posts p
//	 LEFT JOIN im_need_orders o
//	  ON p.id = o.order_id
//	  WHERE p.id IS NULL OR o.order_id IS NULL AND post_status LIKE '%wc-processing%'";
		$sql = "SELECT count(id)
	FROM wp_posts p
  	where post_status like '%wc-processing%'
  	and id not in (select order_id from im_need_orders)";
		$new = SqlQuerySingleScalar( $sql );
//	print "new: " . $new . "<br/>";

		$sql  = "SELECT count(id)
	  FROM im_need_orders
	  WHERE order_id NOT IN (SELECT id FROM wp_posts WHERE post_status LIKE '%wc-processing%')";
		$done = SqlQuerySingleScalar( $sql );

		if ( $done > 0 or $new > 0 ) return false;

		return true;
	}

	/**
	 * @return null
	 */
	public function getOrders() {
		return $this->orders;
	}

}