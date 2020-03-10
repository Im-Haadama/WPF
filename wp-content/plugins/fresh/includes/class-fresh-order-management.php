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
	public function __construct($plugin_name = "Fresh", $version = "1.0") {
		$this->plugin_name = $plugin_name;
		$this->version = '1.0';
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	static function handle()
	{
		$operation = GetParam("operation", false, "show_orders");

		print self::handle_operation($operation);
	}

	static function handle_operation($operation)
	{
		switch ($operation) {
			case "show_orders":
				return self::OrdersTable1(array('wc-processing', 'wc-awaiting-shipment'));
		}
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
