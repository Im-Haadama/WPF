<?php


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
	 * @var      Delivery_Drivers_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0';

	/**
	 * @var
	 */
	private $plugin_name;

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
		error_reporting( E_ALL );
		ini_set( 'display_errors', 'on' );

		$this->plugin_name = $plugin_name;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		$this->loader = new Core_Autoloader(FLAVOR_ABSPATH);
		$this->init_hooks();

		do_action( 'flavor_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		// register_activation_hook( WC_PLUGIN_FILE, array( 'Flavor_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );

		get_sql_conn( reconnect_db() );
//		add_action( 'init', array( 'Flavor_Emails', 'init_transactional_emails' ) );
		// add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'init', array( $this, 'add_image_sizes' ) );
		// add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );

		add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts' ));

//		$this->loader->add_action( 'wp_enqueue_scripts', $orders, 'enqueue_scripts' );
	}

	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['wpf'] = esc_html__( 'Wordpress-f', 'wpf' );
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
			$result .= Core_Html::gui_header(2, $module_name);
			$result .= $setting;
		}

		print  $result;
//		$args = [];
//
//		print Core_Html::GuiCheckbox("aa", false, $args);
		// woocommerce_admin_fields( self::get_settings() );
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
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public static function get_settings() {

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
//		var_dump($settings);
	//	$settings = self::plugins_setting();
		return apply_filters( 'wpf_settings', $settings );
	}


/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( in_array( $error['type'], array(
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
		$instance->handle_operation();
	}

	/**
	 * @param $operation
	 *
	 * @return string|void
	 */
	function handle_operation($operation) {
//		print __FUNCTION__. ': ' . $operation . "<br/>";
		$module = strtok( $operation, "_" );
		if ( $module === "data" ) {
			return handle_data_operation( $operation );
		}

		if ( $module === "fresh" ) {
			return Fresh::instance()->handle_operation( $operation );
		}

		switch ( $operation ) {
			case "flavor_main":
				self::show_main();
				return;
				// http://store.im-haadama.co.il/wp-content/plugins/flavor/post.php?operation=nav_add&main=Flavor&sub=Bank%20transactions&target=%2Ffinance_bank
			case "nav_add":
				$main = get_param("main", true);
				$sub = get_param("sub", false);
				$target = get_param("target", true);
				$nav = $this->getNav();
				$main_id = $nav->AddMain(array('title' => $main, 'url' => $target));
				if (! $main_id) return $main_id;
//				print "$main $sub $target<br/>";
				if ($sub) return $nav->AddSub($main_id, array( 'title' => $sub, 'url' => $target));
				return $main_id;
		}
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
		$shortcodes->add(array('flavor'  => array(__CLASS__ . '::static_handle_show', "read")));

		// Init action.
		do_action( 'flavor_init' );
	}

	/**
	 *
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'flavor' );

		unload_textdomain( 'flavor' );
//		load_textdomain( 'flavor', FERSH_LANG_DIR . '/flavor/flavor-' . $locale . '.mo' );
//		load_plugin_textdomain( 'flavor', false, plugin_basename( dirname( FLAVOR_PLUGIN_FILE ) ) . '/i18n/languages' );
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
			         strstr( $post->post_content, '[finance' ) );
		return false;

	}

	static public function plugins_setting()
	{
//		$result = Core_Html::gui_header(1, "Settings");

		$sections = [];
		foreach (array("Fresh", "Finance", "Flavor", "Focus") as $plugin)
		{
			if (class_exists($plugin)){ // Todo: need to check permissions
				$call = array($plugin, "settingPage");
				if (is_callable($call)){
					$section = call_user_func($call);
//					var_dump($section);
					$sections[$plugin] = $section;
//					array_push($sections, $section);
				}
			}
		}
//		$result .= Core_Html::GuiTabs($tabs);

		return $sections;
	}

	static private function getPost()
	{
		return "/wp-content/plugins/flavor/post.php";
	}

	static public function SettingPage()
	{
		$result = "";
		$module_list = array( "Flavor" => array());

		$result .= self::ClassSettingPage($module_list);
//		foreach ($module_list as $item){
//			$args = [];
//			$args ["text"] = __("Add") . " " . __($item);
//			$args["action"] = add_param_to_url(self::getPost() , array( "operation" => "add_nav", "module" => $item )) . ";location_reload";
//			$result .= Core_Html::GuiButtonOrHyperlink("btn_add_" . $item, null, $args);
//		}

		return $result;
	}

	static function ClassSettingPage($module_list)
	{
		$u = new Core_Users();
		$result = "";
		foreach ($module_list as $item => $sub_menu_items){
//			$args = ($item => array(
//				'name' => 'Create navigation items for ' . $item,
//				'type' => ''
//			)
			$args ["text"] = __("Add") . " " . __($item);
			$args["action"] = add_param_to_url(self::getPost(), array( "operation" => "fresh_nav_add", "main" => $item ), null, false) . ";location_reload";
			$result .= Core_Html::GuiButtonOrHyperlink("btn_add_" . $item, null, $args) . "<br/>";
			foreach ($sub_menu_items as $sub_menu_item) {
				$main_nav = __CLASS__;
				$sub_nav =  $sub_menu_item[0];
				$target = $sub_menu_item[1];
				if (! isset($sub_menu_item[2]) or (! $u->can($capability = $sub_menu_item[2]))) continue;

				$sub_args = [];
				$sub_args["text"] = __($sub_nav);

				$sub_args["action"] = add_param_to_url(self::getPost() , array( "operation" => "nav_add",
				                                                                "main" => $main_nav,
				                                                                "sub" => $sub_nav,
				                                                                "target" => $target ), false) . ";location_reload";
				//print $sub_args["action"] . "<br/>";
				$result .= "===>" . Core_Html::GuiButtonOrHyperlink( "btn_add_" . $sub_nav, null, $sub_args ) . "<br/>";
			}
		}
		return $result;
	}

	public function admin_scripts() {
		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );
	}

}

function flavor_get_logger()
{
	return Core_Logger::instance();
}

?>