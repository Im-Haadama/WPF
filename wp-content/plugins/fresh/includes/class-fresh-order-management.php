<?php


class Fresh_Order_Management {
	private $plugin_name;
	private $version;
	static private $_instance;

	/**
	 * Fresh_Orders constructor.
	 *
	 * @param $plugin_name
	 */
	public function __construct( $plugin_name = "Fresh", $version = "1.0" ) {
		$this->plugin_name = $plugin_name;
		$this->version     = '1.0';

		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts' ));
	}

	static public function init_hooks() {
		// add_filter( 'manage_shop_order_posts_custom_column', array(__CLASS__, 'add_my_account_order_actions'), 10, 2 );
		add_filter('woocommerce_admin_order_actions', array(__CLASS__, 'add_order_action'), 10, 2);
		add_action('admin_post_delivery', array(__CLASS__, 'create_delivery_note'));
	}

	static public function create_delivery_note($a)
	{
		print "<html dir='rtl'>";
		$show_inventory = false;
		$order_id = GetParam("post");
		print Core_Html::GuiHeader(1, "del" . $order_id);
		if (Fresh_Order::get_delivery_id( $order_id )) {
		$d = Fresh_Delivery::CreateFromOrder( $order_id );
			$d->PrintDeliveries( FreshDocumentType::delivery, Fresh_DocumentOperation::create, false, $show_inventory );
		}
		else
		{
			$O = new Fresh_Order( $order_id );
			print $O->infoBox( false, "יצירת תעודת משלוח ל" );
			$d = Fresh_Delivery::CreateFromOrder( $order_id );
			$d->PrintDeliveries( FreshDocumentType::delivery, Fresh_DocumentOperation::create, false, $show_inventory );
		}

//		print $result;
	}

	static public function add_order_action( $actions, $order)
	{
		switch ($order->status)
		{
			case "processing":
					$actions['delivery_note'] = array(
//						'url'    => wp_nonce_url( admin_url( 'admin-post.php?post=' . $order->get_id() . '&action=delivery' ), 'woocommerce-mark-order-status' ),
					'url' => '/wp-content/plugins/fresh/delivery/create-delivery.php?order_id=' . $order->get_id(),
						'name'   => __( 'Delivery', 'woocommerce' ),
						'action' => 'delivery',
					);
					break;
			case "awaiting-shipment":
				$actions['delivery_note'] = array(
//						'url'    => wp_nonce_url( admin_url( 'admin-post.php?post=' . $order->get_id() . '&action=delivery' ), 'woocommerce-mark-order-status' ),
					'url' => '/wp-content/plugins/fresh/delivery/get-delivery.php?order_id=' . $order->get_id(),
					'name'   => __( 'Delivery', 'woocommerce' ),
					'action' => 'delivery',
				);
				break;
		}
		return $actions;
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	static function handle() {
		$operation = GetParam( "operation", false, "show_orders" );

		print self::handle_operation( $operation );
	}

	public function enqueue_scripts() {
	}
}

class Fresh_OrderFields {
	const
		/// User interface
		line_select = 0,
		type = 1,
		mission = 2,
		order_id = 3,
		customer = 4,
		recipient = 5,
//		total_order = 6,
//		good_costs = 7,
//		margin = 8,
//		delivery_fee = 9,
		city = 6,
		payment_type = 7,
		delivery_note = 8,
//	,
//		percentage = 13,
		field_count = 9;
}
