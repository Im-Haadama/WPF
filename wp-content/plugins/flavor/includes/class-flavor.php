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

//		$this->loader->add_action( 'wp_enqueue_scripts', $orders, 'enqueue_scripts' );
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
	function handle_operation(  ) {
		$operation = get_param("operation", false, "flavor_main");
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

			case "add_nav":
				$flavor = Flavor::instance();
				$nav = $flavor->getNav();
				$menu_item = array("title" => 'Flavor', 'url' => "/flavor");
				return $nav->AddMain($menu_item);
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

	static public function show_main()
	{
		$result = Core_Html::gui_header(1, "Settings");

		$tabs = [];
		foreach (array("Fresh", "Finance", "Flavor", "Focus") as $plugin)
		{
			if (class_exists($plugin)){ // Todo: need to check permissions
				array_push($tabs, array($plugin, __($plugin), $plugin::instance()->settingPage()));
			}
		}
		$result .= Core_Html::GuiTabs($tabs);

		print $result;
	}

	static private function getPost()
	{
		return "/wp-content/plugins/flavor/post.php";
	}

	public function SettingPage()
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
		$result = "";
		foreach ($module_list as $item => $sub_menu_items){
			$args = [];
			$args ["text"] = __("Add") . " " . __($item);
			$args["action"] = add_param_to_url(self::getPost() , array( "operation" => "fresh_nav_add", "module" => $item )) . ";location_reload";
			$result .= Core_Html::GuiButtonOrHyperlink("btn_add_" . $item, null, $args) . "<br/>";
			foreach ($sub_menu_items as $sub_menu_item) {
				$operation = $sub_menu_item[1];
				$sub_args = [];
				$sub_args["text"] = __($sub_menu_item[0]);
				$sub_args["action"] = add_param_to_url(self::getPost() , array( "operation" => "fresh_nav_add", "module" => $item, "sub_module" => $sub_menu_item[1] )) . ";location_reload";
				$result .= "===>" . Core_Html::GuiButtonOrHyperlink( "btn_add_" . $operation, null, $sub_args ) . "<br/>";
			}
		}
		return $result;
	}
}

function flavor_get_logger()
{
	return Core_Logger::instance();
}