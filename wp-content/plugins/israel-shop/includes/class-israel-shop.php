<?php


class Israel_Shop
{
	static private $_instance;
	protected $auto_loader;
	protected $zones;
	public $version = '1.0';

	/**
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Israel_Shop constructor.
	 */
	public function __construct($plugin_name)
	{
		$this->define_constants();

		// require_once FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php';
		// if (! function_exists("InfoGet")) die(FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php');
		$this->auto_loader = new Core_Autoloader(ISRAEL_ZONE_ABSPATH);

		$this->zones = new Israel_Zones();
		self::$_instance = $this;

		$this->init_hooks();
	}

	/**
	 * @return Israel_Shop
	 */
	public static function instance(): Israel_Shop {
		return self::$_instance;
	}

	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'ISRAEL_ZONE_ABSPATH', dirname( ISRAEL_ZONES_PLUGIN_FILE ) . '/' );
		$this->define( 'ISRAEL_PLUGIN_BASENAME', plugin_basename( ISRAEL_ZONES_PLUGIN_FILE ) );
		$this->define( 'ISRAEL_VERSION', $this->version );
		$this->define( 'ISRAEL_INCLUDES', ISRAEL_ZONE_ABSPATH . '/includes/' );
		$this->define( 'ISRAEL_DELIMITER', '|' );
		$this->define( 'ISRAEL_LOG_DIR', $upload_dir['basedir'] . '/ISRAEL-logs/' );
		$this->define( 'ISRAEL_INCLUDES_URL', plugins_url() . '/ISRAEL/includes/' ); // For js
		$this->define( 'WC_URL', plugins_url() . '/woocommerce/' ); // For css

		$this->define( 'FLAVOR_INCLUDES_URL', plugins_url() . '/flavor/includes/' ); // For js
		$this->define( 'FLAVOR_INCLUDES_ABSPATH', plugin_dir_path(__FILE__) . '../../flavor/includes/' );  // for php
	}

	function init()
	{
		Israel_Database::Upgrade(self::instance()->get_version());
		$this->zones->run(5);
	}

	function init_hooks()
	{
		// Admin menu
		add_action('admin_menu', __CLASS__ . '::admin_menu');
	}

	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	static function admin_menu()
	{
		$menu = new Core_Admin_Menu();

		$menu->AddMenu('Israel', 'Israel', 'shop_manager', 'israel', __CLASS__ . '::general_settings');
	}

	static function general_settings()
	{
		print 1/0;

		$result = "";
		$tabs = [];
		$args = [];
//		$args["post_file"] = self::getPost();

		$tab = GetParam("tab", false, "baskets");
		$url = GetUrl(1) . "?page=settings&tab=";

		$cities_url = $url . "cities";

		$tabs["cities"] = array(
			"Cities",
			$cities_url,
			"llllll"
			//Fresh_Basket::settings($cities_url, $args)
		);

//		$tabs["suppliers"] = array(
//			"Suppliers",
//			$url . "suppliers",
//			Fresh_Suppliers::admin_page()
//			//Fresh_Suppliers::SuppliersTable()
//		);
//
//		$tabs["missing_pictures"] = array(
//			"Missing Pictures",
//			$url . "missing_pictures",
//			Fresh_Catalog::missing_pictures()
//		);

//		array_push( $tabs, array(
//			"workers",
//			"Workers",
//			self::company_workers( $company, $args )
//		) );

		$args["btn_class"] = "nav-tab";
		$args["tabs_load_all"] = true;
		$args["nav_tab_wrapper"] = "nav-tab-wrapper woo-nav-tab-wrapper";

		$result .= Core_Html::NavTabs($tabs, $args);
		$result .= $tabs[$tab][2];

		print $result;
		print 1/0;
	}
}