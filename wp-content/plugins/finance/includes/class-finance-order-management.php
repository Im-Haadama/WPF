<?php

class Finance_Order_Management {
	static private $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init_hooks(Core_Loader $loader) {
		add_filter('order_complete', array($this, 'order_complete_wrap'));
		add_filter('woocommerce_admin_order_actions', array(__CLASS__, 'add_order_action'), 10, 2);
		// Set Here the WooCommerce icon for your action button
		add_action( 'admin_head', array($this, 'add_custom_order_actions_button_css' ));

		$loader->AddAction('mission_print', $this, 'mission_print_wrap');
		$loader->AddAction('order_set_mission', $this);
		$loader->AddAction('order_add_product', $this);
		$loader->AddAction('wp_trash_post', $this);
//		FinanceLog("before_delete_post added");
	}

	static public function add_order_action($actions, WC_Order $order)
	{
		$O = new Finance_Order($order->get_id());
		switch ($order->status)
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
			$o = new Fresh_Order($id);
			$mission_id = $o->getMission();
			if ( ! in_array( $mission_id, $missions ) ) {
//				print "adding $mission_id<br/>";
				if ($mission_id) array_push( $missions, $mission_id );
			}
		}
		foreach ( $missions as $mission ) {
//			print "mid=$mission<b<!---->r/>";
			$m = new Mission($mission);
			print Core_Html::GuiHyperlink(  $m->getMissionName(), AddParamToUrl(Fresh::getPost(), array("operation" => "mission_print", "mission_id" => $mission )));
			print "<br/>";
		}
//			printCore_Html::GuiHyperlink( "אספקות", "print.php?operation=supplies" );
	}

	function mission_print( $mission_id_filter = null )
	{
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

			$mission_id = $o->getMission();
			if ( $mission_id ) {
				$mission = Mission::getMission( $mission_id );
				$start   = $mission->getStartAddress();
				$end     = $mission->getEndAddress();
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
}