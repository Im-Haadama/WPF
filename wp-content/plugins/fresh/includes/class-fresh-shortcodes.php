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
			'Finance_Suppliers'    => __CLASS__ . '::suppliers', // [fresh_suppliers]
			'fresh_control'        => __CLASS__ . '::packing_control', // [fresh_suppliers]
//			'fresh_orders'    => __CLASS__ . '::orders',
			'fresh_account_status' => __CLASS__ . '::fresh_account_status',
			'Finance_Inventory'    => __CLASS__ . '::inventory',
			/// Personal area
			'fresh_client_balance' => __CLASS__ . '::client_balance',
			'fresh_open_orders'    => __CLASS__ . '::open_orders',
			'fresh_delivery'       => __CLASS__ . '::delivery',
			'fresh_client_archive' => __CLASS__ . '::client_archive',
			'fresh_deliveries'     => __CLASS__ . '::deliveries'
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
//	public static function shortcode_wrapper(
//		$function,
//		$atts = array(),
//		$wrapper = array(
//			'class'  => 'fresh',
//			'before' => null,
//			'after'  => null,
//		)
//	) {
//		ob_start();
//
//		// @codingStandardsIgnoreStart
//		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
//		call_user_func( $function, $atts );
//		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
//		// @codingStandardsIgnoreEnd
//
//		return ob_get_clean();
//	}

	public static function delivery()
	{
		$user_id = get_user_id(true);
		$last_delivery = Fresh_Delivery::CustomerLast($user_id);
		$delivery_id = GetParam("id", false, $last_delivery);
		if (! $delivery_id) return __("No deliveries yet");

		$operation = GetParam("operation", false, false);
		if ($operation) do_action($operation, $delivery_id);

		$edit = ("edit"== $operation);

		$delivery = new Fresh_Delivery($delivery_id);
		$result = $delivery->CustomerView($edit);

		if (! $edit and im_user_can("edit_shop_orders") and ! $delivery->paid()) {
			$result  .= Core_Html::GuiHyperlink( "Edit delivery note", AddToUrl( "operation", "edit" ) );
			$user_id = $delivery->getUserId();
			$user    = new Fresh_Client( $user_id );
			if ( $user->customer_type())
				$result .= Core_Html::GuiHyperlink( "Update by customer type", AddToUrl( "operation", "update_by_customer_type" ) );
		}

		return $result;
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

		return Finance_Inventory::handle();
	}

	public static function suppliers( $atts ) {
		$operation = GetParam("operation", false, "show_status");
		if (get_user_id(true))
			print Fresh::instance()->handle_operation($operation);
	}

	public static function inventory_count( $atts ) {
		$operation = GetParam("operation", false, "inventory");
		if (get_user_id(true))
			print Fresh::instance()->handle_operation($operation);
	}

	public static function packing_control( $atts ) {
		$operation = GetParam("operation", false, "inventory");
		if (get_user_id(true))
			print Fresh_Control::handle($operation);
	}

	public static function orders( $atts ) {
		$operation = GetParam("operation", false, "orders");
		if (get_user_id(true) and im_user_can("show_orders"))
			print Fresh_Order::instance()->handle_operation($operation);
		else
			print "no permissions "  . __FUNCTION__;
	}

	public static function client_balance( $atts ) {
		$u = new Fresh_Client(get_user_id());
		return  $u->balance();
	}

	public static function open_orders( $atts ) {
		return Fresh_Client_Views::open_orders(get_user_id());
	}

	public static function client_archive( $atts ) {
		$operation = GetParam("operation", false, "client_archive");
		return Fresh_Client_Views::handle_operation($operation);
	}

	public static function output( $atts ) {
		$status = new Fresh_Status();

		$operation = GetParam("operation", false, "show_status");
		if (! get_user_id(true)) { print "Must login"; return; };

		switch ($operation){
			case "show_status":
				print $status->status();
				return;

			case "show_orders":
				print $status->ShowOrders(GetParam("status", false, 'wc-pending'));
				return;

			case "show_supplies":
				print $status->SupplyTable(GetParam("status", false, 1));
				return;

		}
		// handle actions / operations.
		print Fresh::instance()->handle_operation($operation);
	}

	static function deliveries()
	{
//		$result = '<div class="elementor-element elementor-element-1509c1bd elementor-widget elementor-widget-heading" data-id="1509c1bd" data-element_type="widget" data-widget_type="heading.default">';
		$result  = '';

		$wc_zones = WC_Shipping_Zones::get_zones();

		foreach ($wc_zones as $zone_id => $zone)
		{
			$args["class"] = 'elementor-heading-title elementor-size-default';
			$result .= Core_Html::GuiHeader(1, $zone['zone_name'], $args);
			$time = ""; foreach ($zone['shipping_methods'] as $method) $time .= $method->title . ", ";
			$result .= Core_Html::GuiHeader(3, __("Next deliveries", 'fresh') . ': ' .trim($time, ", "));
			if (TableExists("cities")) {
				$result .= Core_Html::GuiHeader( 3, __( "Cities", 'fresh' ) );
				$cities = SqlQueryArrayScalar("select city_name from im_cities where zone = $zone_id");
				foreach ($cities as $city) $result .= $city . ", ";
				$result = trim($result, ", ") . ".<br/>";
			}
			$info = SqlQuerySingleAssoc("select default_rate, min_order from wp_woocommerce_shipping_zones where zone_id = $zone_id");
			$result .= __("Minimum order", 'fresh') . ": " . $info['min_order'] . get_woocommerce_currency_symbol() . ".<br/>";
			$result .= __("Shipping price", 'fresh') . ": " . $info['default_rate'] . get_woocommerce_currency_symbol() . ".<br/>";
			$result .= "<br/>";

		}
//		$result .= "</div>";
		return $result;
	}
}

//if (! defined('SHOW_PROD_NO_PIC')) {
//	add_filter( 'woocommerce_product_query_meta_query', 'products_with_thumbs', 10 );
//}

function products_with_thumbs()
{
	return array(
		'relation' => 'OR',
		array('key' => '_thumbnail_id',
		'compare' => '>',
		'value' => '0')
	);
}