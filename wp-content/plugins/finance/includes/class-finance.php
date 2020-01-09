<?php


class Finance {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 * created: 22/12/2019
	 * 
	 * @since    1.0.0
	 * @access   protected
	 * @var     
	 */
	protected $loader;
	protected $shortcodes;
	protected $payments;
	protected $bank;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0';

	private $plugin_name;

	/**
	 * The single instance of the class.
	 *
	 * @var Finance
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * finance instance.
	 *
	 */
	public $finance = null;

	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

	/**
	 * Main Finance Instance.
	 *
	 * Ensures only one instance of Finance is loaded or can be loaded.
	 *
	 * @static
	 * @return Finance - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self("Finance");
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		die( __FUNCTION__ .  __( 'Cloning is forbidden.', 'finance' ));
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		finance_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'finance' ), '2.1' );
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
		$this->plugin_name = $plugin_name;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		if (! defined('FINANCE_ABSPATH')) die ("not defined");
		$this->loader = new Core_Autoloader(FINANCE_ABSPATH);

		$this->init_hooks();

		do_action( 'finance_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		// register_activation_hook( WC_PLUGIN_FILE, array( 'Finance_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );

		get_sql_conn(reconnect_db());
//		add_action( 'init', array( 'Finance_Emails', 'init_transactional_emails' ) );
		// add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'init', array( $this, 'add_image_sizes' ) );
		// add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts' ));
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ) ) ) {
			$logger = finance_get_logger();
			$logger->critical(
			/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'finance' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'finance_shutdown_error', $error );
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'FINANCE_ABSPATH', dirname( FINANCE_PLUGIN_FILE ) . '/' );
		$this->define( 'FINANCE_PLUGIN_BASENAME', plugin_basename( FINANCE_PLUGIN_FILE ) );
		$this->define( 'FINANCE_VERSION', $this->version );
		$this->define( 'FINANCE_INCLUDES', FINANCE_ABSPATH . 'includes/' );
		$this->define( 'FINANCE_INCLUDES_URL', plugins_url() . '/finance/includes/' ); // For js
		$this->define( 'FLAVOR_INCLUDES_ABSPATH', plugin_dir_path(__FILE__) . '../../flavor/includes/' );  // for php
		$this->define( 'FINANCE_DELIMITER', '|' );
		$this->define( 'FINANCE_LOG_DIR', $upload_dir['basedir'] . '/finance-logs/' );
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

	function handle_operation($operation)
	{
		switch ($operation)
		{
			case "data_update":
			case "update":
				return Core_Data::handle_operation($operation);

			case "new_customer":
				$order_id = get_param("order_id", true);
				return self::new_customer($order_id);

			case 'bank_import_from_file':
//				require_once( FINANCE_INCLUDES . "/core/data/Importer.php" );
//				var_dump($_FILES);
				$file_name = $_FILES["fileToUpload"]["tmp_name"];
				print "Trying to import $file_name<br/>";
				$I                    = new Core_Importer();
				$fields               = null;
				$fields               = array();
				$fields['account_id'] = get_param( 'selection' );
				if ( ! $fields['account_id'] ) {
					die( "not account given" );
				}
				try {
					$result = $I->Import( $file_name, "im_bank", $fields, 'Finance_Bank::bank_check_dup' );
				} catch ( Exception $e ) {
					print $e->getMessage();

					return;
				}
				print $result[0] . " rows imported<br/>";
				print $result[1] . " duplicate rows <br/>";
				print $result[2] . " failed rows <br/>";
				return true;
				break;
		}
		return false;
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		if (! class_exists('Core_Autoloader'))
			require_once FINANCE_INCLUDES . 'core/class-core-autoloader.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/fund.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/data/sql.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/wp.php';

		/**
		 * Interfaces.
		 */
//		include_once WC_FINANCE_INCLUDES . 'includes/interfaces/class-wc-abstract-order-data-store-interface.php';

		/**
		 * Abstract classes.
		 */
//		include_once WC_FINANCE_INCLUDES . 'includes/abstracts/abstract-wc-data.php';

		/**
		 * Core classes.
		 */
//		include_once WC_FINANCE_INCLUDES . 'includes/wc-core-functions.php';

		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
//		include_once WC_FINANCE_INCLUDES . 'includes/class-wc-data-store.php';

		/**
		 * REST API.
		 */
//		include_once WC_FINANCE_INCLUDES . 'includes/legacy/class-wc-legacy-api.php';

		/**
		 * Libraries
		 */
//		include_once WC_FINANCE_INCLUDES . 'includes/libraries/action-scheduler/action-scheduler.php';
//
//		if ( defined( 'WP_CLI' ) && WP_CLI ) {
//			include_once WC_FINANCE_INCLUDES . 'includes/class-wc-cli.php';
//		}
//
//		if ( $this->is_request( 'admin' ) ) {
//			include_once WC_FINANCE_INCLUDES . 'includes/admin/class-wc-admin.php';
//		}
//
//		if ( $this->is_request( 'frontend' ) ) {
//			$this->frontend_includes();
//		}
//
//		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'woocommerce_allow_tracking', 'no' ) ) {
//			include_once WC_FINANCE_INCLUDES . 'includes/class-wc-tracker.php';
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
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-ten.php';
//					break;
//				case 'twentyeleven':
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-eleven.php';
//					break;
//				case 'twentytwelve':
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-twelve.php';
//					break;
//				case 'twentythirteen':
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-thirteen.php';
//					break;
//				case 'twentyfourteen':
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-fourteen.php';
//					break;
//				case 'twentyfifteen':
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-fifteen.php';
//					break;
//				case 'twentysixteen':
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-sixteen.php';
//					break;
//				case 'twentyseventeen':
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-seventeen.php';
//					break;
//				case 'twentynineteen':
//					include_once WC_FINANCE_INCLUDES . 'includes/theme-support/class-wc-twenty-nineteen.php';
//					break;
//			}
//		}
//	}
//
//	/**
//	 * Include required frontend files.
//	 */
//	public function frontend_includes() {
//		include_once WC_FINANCE_INCLUDES . 'includes/wc-cart-functions.php';
//	}
//
//	/**
//	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
//	 */
	public function include_template_functions() {
//		include_once WC_FINANCE_INCLUDES . 'includes/finance-template-functions.php';
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_finance_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		$this->bank = Finance_Bank::instance();
		$this->payments = Finance_Payments::instance();
		$this->shortcodes = Core_Shortcodes::instance();
		$this->shortcodes->add($this->payments->getShortcodes());
		$this->shortcodes->add($this->bank->getShortcodes());

		$this->shortcodes->do_init();

		// For testing:
//		wp_set_current_user(369);

		// Load class instances.
//		$this->product_factory                     = new WC_Product_Factory();
//		$this->order_factory                       = new WC_Order_Factory();
//		$this->countries                           = new WC_Countries();
//		$this->integrations                        = new WC_Integrations();
//		$this->structured_data                     = new WC_Structured_Data();
//		$this->deprecated_hook_handlers['actions'] = new WC_Deprecated_Action_Hooks();
//		$this->deprecated_hook_handlers['filters'] = new WC_Deprecated_Filter_Hooks();

		// Classes/actions loaded for the frontend and for ajax requests.
//		if ( $this->is_request( 'frontend' ) ) {
//			// Session class, handles session data for users - can be overwritten if custom handler is needed.
//			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
//			$this->session = new $session_class();
//			$this->session->init();
//
//			$this->customer = new WC_Customer( get_current_user_id(), true );
//			// Cart needs the customer info.
//			$this->cart = new WC_Cart();
//
//			// Customer should be saved during shutdown.
//			add_action( 'shutdown', array( $this->customer, 'save' ), 10 );
//		}
//
//		$this->load_webhooks();

		// Init action.
		do_action( 'finance_init' );
	}

	static public function finance_main()
	{
		$result = "";

		if (im_user_can("show_bank")) {
			$bank = new Finance_Bank(FINANCE_PLUGIN_DIR . '/post.php');
			$operation = get_param("operation", false, null);
			print "operation: $operation<br/>";
			if ($operation) {
				self::handle_bank_operation($operation, get_url(1));
				return;
			}

			$result .= Core_Html::gui_header(1, "Main finance");
			$result .= Core_Html::gui_header(2, "Bank");

			$result .= $bank->bank_status();
		}

		if (im_user_can("show_bank")) {
			print Core_Html::gui_header(2, "Bank");
		}

		print $result;
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
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'finance' );

//		unload_textdomain( 'finance' );
//		load_textdomain( 'finance', FERSH_LANG_DIR . '/finance/finance-' . $locale . '.mo' );
//		load_plugin_textdomain( 'finance', false, plugin_basename( dirname( FINANCE_PLUGIN_FILE ) ) . '/i18n/languages' );
	}
//
//	/**
//	 * Ensure theme and server variable compatibility and setup image sizes.
//	 */
	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		$this->define( 'FINANCE_TEMPLATE_PATH', $this->template_path() );

		// $this->add_thumbnail_support();
	}
//
//	/**
//	 * Ensure post thumbnail support is turned on.
//	 */
//	private function add_thumbnail_support() {
//		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
//			add_theme_support( 'post-thumbnails' );
//		}
//		add_post_type_support( 'product', 'thumbnail' );
//	}
//
//
//	/**
//	 * Get the plugin url.
//	 *
//	 * @return string
//	 */
//	public function plugin_url() {
//		return untrailingslashit( plugins_url( '/', WC_PLUGIN_FILE ) );
//	}
//
//	/**
//	 * Get the plugin path.
//	 *
//	 * @return string
//	 */
//	public function plugin_path() {
//		return untrailingslashit( plugin_dir_path( WC_PLUGIN_FILE ) );
//	}
//
//	/**
//	 * Get the template path.
//	 *
//	 * @return string
//	 */
	public function template_path() {
		return apply_filters( 'finance_template_path', 'finance/' );
	}
//
//	/**
//	 * Get Ajax URL.
//	 *
//	 * @return string
//	 */
//	public function ajax_url() {
//		return admin_url( 'admin-ajax.php', 'relative' );
//	}
//
//	/**
//	 * Return the WC API URL for a given request.
//	 *
//	 * @param string    $request Requested endpoint.
//	 * @param bool|null $ssl     If should use SSL, null if should auto detect. Default: null.
//	 * @return string
//	 */
//	public function api_request_url( $request, $ssl = null ) {
//		if ( is_null( $ssl ) ) {
//			$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
//		} elseif ( $ssl ) {
//			$scheme = 'https';
//		} else {
//			$scheme = 'http';
//		}
//
//		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
//			$api_request_url = trailingslashit( home_url( '/index.php/wc-api/' . $request, $scheme ) );
//		} elseif ( get_option( 'permalink_structure' ) ) {
//			$api_request_url = trailingslashit( home_url( '/wc-api/' . $request, $scheme ) );
//		} else {
//			$api_request_url = add_query_arg( 'wc-api', $request, trailingslashit( home_url( '', $scheme ) ) );
//		}
//
//		return esc_url_raw( apply_filters( 'woocommerce_api_request_url', $api_request_url, $request, $ssl ) );
//	}
//
//	/**
//	 * Load & enqueue active webhooks.
//	 *
//	 * @since 2.2
//	 */
//	private function load_webhooks() {
//
//		if ( ! is_blog_installed() ) {
//			return;
//		}
//
//		wc_load_webhooks();
//	}
//

	public function enqueue_scripts() {
		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

		$file = FINANCE_INCLUDES_URL . 'finance.js';
		wp_enqueue_script( 'finance', $file, null, $this->version, false );
	}


	public function run ()
	{
//		var_dump($this->shortcodes);
//		$this->loader->run();
	}

	static public function settingPage()
	{
		$result = "";
		//                     Top nav                  Sub nav             target,                              capability
		$module_list = array( "Finance" => array(array("Bank transactions", "/finance_bank",                     "show_bank"),
								                 array("Bank Receipts",     "/finance_bank?operation=receipts",  "show_bank"),
												 array("Bank payments]",    "/finance_bank?operation=payments",  "show_bank")));

		$result .= Flavor::ClassSettingPage($module_list);
		return $result;
	}
}