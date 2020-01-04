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
	private $nav;

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
		// register_activation_hook( WC_PLUGIN_FILE, array( 'Focus_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
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

		$views = Focus_Views::instance();
		$salary = Focus_Salary::instance();

		$this->loader->add_action( 'wp_enqueue_scripts', $views, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_enqueue_scripts', $salary, 'enqueue_scripts' );

		 require_once ABSPATH . 'wp-includes/pluggable.php';
//		 wp_set_current_user(369);

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
				return Focus_Views::show_task($template_id);
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
				$focus = Focus_Views::instance();
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
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/data/sql.php';
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/org_gui.php';
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/fund.php';
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
		// Before init action.
		do_action( 'before_focus_init' );

//		new Focus_Nav("management." . get_user_id());

//		print Focus_Nav::instance()->get_nav();

		// Set up localisation.
		$this->load_plugin_textdomain();
		$shortcodes = Core_Shortcodes::instance();
		$shortcodes->add(array('focus_main'           =>'Focus_Views::handle_focus_show'
//		                       'salary_main'        => array($salary_instance, 'salary_main'),
//		                       'roles_main'    => __CLASS__ . '::roles_main',
//		                       'show_settings' => __CLASS__ . '::show_settings'
		));


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

	public static function salary_main($atts) {
		$operation = get_param("operation", false, "salary_main");
		print "operation=" . $operation;
		return self::shortcode_wrapper( array( 'Focus_Salary', 'handle_salary_show' ), $operation );
	}

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
//	/**
//	 * Add WC Image sizes to WP.
//	 *
//	 * As of 3.3, image sizes can be registered via themes using add_theme_support for woocommerce
//	 * and defining an array of args. If these are not defined, we will use defaults. This is
//	 * handled in wc_get_image_size function.
//	 *
//	 * 3.3 sizes:
//	 *
//	 * woocommerce_thumbnail - Used in product listings. We assume these work for a 3 column grid layout.
//	 * woocommerce_single - Used on single product pages for the main image.
//	 *
//	 * @since 2.3
//	 */
//	public function add_image_sizes() {
//		$thumbnail         = wc_get_image_size( 'thumbnail' );
//		$single            = wc_get_image_size( 'single' );
//		$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
//
//		add_image_size( 'woocommerce_thumbnail', $thumbnail['width'], $thumbnail['height'], $thumbnail['crop'] );
//		add_image_size( 'woocommerce_single', $single['width'], $single['height'], $single['crop'] );
//		add_image_size( 'woocommerce_gallery_thumbnail', $gallery_thumbnail['width'], $gallery_thumbnail['height'], $gallery_thumbnail['crop'] );
//
//		// Registered for bw compat. @todo remove in 4.0.
//		add_image_size( 'shop_catalog', $thumbnail['width'], $thumbnail['height'], $thumbnail['crop'] );
//		add_image_size( 'shop_single', $single['width'], $single['height'], $single['crop'] );
//		add_image_size( 'shop_thumbnail', $gallery_thumbnail['width'], $gallery_thumbnail['height'], $gallery_thumbnail['crop'] );
//	}
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
	/**
	 * @return mixed
	 */
	public function template_path() {
		return apply_filters( 'focus_template_path', 'focus/' );
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
//	/**
//	 * WooCommerce Payment Token Meta API and Term/Order item Meta - set table names.
//	 */
//	public function wpdb_table_fix() {
//		global $wpdb;
//		$wpdb->payment_tokenmeta = $wpdb->prefix . 'woocommerce_payment_tokenmeta';
//		$wpdb->order_itemmeta    = $wpdb->prefix . 'woocommerce_order_itemmeta';
//		$wpdb->tables[]          = 'woocommerce_payment_tokenmeta';
//		$wpdb->tables[]          = 'woocommerce_order_itemmeta';
//
//		if ( get_option( 'db_version' ) < 34370 ) {
//			$wpdb->woocommerce_termmeta = $wpdb->prefix . 'woocommerce_termmeta';
//			$wpdb->tables[]             = 'woocommerce_termmeta';
//		}
//	}
//
//	/**
//	 * Get queue instance.
//	 *
//	 * @return WC_Queue_Interface
//	 */
//	public function queue() {
//		return WC_Queue::instance();
//	}
//
//	/**
//	 * Get Checkout Class.
//	 *
//	 * @return WC_Checkout
//	 */
//	public function checkout() {
//		return WC_Checkout::instance();
//	}
//
//	/**
//	 * Get gateways class.
//	 *
//	 * @return WC_Payment_Gateways
//	 */
//	public function payment_gateways() {
//		return WC_Payment_Gateways::instance();
//	}
//
//	/**
//	 * Get shipping class.
//	 *
//	 * @return WC_Shipping
//	 */
//	public function shipping() {
//		return WC_Shipping::instance();
//	}
//
//	/**
//	 * Email Class.
//	 *
//	 * @return WC_Emails
//	 */
//	public function mailer() {
//		return WC_Emails::instance();
//	}

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
//	public function GetNav()
//	{
//		if (! $this->nav and ($user_id = get_user_id()))
//			$this->nav = new Focus_Nav("management." . $user_id);
//
//		return $this->nav;
//	}
}

