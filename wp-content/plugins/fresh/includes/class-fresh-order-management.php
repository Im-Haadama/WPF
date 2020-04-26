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
//		MyLog(__FUNCTION__ . __CLASS__);
		// add_filter( 'manage_shop_order_posts_custom_column', array(__CLASS__, 'add_my_account_order_actions'), 10, 2 );
		add_filter('woocommerce_admin_order_actions', array(__CLASS__, 'add_order_action'), 10, 2);
		add_filter('order_complete', array(__CLASS__, 'order_complete_wrap'));
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

	static public function order_complete_wrap()
	{
		$order_id = GetParam("order_id", true);
		return self::order_complete($order_id);
	}

	static public function add_order_action($actions, $order)
	{
		$O = new Fresh_Order($order->get_id());
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
				'url' => '/wp-content/plugins/fresh/delivery/create-delivery.php?order_id=' . $order->get_id(),
					'name'   => __( 'Delivery', 'woocommerce' ),
					'action' => 'delivery'
				);
				break;
			case "awaiting-shipment":
			case "completed":
				$actions['delivery_note'] = array(
//						'url'    => wp_nonce_url( admin_url( 'admin-post.php?post=' . $order->get_id() . '&action=delivery' ), 'woocommerce-mark-order-status' ),
					'url' => self::get_url($order->get_id()),
					'name'   => __( 'Delivery', 'woocommerce' ),
					'action' => 'delivery'
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

	static function get_url($order_id)
	{
//		$order = new Fresh_Order($order_id);
//		if ($order->getShippingFee())
			return '/wp-content/plugins/fresh/delivery/get-delivery.php?order_id=' . $order_id;

	}

	static function order_complete($order_id, $force = false)
	{
		MyLog(__FUNCTION__ . " $order_id");
		// Order is complete.
		// If no delivery note, create one with 100% supplied.
		$O = new Fresh_Order($order_id);
//		print "fee= " . $O->getShippingFee() . "<br/>";
		if (! $O->getDeliveryId()) {
			$fee = $O->getShippingFee();
			MyLog($fee);
			// Check if there is delivery fee.
			if (! $fee) {
				MyLog("No delivery fee");
				Print "Not delivery fee. Add to order before completion";
			}

			// if ($O->)
//			print "no del id</br>";
			$del_id = Fresh_Delivery::CreateDeliveryFromOrder($order_id, 1);
//			if ($del_id > 0)
//				print "$del_id created<br/>";
		} else {
			MyLog ("have del: " . $O->getDeliveryId());
		}
//		else {
//			print "has del: " . $O->getDeliveryId() ."<br/>";
//		}
		return true;
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
