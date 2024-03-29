<?php

/**
 * Class Focus
 */
class Focus {
	protected Core_Hook_Handler $loader;
	protected $auto_loader;
	protected $manager;
	protected $views;
	protected $database;
	private $focus_users;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.6.6';

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
	private function __construct($plugin_name)
	{
		// Call after install
//		self::CreateRoles();

		$this->plugin_name = $plugin_name;
//		if (function_exists("get_user_id") and $user_id = get_user_id()) $this->nav_name = "management." . $user_id;
//		else $this->nav_name = null;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		$this->init();
		$this->init_hooks();

		do_action( 'focus_loaded' );
	}

	static function addRoles()
	{
		Flavor_Roles::instance()->addRole( "focus_user", array("show_tasks"));
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		// register_activation_hook( WC_PLUGIN_FILE, array( 'Focus_Install', 'install' ) );
//		register_shutdown_function( array( $this, 'log_errors' ) );
		AddAction( 'after_setup_theme', array( $this, 'setup_environment' ) );
		AddAction( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
//		AddAction( 'init', array( $this, 'init' ), 0 );
//		AddAction( 'init', array( 'Core_Shortcodes', 'init' ) );

//		GetSqlConn(ReconnectDb());

//		AddAction( 'init', array( 'Focus_Emails', 'init_transactional_emails' ) );
		// AddAction( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// AddAction( 'init', array( $this, 'add_image_sizes' ) );
		// AddAction( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		// $orders = new Focus_Orders( $this->get_plugin_name(), $this->get_version() );

//		$manager = Focus_Manager::instance(self::getPost());
//		$tasks = Focus_Salary::instance(self::getPost());

//		$this->loader->AddAction( 'wp_enqueue_scripts', $this->manager, 'enqueue_scripts' );
		$this->loader->AddAction( 'wp_enqueue_scripts', $this->views, 'admin_scripts' );

//		Focus_Project::init();


        $this->focus_users = new Focus_Users_Management();
        $this->focus_users->init_hooks($this->loader);

        $focus_actions = new Focus_Actions();
        $focus_actions->init_hooks($this->loader);

		$focus_views = Focus_Views::instance();
		$focus_views->init_hooks($this->loader);
	}

	function next_page($input)
	{
		return "/project?i=1";
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

	function install()
	{
		$this->database = new Focus_Database("Focus");
		$this->database->install($this->version, true);
		// Give the administrator the capability
		FocusLog("Creating role");
		Flavor_Roles::instance()->addRole("focus_user", array("show_tasks"));
		FocusLog("Adding role to admin");
		$u = new Core_Users(get_user_id());
		$u->add_role("focus_user");
		// Create default pages
		Core_Pages::CreateIfNeeded("focus", "/focus", "focus_main");
		Core_Pages::CreateIfNeeded("project", "/project", "focus_project");
		Core_Pages::CreateIfNeeded("task", "/task", "focus_task");
		Core_Pages::CreateIfNeeded("signup", "/signup", "focus_sign_up");
		Core_Pages::CreateIfNeeded("template", "/template", "focus_template");
	}
	/**
	 * Define WC Constants.
	 */
	private function define_constants()
	{
		$this->define( 'FOCUS_ABSPATH', dirname( FOCUS_PLUGIN_FILE ) . '/' );
		$this->define( 'FOCUS_VERSION', $this->version );
		$this->define( 'FOCUS_INCLUDES', FOCUS_ABSPATH . 'includes/' );
		$this->define( 'FOCUS_INCLUDES_URL', plugins_url() . '/focus/includes/' ); // For js
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
//	function handle_operation($operation)
//	{
//		$ignore_list = array("operation");
//		$input = null;
//
//		////////////////////////
//		// called by post.php //
//		////////////////////////
//		Core_Hook_Handler::instance()->DoAction($operation, GetParams($ignore_list));
//		return;
//		if ( $result ) return $result;
//
//		// Handle global operation
//		switch ($operation)
//		{
//			case "bad_url":
//				$template_id = GetParam("id", true);
//				return Focus_Actions::show_task($template_id);
//				break;
//		}
//		$args["post_file"] = GetUrl(1);
//		$args["page"] = GetParam("page", false, 1);
//		// Pass to relevant module.
//		$module = strtok($operation, "_");
//		switch ($module){
//			case "gem":
//				return Core_Gem::handle_operation($operation, $args);
//			case "salary":
//				$salary = Finance_Salary::instance();
//				return ($salary->handle_operation($operation));
//				break;
//			case "data":
//				$data = Core_Data::instance();
//				return ($data->handle_operation($operation));
//				break;
//			default:
////				print "fault";
////				return "no handler found for $operation";
//				$focus = Focus_Actions::instance();
//				return $focus->handle_focus_do($operation);
//		}
//
//	}
	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {

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

	public function init()
	{
		$this->loader = Core_Hook_Handler::instance();
		$this->auto_loader = Core_Autoloader::instance();
		$this->auto_loader->add_path(FOCUS_INCLUDES);

		$this->loader->AddAction( 'init', Core_Shortcodes::instance(), 'init' );

		$shortcodes = Core_Shortcodes::instance();

		$plugins = get_option( 'active_plugins', array());
		$plugin = "focus/focus.php";
		if (! in_array("wpf_flavor/wpf_flavor.php", $plugins)) {
			unset ( $plugins[ $plugin ] );
			return false;
		}
		// Before init action.
		do_action( 'before_focus_init' );

		$this->manager = Focus_Manager::instance();
		$this->views = Focus_Views::instance(self::getPost());

		WPF_Organization::instance()->init($this->loader);

		// Set up localisation.
		$this->load_plugin_textdomain();

		$shortcodes->add($this->views->getShortcodes());

		$this->views->init($this->loader);

		// Creates the tasks from templates.
		$this->manager->init();

		Core_Gem::getInstance()->AddTable("working_teams");

		// Todo: activate this somewhere else
//		self::addRoles();

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
	public function run()
	{
//		print "run " . __CLASS__;
//		$this->loader->run();
		$this->manager->run();
		if (is_admin()) {
			self::install();
		}
//		self::create_tasks();
	}

	/**
	 * @return string|null
	 */

	static public function settingPage()
	{
		die("not implemented");
		$result = "";
		//                     Top nav                  Sub nav    target,                                        capability
//		$module_list = array( "Focus" => array(array("Repeating weekly", "/focus?operation=show_repeating_tasks&freq=w","show_tasks"),
//			                                   array("Repeating monthly", "/focus?operation=show_repeating_tasks&freq=j","show_tasks"),
//			                                   array("Repeating annual", "/focus?operation=show_repeating_tasks&freq=z","show_tasks")));

		$result .= WPF_Flavor::ClassSettingPage($module_list);
		return $result;
	}

	static function getPost()
	{
		return WPF_Flavor::getPost();
	}

	static function print_driver_tasks( $mission_id = 0 ) {
		$data = "";
		if ( ! TableExists( 'tasklist' ) ) {
			return "";
		}

		// Self collect supplies
		$sql = "SELECT t.id FROM im_tasklist t " .
		       "WHERE (status < 2)";

		if ( $mission_id ) {
			$sql .= " and t.mission_id = " . $mission_id;
		}

		$tasks = SqlQueryArrayScalar( $sql );
		foreach ( $tasks as $task_id ) {
			$t = new Focus_Tasklist($task_id);
			$data .= $t->print_task();
		}

		return $data;
	}
}

function FocusLog($message)
{
	MyLog($message, '', 'focus.log');
}

class enumTasklist {
	const
		waiting = 0,
		started = 1,
		done = 2,
		canceled = 3,
		bad_url = 4,
		failed = 5,
		done_creator = 6;
}
$Tasklist_Status_Names = array("waiting", "started", "done", "canceled", "bad_url", "failed", "done_creator");