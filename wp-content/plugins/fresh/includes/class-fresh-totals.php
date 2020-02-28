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

	public function enqueue_scripts() {
//		print "<script>let focus_post_url = \"" . self::getPost() . "\"; </script>";

//		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
//		wp_enqueue_script( 'data', $file, null, $this->version, false );
//
//		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
//		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );
//
//		$file = FOCUS_INCLUDES_URL . 'focus.js';
//		wp_enqueue_script( 'focus', $file, null, $this->version, false );
//
//		$file = FLAVOR_INCLUDES_URL . 'core/gem.js';
//		wp_enqueue_script( 'gem', $file, null, $this->version, false );
	}

	function getShortcodes() {
		//             code                           function                  capablity (not checked, for now).
		return ( array(
			'fresh_totals'         => array( 'Fresh_Totals::totals', 'edit_shop_orders' ),
			'fresh_total_table' => array('Fresh_Totals::total_table', 'edit_shop_orders'),
			'fresh_test' => array("Fresh_Totals::test", null)
		) );
	}

	static function total_table($args)
	{
		$needed_products = array();
		Fresh_Order::CalculateNeeded( $needed_products );

		var_dump($needed_products);

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

//
//		print "<style>
//table {
//    font-family: arial, sans-serif;
//    border-collapse: colb
//
//td, th {
//    border: 1px solid #dddddd;
//    text-align: right;
//    padding: 8px;
//}
//
//tr:nth-child(even) {
//    background-color: #dddddd;
//}
//</style>";
//
