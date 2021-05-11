<?php

my_show_errors();
class Finance_Order_Management {
	static private $_instance;

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

	static function printing()
	{
		if ($operation = GetParam("operation", false, null)) {
			print apply_filters($operation, null);
			return;
		}
		$sql = 'SELECT posts.id as id'
		       . ' FROM `wp_posts` posts'
		       . " WHERE post_status LIKE '%wc-processing%' order by 1";

		$result = SqlQuery( $sql );

		$missions = array();
		while ( $row = SqlFetchAssoc( $result ) ) {
			$id         = $row["id"];
//			print "id=$id<br/>";
			$o = new Finance_Order($id);
			$mission_id = $o->getMissionId();
			if ( ! in_array( $mission_id, $missions ) ) {
//				print "adding $mission_id<br/>";
				if ($mission_id) array_push( $missions, $mission_id );
			}
		}
		foreach ( $missions as $mission ) {
//			print "mid=$mission<b<!---->r/>";
			try {
				$m = new Mission($mission);
				print Core_Html::GuiHyperlink(  $m->getMissionName(), AddParamToUrl(Fresh::getPost(), array("operation" => "mission_print", "mission_id" => $mission )));
				print "<br/>";
			} catch (Exception $exception) {
				print $exception->getMessage() ."<br/>";
			}
		}
//			printCore_Html::GuiHyperlink( "אספקות", "print.php?operation=supplies" );
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
				print Flavor_Mission::gui_select_mission("mis_" . $post->ID, $O->getMissionId(),
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
}