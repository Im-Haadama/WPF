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
//		MyLog(debug_trace(5));
//		MyLog(__FUNCTION__ . __CLASS__);
		// add_filter( 'manage_shop_order_posts_custom_column', array(__CLASS__, 'add_my_account_order_actions'), 10, 2 );
		add_filter('woocommerce_admin_order_actions', array(__CLASS__, 'add_order_action'), 10, 2);
		add_filter('order_complete', array(__CLASS__, 'order_complete_wrap'));
		add_action('admin_post_delivery', array(__CLASS__, 'create_delivery_note'));
		add_action( 'woocommerce_view_order', array(__CLASS__, 'show_edit_order'), 10 );
		// https://wordpress.stackexchange.com/questions/357948/how-to-update-the-order-items-while-editing-an-order-on-the-woocommerce-admin-or

		// update siton prices in cart.
		AddAction('woocommerce_order_before_calculate_totals', array(__CLASS__, 'calculate_by_customer_type'), 10, 3);
//		AddAction( 'woocommerce_before_calculate_totals', 'im_woocommerce_update_price', 99 );

		AddAction( 'woocommerce_before_calculate_totals', array(__CLASS__, 'update_cart'), 99 );

//		AddAction('woocommerce_before_calculate_totals', array(__CLASS__, 'update_cart'), 10, 1);
////		add_action( 'woocommerce_before_calculate_totals', array(__CLASS__, 'im_woocommerce_update_price'), 10, 2 );
	}

	static function update_cart(WC_Cart $cart)
	{
		// Trace the cart
		$message = "cart start " . $_SERVER['REMOTE_ADDR'] . " ";
		if (function_exists('get_user_id') and get_user_id() ) {
			$f = new Fresh_Client(get_user_id());
			$message .= "user: " . $f->getName();
		}
		MyLog( $message);

		if (! SqlQuery("select 1")) {
			MyLog ("not connected to db");
			return;
		}
		$C = new Fresh_Client(get_user_id());
		$client_type = $C->customer_type();

		$message = "Cart: ";

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$prod_id = $cart_item['product_id'];
			$variation_id = $cart_item['variation_id'];
			if ( ! ( $prod_id > 0 ) ) {
				$message .= _( "cart - no prod_id" );
				continue;
			}
			$q          = $cart_item['quantity'];
			$f = new Fresh_Product($prod_id);
			if ($f->is_basket()) { $message .= "basket skipped"; continue; }
			$sell_price = Fresh_Pricing::get_price_by_type($prod_id, $client_type, $q, $variation_id );
			//my_log("set " . $sell_price);
			$cart_item['data']->set_sale_price( $sell_price );
			$cart_item['data']->set_price( $sell_price );
			$message .= $prod_id ." " . $q;
		}
		MyLog($message);
//		if (! function_exists('get_user_id')) return $cart;
//
//		if (get_user_id() != 474) return;
//		MyLog(__FUNCTION__. " " . get_user_id());
//		$customer_id = get_user_id();
//		if (! $customer_id) {
//			MyLog( "no customer id" );
//			return;
//		}
//		$f = new Fresh_Client($customer_id);
//		$customer_type = $f->customer_type();
//		MyLog("calculate cart ");
//		foreach( $cart->get_cart() as $key => $item ) {
//			WC()->cart_contents[ $key ]['line_total'] = 22;
////			if ('WC_Order_Item_Product' != get_class($item)) {
////				Mylog (get_class($item));
////				continue;
////			}
//
//			MyLog(StringVar($item['data']));
//			return $cart;
//			// get the WC_Product object
////			$product = $item->get_product();
////
////			// get the quantity
////			$product_quantity = $item->get_quantity();
////
////			if ($customer_type) {
////				$price = Fresh_Pricing::get_price_by_type($product->id, $customer_type);
////
////				$item->set_total($product_quantity * $price);
////			}
//		}
//		return $cart;
	}
	static function calculate_by_customer_type($and_taxes, WC_Order $order)
	{
		MyLog(__FUNCTION__. " " . $order->customer_id);
		$customer_id = $order->customer_id;
		if (! $customer_id) {
			MyLog( "no customer id" );
			return;
		}
		$f = new Fresh_Client($customer_id);
		$customer_type = $f->customer_type();
		MyLog("calculate cart order " . $order->get_id() . " user $customer_id type: $customer_type");
		foreach( $order->get_items() as $item_id => $item ) {
			if ('WC_Order_Item_Product' != get_class($item)) {
				Mylog (get_class($item));
				continue;
			}

			// get the WC_Product object
			$product = $item->get_product();

			// get the quantity
			$product_quantity = $item->get_quantity();

			if ($customer_type) {
				$price = Fresh_Pricing::get_price_by_type($product->id, $customer_type);

				$item->set_total($product_quantity * $price);
			}
		}
	}

	static public function show_edit_order($order_id)
	{
		$order = new Fresh_Order($order_id);
		if ($order->getStatus() != 'wc-on-hold') return;

		print "ניתן לערוך את ההזמנה " . Core_Html::GuiHyperlink("כאן", "/my-account/edit-order/$order_id") . ".";
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
		if (! $O->getDeliveryId()) {
			$fee = $O->getShippingFee();
			// Check if there is delivery fee.
			if (! $fee) {
				MyLog("No delivery fee");
				Fresh::instance()->add_admin_notice("No delivery fee. Add to order before completion");
				// Change back the order status.
				$O->setStatus('wc-processing');
				return false;
			}

			$del_id = Fresh_Delivery::CreateDeliveryFromOrder($order_id, 1);
			$d = new Fresh_Delivery($del_id);
			$admin_email = get_bloginfo('admin_email');
			if (defined('ADMIN_MAIL')) $admin_email = ADMIN_MAIL;

			$d->send_mail($admin_email);
		} else {
			MyLog ("have del: " . $O->getDeliveryId());
		}
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

//add_filter( 'woocommerce_cart_product_subtotal', 'modify_cart_product_subtotal', 10, 4 );
//function modify_cart_product_subtotal( $product_subtotal, $product, $quantity, $cart ) {
//	$product_subtotal = 22;
//	// Add your logic here.
//	// You can use the $cart instead of using the global $woocommerce variable.
//	return $product_subtotal;
//}