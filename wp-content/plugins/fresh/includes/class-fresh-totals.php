<?php
/**
 * Class Fresh_Totals
 * wrapped 28 Jan 2020
 */

class Fresh_Totals {
	private $post_file;
	private $version;
	protected static $_instance = null;

	public function __construct( $post_file ) {

		$this->post_file     = $post_file;
		$this->version       = "1.0";
		$this->nav_menu_name = null;
	}

	public static function instance( $post = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $post );
		}

		return self::$_instance;
	}

	function getShortcodes() {
		//             code                           function                  capablity (not checked, for now).
		return ( array(
			'fresh_totals'         => array( 'Fresh_Totals::totals', 'edit_shop_orders' ),
			'fresh_total_table' => array('Fresh_Totals::total_table', 'edit_shop_orders'),
			'fresh_test' => array("Fresh_Totals::test", null)
		) );
	}

	static function test()
	{
		if (get_user_id() == 1)
		{
			$order = WC_Order_Factory::get_order(12905);
			var_dump($order);
		}
	}
}
