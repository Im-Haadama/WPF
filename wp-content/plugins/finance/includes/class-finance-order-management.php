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

		$loader->AddAction('order_set_mission', $this);
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
}