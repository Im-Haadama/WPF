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
		$this->define( 'FLAVOR_INCLUDES', FLAVOR_ABSPATH . '/includes/' );
//		$this->define( 'FLAVOR_ROUNDING_PRECISION', 6 );
//		$this->define( 'FLAVOR_DISCOUNT_ROUNDING_MODE', 2 );
//		$this->define( 'FLAVOR_TAX_ROUNDING_MODE', 'yes' === get_option( 'woocommerce_prices_include_tax', 'no' ) ? 2 : 1 );
		$this->define( 'FLAVOR_DELIMITER', '|' );
		$this->define( 'FLAVOR_LOG_DIR', $upload_dir['basedir'] . '/flavor-logs/' );
		/// $this->define( 'FLAVOR_SESSION_CACHE_GROUP', 'wc_session_id' );
		// $this->define( 'FLAVOR_TEMPLATE_DEBUG_MODE', false );
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

	/**
	 * @param $operation
	 *
	 * @return string|void
	 */
	function handle_operation( $operation ) {
		$module = strtok( $operation, "_" );
		if ( $module === "data" ) {
			return handle_data_operation( $operation );
		}

		switch ( $operation ) {
			case "order_set_mission":
				$order_id   = get_param( "order_id", true );
				$mission_id = get_param( "mission_id", true );
				$order      = new Order( $order_id );
				$order->setMissionID( $mission_id );

				return "done";

			case "update":
				return handle_data_operation( $operation );

			case "new_customer":
				$order_id = get_param( "order_id", true );

				return self::new_customer( $order_id );

			case "show_settings":
				print self::show_settings( $operation );

				return;

			case "nav_add":
				$module = get_param( "module", true );
//				Core_Nav::instance();
//			print sql_trace();
//			 	$nav = Flavor_Nav::instance();
//				var_dump($nav);
				Flavor_Nav::instance()->AddModule( $module );
				break;

		}
	}

	/**
	 *
	 */
	static function show_settings() {
//		print __CLASS__ . ':' . __FUNCTION__ . "<br/>";

		print Focus_Nav::instance()->get_nav();

		$result    = gui_header( 1, "Add to menu" );
		$main_menu = array( "Suppliers" );
		foreach ( $main_menu as $item ) {
			$result .= GuiHyperlink( "Add $item", add_to_url( array( "operation" => "nav_add", "module" => $item ) ) );
		}
		print $result;
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		if (! class_exists('Core_Autoloader'))
			require_once FLAVOR_INCLUDES . 'core/class-core-autoloader.php';

		require_once FLAVOR_INCLUDES . 'core/fund.php';
		require_once FLAVOR_INCLUDES . 'core/data/sql.php';
		require_once FLAVOR_INCLUDES . 'core/data/data.php';
		require_once FLAVOR_INCLUDES . 'core/wp.php';

		/**
		 * Interfaces.
		 */

		/**
		 * Abstract classes.
		 */

		/**
		 * Core classes.
		 */
		include_once FLAVOR_INCLUDES . 'core/class-core-shortcodes.php';

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

		/**
		 * Libraries
		 */
//		include_once WC_FLAVOR_INCLUDES . 'includes/libraries/action-scheduler/action-scheduler.php';
//
//		if ( defined( 'WP_CLI' ) && WP_CLI ) {
//			include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-cli.php';
//		}
//
//		if ( $this->is_request( 'admin' ) ) {
//			include_once WC_FLAVOR_INCLUDES . 'includes/admin/class-wc-admin.php';
//		}
//
//		if ( $this->is_request( 'frontend' ) ) {
//			$this->frontend_includes();
//		}
//
//		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'woocommerce_allow_tracking', 'no' ) ) {
//			include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-tracker.php';
//		}
//
//		$this->theme_support_includes();
//		$this->query = new WC_Query();
//		$this->api   = new WC_API();
	}

	/**
	 * Include classes for theme support.
	 *
	 * @since 3.3.0
	 */
//	private function theme_support_includes() {
//		if ( wc_is_active_theme( array( 'twentynineteen', 'twentyseventeen', 'twentysixteen', 'twentyfifteen', 'twentyfourteen', 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' ) ) ) {
//			switch ( get_template() ) {
//				case 'twentyten':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-ten.php';
//					break;
//				case 'twentyeleven':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-eleven.php';
//					break;
//				case 'twentytwelve':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-twelve.php';
//					break;
//				case 'twentythirteen':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-thirteen.php';
//					break;
//				case 'twentyfourteen':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-fourteen.php';
//					break;
//				case 'twentyfifteen':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-fifteen.php';
//					break;
//				case 'twentysixteen':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-sixteen.php';
//					break;
//				case 'twentyseventeen':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-seventeen.php';
//					break;
//				case 'twentynineteen':
//					include_once WC_FLAVOR_INCLUDES . 'includes/theme-support/class-wc-twenty-nineteen.php';
//					break;
//			}
//		}
//	}
//
//	/**
//	 * Include required frontend files.
//	 */
//	public function frontend_includes() {
//		include_once WC_FLAVOR_INCLUDES . 'includes/wc-cart-functions.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/wc-notice-functions.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/wc-template-hooks.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-template-loader.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-frontend-scripts.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-form-handler.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-cart.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-tax.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-shipping-zones.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-customer.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-embed.php';
//		include_once WC_FLAVOR_INCLUDES . 'includes/class-wc-session-handler.php';
//	}
//
//	/**
//	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
//	 */
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
//		print __CLASS__ . ':' . __FUNCTION__ . "<br/>";
		do_action( 'before_flavor_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();
		$shortcodes = Core_Shortcodes::instance();
		$shortcodes->add(array('flavor'  => __CLASS__ . '::show_main'));

//		var_dump( Flavor_Nav::instance() );
//		print "nav = " . Focus_Nav::instance()->get_nav() . "<br/>";

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

	/**
	 * @return string|null
	 */
	public function get_nav()
	{
		if ($this->nav) return $this->nav;
		if (function_exists("get_user_id")) {
			$this->nav = "management." . get_user_id();
			return $this->nav;
		}
		die (__FILE__ . ':' . __LINE__);
	}


	/**
	 * used by template to decide if to load management css.
	 * @return bool
	 * if one of ours shortcodes are used.
	 */
	static public function isManagementPage() {
		global $post;

		return ( strstr( $post->post_content, '[fresh' ) ||
		         strstr( $post->post_content, '[focus' ) ||
		         strstr( $post->post_content, '[salary' ) ||
		         strstr( $post->post_content, '[flavor' ) ||
		         strstr( $post->post_content, '[finance' ) );

	}

	static public function show_main()
	{
		$result = gui_header(1, "Settings");

		$tabs = [];
		foreach (array("Fresh", "Finance") as $plugin)
		{
			if (class_exists($plugin)){ // Todo: need to check permissions
				array_push($tabs, array($plugin, __($plugin), $plugin::instance()->settingPage()));
			}
		}
		$result .= GuiTabs($tabs);

		print $result;
	}
}
