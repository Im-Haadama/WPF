<?php

class Finance_Order_Management {
	static private $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init_hooks() {
		add_filter('order_complete', array($this, 'order_complete_wrap'));
		add_filter('woocommerce_admin_order_actions', array(__CLASS__, 'add_order_action'), 10, 2);

	}
	static public function add_order_action($actions, WC_Order $order)
	{
		if (get_user_id() != 1)
			return $actions;

		$O = new Finance_Order($order->get_id());
		switch ($order->status)
		{
			case "processing":
				if (! ($O->getShippingFee() >0))
					unset($actions['complete']); // Remove the complete
				if ($order->get_id() == "13101"){
					MyLog("actions " . $order->get_id());
					MyLog(StringVar($actions));
				}

				$actions['delivery_note'] = array(
//						'url'    => wp_nonce_url( admin_url( 'admin-post.php?post=' . $order->get_id() . '&action=delivery' ), 'woocommerce-mark-order-status' ),
					'url' => AddParamToUrl("/wp-admin/admin.php?page=deliveries", array("operation"=> "delivery_show_create", "order_id" => $order->get_id())),
//						'/wp-content/plugins/fresh/delivery/create-delivery.php?order_id=' . $order->get_id(),
					'name'   => __( 'Delivery', 'woocommerce' ),
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
}