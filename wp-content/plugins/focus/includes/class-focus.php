<?php


/**
 * Class Focus
 */
class Focus {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Delivery_Drivers_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	protected $salary;
	protected $tasks;

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
	 * @var null
	 */

	/**
	 * The single instance of the class.
	 *
	 * @var Focus
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * focus instance.
	 *
	 */
	public $focus = null;

	/**
	 * @return mixed
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Main Focus Instance.
	 *
	 * Ensures only one instance of Focus is loaded or can be loaded.
	 *
	 * @static
	 * @return Focus - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self("Focus");
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		die( __FUNCTION__ .  __( 'Cloning is forbidden.', 'focus' ));
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		focus_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'focus' ), '2.1' );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
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
	public function __construct($plugin_name)
	{
		error_reporting( E_ALL );
		ini_set( 'display_errors', 'on' );

		$this->plugin_name = $plugin_name;
//		if (function_exists("get_user_id") and $user_id = get_user_id()) $this->nav_name = "management." . $user_id;
//		else $this->nav_name = null;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		$this->loader = new Focus_Loader();
		$this->init_hooks();

		do_action( 'focus_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
//		print "<br/>init_hooks<br/>";
		// register_activation_hook( WC_PLUGIN_FILE, array( 'Focus_Install', 'install' ) );
//		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );

		get_sql_conn(reconnect_db());

//		add_action( 'init', array( 'Focus_Emails', 'init_transactional_emails' ) );
		// add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'init', array( $this, 'add_image_sizes' ) );
		// add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		// $orders = new Focus_Orders( $this->get_plugin_name(), $this->get_version() );

		$tasks = Focus_Tasks::instance(self::getPost());
		$salary = Focus_Salary::instance(self::getPost());

		$this->loader->add_action( 'wp_enqueue_scripts', $tasks, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_enqueue_scripts', $salary, 'enqueue_scripts' );

		 require_once ABSPATH . 'wp-includes/pluggable.php';
//		 if (get_user_id() == 1) wp_set_current_user(383);
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ) ) ) {
			$logger = self::focus_get_logger();
			$logger->critical(
			/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'focus' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'focus_shutdown_error', $error );
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$this->define( 'FOCUS_ABSPATH', dirname( FOCUS_PLUGIN_FILE ) . '/' );
		$this->define( 'FOCUS_VERSION', $this->version );
		$this->define( 'FOCUS_INCLUDES', FOCUS_ABSPATH . 'includes/' );
		$this->define( 'FLAVOR_INCLUDES_URL', plugins_url() . '/flavor/includes/' ); // For js
		$this->define( 'FLAVOR_INCLUDES_ABSPATH', plugin_dir_path(__FILE__) . '../../flavor/includes/' );  // for php
		$this->define( 'FOCUS_DELIMITER', '|' );

		$upload_dir = wp_upload_dir( null, false );
		$this->define( 'FOCUS_LOG_DIR', $upload_dir['basedir'] . '/focus-logs/' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
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
	 * @param  string $type admin, ajax, cron or frontend.
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

	/**
	 * @param $operation
	 *
	 *
	 * @return string|void
	 * true if success, false if failed, integer if insert was made.
	 * if error occurred, it'll be output.
	 * operation can also output dynamic queried values- autolist.
	 * @throws Exception
	 */
	function handle_operation($operation)
	{
//		print __FILE__ . __FUNCTION__;
		// Handle global operation
		switch ($operation)
		{
			case "bad_url":
				$template_id = get_param("id", true);
				return Focus_Tasks::show_task($template_id);
				break;
//			case "reset_menu":
//				print "Reset_menu<br/>";
//				$user_id = get_user_id();
//				$nav_name = $this->GetNavName($user_id);
////				print "nam=" . $this->nav_name . "<br/>";
//				return Focus_Nav::instance()->create_nav($nav_name, $user_id, true);
		}
		// Pass to relevant module.
		$module = strtok($operation, "_");
		switch ($module){
			case "salary":
				$salary = Focus_Salary::instance();
				return ($salary->handle_operation($operation));
				break;
			case "data":
				$data = Core_Data::instance();
				return ($data->handle_operation($operation));
				break;
			default:
				$focus = Focus_Tasks::instance();
				return $focus->handle_focus_do($operation);
		}
		return;
	}
	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoload`er.
		 */
		require_once FOCUS_INCLUDES . 'class-focus-autoloader.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/data/sql.php';
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/org_gui.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/fund.php';
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/wp.php';

		/**
		 * Core classes.
		 */
		include_once FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php';
	}

	/**
	 * Include classes for theme support.
	 *
	 * @since 3.3.0
	 */
	/**
	 *
	 */
	public function include_template_functions() {
//		include_once WC_FOCUS_INCLUDES . 'includes/focus-template-functions.php';
	}


	/**
	 * @return Focus_Logger
	 */
	public function focus_get_logger()
	{
		return Core_Logger::instance();
	}
	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
//		print __CLASS__ . ':' .__FUNCTION__ . "<br/>";
//		var_dump());
		$plugins = get_option( 'active_plugins', array());
		$plugin = "focus/focus.php";
		if (! in_array("flavor/flavor.php", $plugins)) {
			unset ( $plugins[ $plugin ] );
			return false;
		}
		// Before init action.
		do_action( 'before_focus_init' );

		$this->salary = Focus_Salary::instance();
		$this->tasks = Focus_Tasks::instance();

		// Set up localisation.
		$this->load_plugin_textdomain();

		$shortcodes = Core_Shortcodes::instance();
		$shortcodes->add($this->salary->getShortcodes());
		$shortcodes->add($this->tasks->getShortcodes());

		// Load class instances.

		// Init action.
		do_action( 'focus_init' );
	}

//	/**
//	 * Load Localisation files.
//	 *
//	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
//	 *
//	 * Locales found in:
//	 *      - WP_LANG_DIR/woocommerce/woocommerce-LOCALE.mo
//	 *      - WP_LANG_DIR/plugins/woocommerce-LOCALE.mo
//	 */
	/**
	 *
	 */

	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'focus' );

//		unload_textdomain( 'focus' );
		$file = WP_LANG_DIR . '/im-haadama-' . $locale . '.mo';
//		print "trying to load $file <br/>";
		// wp-content/languages/plugins/im_haadama-he_IL.po
		$rc = load_textdomain( 'im-haadama', $file );
//		print "rc=$rc<br/>";
//		load_plugin_textdomain( 'focus', false, plugin_basename( dirname( FOCUS_PLUGIN_FILE ) ) . '/i18n/languages' );
	}
//
//	/**
//	 * Ensure theme and server variable compatibility and setup image sizes.
//	 */
	/**
	 *
	 */
	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		$this->define( 'FOCUS_TEMPLATE_PATH', $this->template_path() );

		// $this->add_thumbnail_support();
	}
	/**
	 * @return mixed
	 */
	public function template_path() {
		return apply_filters( 'focus_template_path', 'focus/' );
	}

	/**
	 *
	 */
	public function run ()
	{
//		print "running loader";
		$this->loader->run();
	}

	/**
	 * @return string|null
	 */

	static public function settingPage()
	{
		$result = "";
		//                     Top nav                  Sub nav    target,                                        capability
		$module_list = array( "Focus" => array(array("Repeating weekly", "/focus?operation=show_repeating_tasks&freq=w","show_tasks"),
			                                   array("Repeating monthly", "/focus?operation=show_repeating_tasks&freq=j","show_tasks"),
			                                   array("Repeating annual", "/focus?operation=show_repeating_tasks&freq=z","show_tasks")));

		$result .= Flavor::ClassSettingPage($module_list);
		return $result;
	}

	static function getPost()
	{
		return "/wp-content/plugins/focus/post.php";
	}
}
