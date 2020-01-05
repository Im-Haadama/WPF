<?php

/**
 * Shortcodes
 *
 * @package WooCommerce/Classes
 * @version 3.2.0
 */

defined( 'FRESH_INCLUDES' ) || exit;

/**
 * Fresh Shortcodes class.
 */
class Fresh_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'fresh_management'           => __CLASS__ . '::fresh_management',
			'fresh_suppliers'            => __CLASS__ . '::suppliers', // [fresh_suppliers]
			'fresh_control'            => __CLASS__ . '::packing_control', // [fresh_suppliers]
			'fresh_orders'    => __CLASS__ . '::orders',
			'fresh_account_status'    => __CLASS__ . '::fresh_account_status',
			'fresh_inventory'    => __CLASS__ . '::inventory',
			/// Personal area
			'fresh_client_balance'    => __CLASS__ . '::client_balance',
			'fresh_open_orders'    => __CLASS__ . '::open_orders',
			'fresh_delivery'    => __CLASS__ . '::delivery',
			'fresh_client_archive'    => __CLASS__ . '::client_archive'

		);

		foreach ( $shortcodes as $shortcode => $function ) {
//			 print "{$shortcode}_shortcode_tag" . " ". $shortcode ." " . $function . "<br/>";
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts     Attributes. Default to empty array.
	 * @param array    $wrapper  Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'fresh',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		// @codingStandardsIgnoreStart
		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
		// @codingStandardsIgnoreEnd

		return ob_get_clean();
	}

	public static function delivery()
	{
		$user_id = get_user_id(true);
		$last_delivery = Fresh_Delivery::CustomerLast($user_id);
		$delivery_id = get_param("id", false, $last_delivery);
		if (! $delivery_id) return __("No deliveries yet");

		$delivery = new Fresh_Delivery($delivery_id);
		return $delivery->CustomerView();
	}

	public static function fresh_management( $atts ) {
		return self::shortcode_wrapper( array( 'Fresh_Shortcode_Management', 'output' ), $atts );
	}

	public static function inventory( $atts ) {
		$atts = [];
		foreach ($_GET as $param => $value)
		{
			$atts[$param] = $value;
		}

		return self::shortcode_wrapper( array( 'Fresh_Inventory', 'handle' ), $atts );
	}

	public static function suppliers( $atts ) {
		$operation = get_param("operation", false, "show_status");
		if (get_user_id(true))
			print Fresh::instance()->handle_operation($operation);
	}

	public static function inventory_count( $atts ) {
		$operation = get_param("operation", false, "inventory");
		if (get_user_id(true))
			print Fresh::instance()->handle_operation($operation);
	}

	public static function packing_control( $atts ) {
		$operation = get_param("operation", false, "inventory");
		if (get_user_id(true))
			print Fresh_Packing::instance()->handle_operation($operation);
	}

	public static function orders( $atts ) {
		$operation = get_param("operation", false, "orders");
		if (get_user_id(true) and im_user_can("show_orders"))
			print Fresh_Order::instance()->handle_operation($operation);
		else
			print "no permissions "  . __FUNCTION__;
	}

	public static function client_balance( $atts ) {
		$operation = get_param("operation", false, "client_balance");
		return Fresh_Client_Views::handle_operation($operation);
	}

	public static function open_orders( $atts ) {
		$operation = get_param("operation", false, "open_orders");
		return Fresh_Client_Views::handle_operation($operation);
	}

	public static function client_archive( $atts ) {
		$operation = get_param("operation", false, "client_archive");
		return Fresh_Client_Views::handle_operation($operation);
	}

	public static function output( $atts ) {
		$status = new Fresh_Status();

		$operation = get_param("operation", false, "show_status");
		if (! get_user_id(true)) { print "Must login"; return; };

		switch ($operation){
			case "show_status":
				print $status->status();
				return;

			case "show_orders":
				print $status->ShowOrders(get_param("status", false, 'wc-pending'));
				return;

			case "show_supplies":
				print $status->SupplyTable(get_param("status", false, 1));
				return;

		}
		// handle actions / operations.
		print Fresh::instance()->handle_operation($operation);
	}
}