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

	public function init_hooks() {
		// add_filter( 'manage_shop_order_posts_custom_column', array(__CLASS__, 'add_my_account_order_actions'), 10, 2 );
//		add_action('admin_post_delivery', array(__CLASS__, 'create_delivery_note'));
		add_action('woocommerce_view_order', array(__CLASS__, 'show_edit_order'), 10 );
		add_action('wp_ajax_woocommerce_calc_line_taxes', array($this, 'update_prices'), 9);
		add_action('admin_notices', array($this, 'delivered_previous_days'));
	}

	function delivered_previous_days() {
		FreshLog(__FUNCTION__);
		$debug = 0;
		$result = "Marking orders of yesterday delivered:\n";
		$ids    = SqlQueryArrayScalar( "SELECT * FROM `wp_posts` 
WHERE (post_status = 'wc-awaiting-shipment' or post_status = 'wc-processing') 
and curdate() > order_mission_date(id)" );

		$message = "";
		if ( ! $ids or ! count( $ids ) ) {
			if ($debug) MyLog("No del found");
			return;
		}

		foreach ( $ids as $id ) {
			$order = new Finance_Order( $id );
			if ($order->getStatus() == 'wc-processing') continue;
			FinanceLog("adding $id");
			$order->delivered( $message );
			$result .= "Order $id $message\n";
		}

		if ( strlen( $result ) > 39) {
			$class   = 'notice notice-info';

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $result ) );
		}
	}



	// Change of function calc_line_taxes() from wp-content/plugins/woocommerce/includes/class-wc-ajax.php
	function update_prices()
	{
		check_ajax_referer( 'calc-totals', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'], $_POST['items'] ) ) {
			wp_die( -1 );
		}

		$order_id           = absint( $_POST['order_id'] );
		$calculate_tax_args = array(
			'country'  => isset( $_POST['country'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['country'] ) ) ) : '',
			'state'    => isset( $_POST['state'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['state'] ) ) ) : '',
			'postcode' => isset( $_POST['postcode'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : '',
			'city'     => isset( $_POST['city'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['city'] ) ) ) : '',
		);

		// Parse the jQuery serialized items.
		$items = array();
		parse_str( wp_unslash( $_POST['items'] ), $items ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Save order items first.
		wc_save_order_items( $order_id, $items );

		// Grab the order and recalculate taxes.
		$order = wc_get_order( $order_id );

		// My changes
		// Start
		// We need the customer. For now take it from the saved info. (The order must be saved with the client id).
		$O = new Fresh_Order($order_id);
		$customer_type = $O->getCustomerType();
//		MyLog("ct=" . $customer_type);

		foreach ($order->get_items() as $item_id => &$item) {
			$q = $item->get_quantity();
			$prod_id = $item->get_product_id();
			$price = Fresh_Pricing::get_price_by_type($prod_id, $customer_type);

//			$item->set_subtotal( 50 ); // Regular price.
			$item->set_total( $price * $q ); // Discount price.
		}
		// End

		$order->calculate_taxes( $calculate_tax_args );
		$order->calculate_totals( false );
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-items.php';
		wp_die();
	}

	static public function show_edit_order($order_id)
	{
		$order = new Fresh_Order($order_id);
		if ($order->getStatus() != 'wc-on-hold') return;

		print "ניתן לערוך את ההזמנה " . Core_Html::GuiHyperlink("כאן", "/my-account/edit-order/$order_id") . ".";
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