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

	// From post.php
	static function handle_operation($operation)
	{
		if ($operation){
			$args = self::Args();

			$result = apply_filters( $operation, '', null, $args );
			if ( $result ) 	return $result;
		}
		return false;
	}

	static function Args($type = null)
	{
		$args = [];
		$args["post_file"] = plugin_dir_url(dirname(__FILE__)) . "post.php";
		// $args["edit"] = true;

		return $args;
	}

	/**
	 *
	 * Israel_Shop constructor.
	 */
	public function __construct()
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
		if (! self::$_instance)
			self::$_instance = new Israel_Shop();
		return self::$_instance;
	}

	static public function getPost()
	{
		return '/wp-content/plugins/israel-shop/post.php';
	}

	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'ISRAEL_ZONE_ABSPATH', dirname( ISRAEL_ZONES_PLUGIN_FILE ) . '/' );
		$this->define( 'ISRAEL_PLUGIN_BASENAME', plugin_basename( ISRAEL_ZONES_PLUGIN_FILE ) );
		$this->define( 'ISRAEL_VERSION', $this->version );
		$this->define( 'ISRAEL_INCLUDES', ISRAEL_ZONE_ABSPATH . '/includes/' );
		$this->define( 'ISRAEL_DELIMITER', '|' );
		$this->define( 'ISRAEL_LOG_DIR', $upload_dir['basedir'] . '/israel-logs/' );
		$this->define( 'ISRAEL_INCLUDES_URL', plugins_url() . '/israel-shop/includes/' ); // For js
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

		add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

		add_action('vat_add_category', array($this, 'vat_add_category'));
		add_action('vat_remove_category', array($this, 'vat_remove_category'));
		AddAction('israel_data_update', array($this, 'data_update'));
	}

	public function data_update()
	{
		return Core_Data::data_update('cities');
	}

	function vat_add_category()
	{
		$new_categ = GetParam("new_categ");
		$current = explode(",", InfoGet("fresh"));
		if (! in_array($new_categ, $current)) array_push($current, $new_categ);
		InfoUpdate("fresh", CommaImplode($current, false, ",") );
		return true;
	}

	function vat_remove_category()
	{
		$result = "";
		$remove_categ = rtrim(GetParam("categ"));
		$current = explode(",", InfoGet("fresh"));
		if (($key = array_search($remove_categ, $current))) {
			$c = new Fresh_Category($key);
//			$result .= "Removing category " . $c->getName();
			unset( $current[ $key ] );
		}
		InfoUpdate("fresh", CommaImplode($current,false, ",") );
		return $result;
	}

	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public function admin_scripts() {
		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
		wp_enqueue_script( 'data', $file);

		$file = ISRAEL_INCLUDES_URL . 'js/admin.js';
		wp_register_script( 'israel_admin', $file );
		wp_enqueue_script('israel_admin');

	}

	static function admin_menu()
	{
		$menu = new Core_Admin_Menu();

//		$menu->AddMenu('Israel', 'Israel', 'shop_manager', 'israel', __CLASS__ . '::general_settings');

		$menu->AddSubMenu("edit.php?post_type=product", "edit_shop_orders",
			array('page_title' => 'VAT', 'function' => array("Israel_Shop" , 'SettingsWrap' )));
	}

	static function SettingsWrap()
	{
		$result = Core_Html::GuiHeader(1, __("Categories that are vat free"));
		$operation = GetParam("operation", false, null);
		if ($operation)
			$result .= apply_filters($operation, "");
		foreach (explode(",", InfoGet("fresh")) as $categ) {
			$c = new Fresh_Category($categ);
			$result .= Core_Html::GuiHyperlink("X", AddToUrl(array("operation"=>"vat_remove_category", "categ" => $categ))). " " .$c->getName() . "<br/>";
		}

		$result .= "<div>";
		$result .= Core_Html::GuiHeader(2, "Select category to add");
		$result .= Fresh_Category::Select("new_categ");
		$result .= Core_Html::GuiButton("btn_add", "Add", array("action" => "vat_add_category('" . Israel_Shop::getPost() . "')"));
		$result .= "</div>";

		print $result;
	}
}