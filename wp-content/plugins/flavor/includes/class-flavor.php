<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

/**
 * Class Flavor
 */
class Flavor {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      $auto_loader Maintains and registers all hooks for the plugin.
	 */
	protected $auto_loader;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.5';

	/**
	 * @var
	 */
	private $plugin_name;

	private $database;

	private $loader;

	protected $admin_notices;

	/**
	 * The single instance of the class.
	 *
	 * @var Flavor
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * @var null
	 */
	protected $nav = null;

	/**
	 * flavor instance.
	 *
	 */
	public $flavor = null;

	/**
	 * @return mixed
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Main Flavor Instance.
	 *
	 * Ensures only one instance of Flavor is loaded or can be loaded.
	 *
	 * @static
	 * @return Flavor - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "Flavor" );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		die( __FUNCTION__ . __( 'Cloning is forbidden.', 'flavor' ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		flavor_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'flavor' ), '2.1' );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'payment_gateways', 'shipping', 'mailer', 'checkout' ), true ) ) {
			return $this->$key();
		}
	}

	/**
	 * WooCommerce Constructor.
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		$this->auto_loader = new Core_Autoloader(FLAVOR_ABSPATH);
		$this->loader = Core_Loader::instance();
		$this->init_hooks();

//		if (get_user_id() == 1)
//			print "loading flavor";
		do_action( 'flavor_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		$this->database = new Flavor_Database();
		$this->database->install($this->version);
		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );
//		add_action('wp', 'unlogged_guest_posts_redirect');
		add_action('data_save_new', array('Core_Data', 'data_save_new'));
		add_action('admin_menu', array($this, 'admin_menu'));

		GetSqlConn( ReconnectDb() );

		// Register tables that can be fetched.
		$i = Core_Db_MultiSite::getInstance();
//		foreach (array("multisite", "missions", "woocommerce_shipping_zones") as $table)
		$i->AddTable("multisite");
		$i->AddTable("options", "option_id" );

		AddAction( 'admin_enqueue_scripts', array($this, 'admin_scripts' ));
		Core_Gem::getInstance()->init_hooks($this->loader);
		Flavor_Org_Views::instance()->init_hooks($this->loader);
		Flavor_Mission::init_hooks();
		Core_Data::init_hooks($this->loader);
		add_action( 'admin_notices', array($this, 'admin_notices') );
	}

	public function admin_menu()
	{
		$menu = Core_Admin_Menu::instance();

		$menu->AddSubMenu('missions', 'edit_shop_orders',
			array('page_title' => 'Missions',
			      'menu_title' => 'Missions',
			      'menu_slug' => 'missions',
			      'function' => 'Flavor_Mission::missions'));

		if (TableExists("mission_types"))
			self::AddTop('missions',"Missions", '/wp-admin/admin.php?page=missions');

		self::AddTop('orders', 'Orders', '/wp-admin/edit.php?post_type=shop_order&post_status=wc-processing');
		self::AddTop('orders_all', 'All orders', '/wp-admin/edit.php?post_type=shop_order', 'orders');
		self::AddTop('orders_print', 'Print', '/wp-admin/admin.php?page=printing', 'orders');
	}

	static function AddTop($id, $title, $href, $parent = null)
	{
		$menu = Core_Admin_Menu::instance();

		$menu->AddTop($id, __($title, "e-fresh"), $href, $parent);
	}

	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['wpf'] = esc_html__( 'WPF', 'wpf' );
		return $settings_tabs;
	}

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function settings_tab() {
		$result = "";
		foreach (self::plugins_setting() as $module_name => $setting) {
			$result .= Core_Html::GuiHeader(2, $module_name);
			$result .= $setting;
		}

		print  $result;
	}

	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_settings() {
		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * Get all the settings for this plugin for @param string $current_section
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 * @see woocommerce_admin_fields() function.
	 *
	 */
	public function get_settings($current_section = '')
	{
		print 1/0;
		switch ($current_section){
			case "Fresh":
				$settings = "lalalala";
				$id = 'Fresh';
				break;
			default:
				$settings = "noting selected";
				$id = 'aa';
				break;

		}
		return apply_filters('woocommerce_get_settings_' . $id, $settings, $current_section);
		// Get loop of all Pages.
		$args = array(
			'sort_column'  => 'post_title',
			'hierarchical' => 1,
			'post_type'    => 'page',
			'post_status'  => 'publish'
		);
		$pages = get_pages( $args );

		// Create data array.
		$pages_array = array( 'none' => '' );

		// Loop through pages.
		foreach ( $pages as $page ) {
			$pages_array[ $page->ID ] = $page->post_title;
		}

		// Go Pro.
		$go_pro = '';

//		if ( ! function_exists( 'wpf_pro_all_settings' ) ) {
//			$go_pro = ' | <a href="https://deviodigital.com/product/delivery-drivers-for-woocommerce-pro" target="_blank" style="font-weight:700;">' . esc_html__( 'Go Pro', 'wpf' ) . '</a>';
//		}

		$settings = array(
			// Section title.
			'wpf_settings_section_title' => array(
				'name' => esc_html__( 'Wordpress-F plugins for Wordpress', 'wpf' ),
				'type' => 'title',
				'desc' => esc_html__( 'Brought to you by', 'wpf' ) . " " . Core_Html::GuiHyperlink("WP-F", "https://wordpress-f.com/"), // ' <a href="https://www.deviodigital.com" target="_blank">Devio Digital</a>' . $go_pro,
				'id'   => 'wpf_settings_section_title'
			),
			// Dispatch phone number.
			'dispatch_phone_number' => array(
				'name' => esc_html__( 'Dispatch phone number', 'wpf' ),
				'type' => 'text',
				'desc' => esc_html__( 'Allow your drivers to call if they have questions about an order.', 'wpf' ),
				'id'   => 'wpf_settings_dispatch_phone_number'
			),
//			// Google Maps API key.
//			'google_maps_api_key' => array(
//				'name' => esc_html__( 'Google Maps API key', 'wpf' ),
//				'type' => 'text',
//				'desc' => esc_html__( 'Add a map to the order directions for your drivers.', 'wpf' ),
//				'id'   => 'wpf_settings_google_maps_api_key'
//			),
//			// Driver ratings.
//			'driver_ratings' => array(
//				'name' => esc_html__( 'Driver ratings', 'wpf' ),
//				'type' => 'select',
//				'desc' => esc_html__( 'Add driver details with delivery star ratings to order details page.', 'wpf' ),
//				'id'   => 'wpf_settings_driver_ratings',
//				'options' => array(
//					'yes' => 'Yes',
//					'no'  => 'No',
//				),
//			),
//			// Driver phone number.
//			'driver_phone_number' => array(
//				'name' => esc_html__( 'Driver phone number', 'wpf' ),
//				'type' => 'select',
//				'desc' => esc_html__( 'Add a button for customers to call driver in the driver details.', 'wpf' ),
//				'id'   => 'wpf_settings_driver_phone_number',
//				'options' => array(
//					'yes' => 'Yes',
//					'no'  => 'No',
//				),
//			),
//			// Section End.
			'section_end' => array(
				'type' => 'sectionend',
				'id'   => 'wpf_settings_section_end'
			),
		);
		return apply_filters( 'wpf_settings', $settings );
	}


/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( isset($error['type']) and in_array( $error['type'], array(
			E_ERROR,
			E_PARSE,
			E_COMPILE_ERROR,
			E_USER_ERROR,
			E_RECOVERABLE_ERROR
		) ) ) {
			$logger = flavor_get_logger();
			$logger->critical(
			/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'flavor' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'flavor_shutdown_error', $error );
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'FLAVOR_ABSPATH', dirname( FLAVOR_PLUGIN_FILE ) . '/' );
		$this->define( 'FLAVOR_PLUGIN_BASENAME', plugin_basename( FLAVOR_PLUGIN_FILE ) );
		$this->define( 'FLAVOR_VERSION', $this->version );
		$this->define( 'FLAVOR_URL', plugins_url() . '/flavor/' ); // For languages
		$this->define( 'FLAVOR_INCLUDES_URL', plugins_url() . '/flavor/includes/' ); // For js
		$this->define( 'FLAVOR_INCLUDES_ABSPATH', plugin_dir_path(__FILE__) . '../../flavor/includes/' );  // for php
		$this->define( 'FLAVOR_DELIMITER', '|' );
		$this->define( 'FLAVOR_LOG_DIR', $upload_dir['basedir'] . '/flavor-logs/' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string $name Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
		}
	}

	static function static_handle_show()
	{
		$instance = self::instance();
		$operation = GetParam("operation", false, "default");
		$instance->handle_operation($operation);
	}

	/**
	 * @param $operation
	 *
	 * @return string|void`
	 */
	function handle_operation($operation) {
		$ignore_list = array("operation");
		$args = GetParams($ignore_list);
		$args["post_file"] = Flavor::getPost();

		try {
			do_action( $operation, $args );
		} catch (Exception $e) {
			print $e;
			return false;
		}
		return true;
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		if (! class_exists('Core_Autoloader'))
			require_once FLAVOR_INCLUDES_ABSPATH . 'core/class-core-autoloader.php';

		require_once FLAVOR_INCLUDES_ABSPATH . 'core/fund.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/data/sql.php';
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/gui/inputs.php';
		// require_once FLAVOR_INCLUDES . 'core/data/data.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/wp.php';

		/**
		 * Interfaces.
		 */

		/**
		 * Abstract classes.
		 */

		/**
		 * Core classes.
		 */
		include_once FLAVOR_INCLUDES_ABSPATH . 'core/class-core-shortcodes.php';
		include_once FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php';


		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-data-store.php';

		/**
		 * REST API.
		 */
//		include_once WC_FLAVOR_INCLUDES . 'includes/legacy/class-wc-legacy-api.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-api.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-auth.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-register-wp-admin-settings.php';

	}

	/**
	 *
	 */
	public function include_template_functions() {
//		include_once WC_FLAVOR_INCLUDES . 'includes/flavor-template-functions.php';
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_flavor_init' );
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
		add_action( 'woocommerce_settings_tabs_wpf', __CLASS__ . '::settings_tab' );
		add_action( 'woocommerce_update_options_wpf', __CLASS__ . '::update_settings' );
		// Add custom type.
		add_action( 'woocommerce_admin_field_custom_type', __CLASS__ . '::output_custom_type', 10, 1 );

		// Set up localisation.
		$this->load_plugin_textdomain();
		$shortcodes = Core_Shortcodes::instance();
		$shortcodes->add(array('flavor'  => array(__CLASS__ . '::static_handle_show', "read"),
			'log_viewer' => array('Core_Logger::log_viewer', 'edit_shop_orders'),
			'check_system' => array(__CLASS__ . '::check_system', null),
			'flavor_displayname' => array(array($this, 'display_name'), null)));

		// Init action.
		do_action( 'flavor_init' );
//		add_action( 'admin_bar_menu', 'modify_admin_bar', 200 );

		$url = plugins_url() . '/flavor/assets/css/';
		wp_register_style( 'flavor_styles', $url . 'modal.css', array(), $this->version );
		wp_enqueue_style('flavor_styles');

		wp_register_style( 'table_styles', $url . 'tables.css', array(), $this->version );
		wp_enqueue_style('table_styles');

////		wp_register_style( 'woocommerce_admin_styles', WC_URL . '/assets/css/admin.css', array(), WC_VERSION );
//
//		wp_enqueue_style('woocommerce_admin_styles');

		self::admin_menu();
		add_action( 'admin_bar_menu', array(Core_Admin_Menu::instance(), 'do_modify_admin_bar'), 200 );
	}

	static public function display_name()
	{
		$f = new Fresh_Client(get_user_id());
		return  $f->getName();
	}

	static public function check_system()
	{
		$result = "שלום<br/>";
		$result .= SqlQuerySingleScalar("select \"אהא\"") . "<br/>";
		$result .= SqlQuery("SET collation_connection = utf8_general_ci;") . "<br/>";
		$conn = GetSqlConn();
		$conn->set_charset("utf8");
		// $result .= sql_query()
		$result .= SqlQuerySingleScalar("select post_title from wp_posts where id = 14");

//		ob_start();
//		print phpinfo();
//		$result .= ob_get_contents();

		return $result;
	}

	/**
	 *
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'flavor' );
		$domain = 'e-fresh';

		$file = dirname( FLAVOR_PLUGIN_FILE ) . "/languages/e-fresh-$locale.mo";
		return load_textdomain( 'e-fresh', $file );
	}

	/**
	 *
	 */
	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		$this->define( 'FLAVOR_TEMPLATE_PATH', $this->template_path() );
	}

	/**
	 * @return mixed
	 */
	public function template_path() {
		return apply_filters( 'flavor_template_path', 'flavor/' );
	}

	/**
	 *
	 */
	public function run() {
//		$this->loader->run();
	}

	public function getNav()
	{
		if ($this->nav) return $this->nav;
		if (function_exists("get_user_id")) {
			$this->nav = new Core_Nav("management." . get_user_id());
			return $this->nav;
		} else {
			print "not connected";
		}
		return null;
	}
//	/**
//	 * @return string|null
//	 */
	public function getNavName()
	{
		if ($nav = $this->getNav()) return $nav->getNavMenuName();
		die (__FILE__ . ':' . __LINE__);
	}
//

	/**
	 * used by template to decide if to load management css.
	 * @return bool
	 * if one of ours shortcodes are used.
	 */
	static public function isManagementPage() {
		global $post;
		if ($post)
			return ( strstr( $post->post_content, '[fresh' ) ||
			         strstr( $post->post_content, '[focus' ) ||
			         strstr( $post->post_content, '[salary' ) ||
			         strstr( $post->post_content, '[flavor' ) ||
			         strstr( $post->post_content, '[suppliers' ) ||
			         strstr( $post->post_content, '[finance' ) );
		return false;

	}

	static public function plugins_setting()
	{
		$sections = [];
		foreach (array("Fresh", "Finance", "Flavor", "Focus") as $plugin)
		{
			if (class_exists($plugin)){ // Todo: need to check permissions
				$call = array($plugin, "settingPage");
				if (is_callable($call)){
					$section = call_user_func($call);
					$sections[$plugin] = $section;
				}
			}
		}

		return $sections;
	}

	static function getPost($action = null)
	{
//		return plugin_dir_url(dirname(__FILE__)) . "post.php"; // Physical file.
		$result = "/wp-content/plugins/flavor/post.php";
		if ($action)
			$result .= "?operation=$action&nonce=" . wp_create_nonce($action);

		return $result;
	}

	static public function SettingPage()
	{
		$result = "";
//		$module_list = array( "Flavor" => array());

//		$result .= self::ClassSettingPage($module_list);

		return $result;
	}

	static function ClassSettingPage($module_list)
	{
		$debug = (get_user_id() == 1);

		$u = new Core_Users();
		$result = "";
		foreach ($module_list as $item => $sub_menu_items){
			if (! isset($sub_menu_items[2])) {
				if ($debug) print "$item: coding error: expecting index array 0 - name, 1 - target, 2 - capability<br/>";
				continue;
			}
			$args ["text"] = __("Add") . " " . __($item);
			$args["action"] = AddParamToUrl(self::getPost(),
					array( "operation" => "nav_add",
					       "main" => $item,
						"target" => $sub_menu_items['target'])) . ";location_reload";
			$result .= Core_Html::GuiButtonOrHyperlink("btn_add_" . $item, null, $args) . "<br/>";
			foreach ($sub_menu_items as $sub_menu_item) {
				$main_nav = $item;
				$sub_nav =  $sub_menu_item[0];
				$target = $sub_menu_item[1];
				if (! isset($sub_menu_item[2]))
				{
					if ($debug) print "index 2 is missing in " . $sub_menu_item[0] . "<br/>";
					continue;
				}
				if (! $u->can($capability = $sub_menu_item[2])){
					if ($debug) print "capability $capability is missing for $sub_nav<br/>";
					continue;
				}

				$sub_args = [];
				$sub_args["text"] = __($sub_nav);

				$sub_args["action"] = AddParamToUrl(self::getPost() , array( "operation" => "nav_add",
				                                                             "main"      => $main_nav,
				                                                             "sub"       => $sub_nav,
				                                                             "target"    => $target ), false) . ";location_reload";
				$result .= "===>" . Core_Html::GuiButtonOrHyperlink( "btn_add_" . $sub_nav, null, $sub_args ) . "<br/>";
			}
		}
		return $result;
	}

	public function admin_scripts() {
		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gem.js';
		wp_enqueue_script( 'gem', $file, null, $this->version, false );
	}

	function add_admin_notice($message)
	{
		if (! $this->admin_notices) $this->admin_notices = array();
		array_push($this->admin_notices, $message);
	}

	function admin_notices() {
		if (! $this->admin_notices) return;
		print '<div class="notice is-dismissible notice-info">';
		foreach ($this->admin_notices as $notice)
			print _e( $notice );
		print '</div>';
	}

	static function getTextDomain()
	{
		return 'e-fresh';
	}
}

function flavor_get_logger()
{
	return Core_Logger::instance();
}

function unlogged_guest_posts_redirect()
{
	if (Flavor::isManagementPage() && !is_user_logged_in()) {
			auth_redirect();
		}
}
function modify_admin_bar( $wp_admin_bar )
{
	$wp_admin_bar->add_node( [
		'id' => 'missions',
		'title' => __( 'Missions', 'משימות' ),
		'href' => '/wp-admin/admin.php?page=missions',
	] );


//		$wp_admin_bar->add_node( [
//			'id' => 'elementor-maintenance-edit',
//			'parent' => 'missions',
//			'title' => __( 'Edit Template', 'elementor' ),
//			'href' => "a.php",
//		] );
//
//	MyLog(__FUNCTION__);

}
?>