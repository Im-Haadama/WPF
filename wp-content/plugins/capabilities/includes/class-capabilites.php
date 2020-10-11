<?php


class Capabilites {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 * created 20 Dec 2019
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      $loader
	 */
	protected $loader;

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
	 * @var Capabilites
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * capabilites instance.
	 *
	 */
	public $capabilites = null;

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

	/**
	 * Main Capabilites Instance.
	 *
	 * Ensures only one instance of Capabilites is loaded or can be loaded.
	 *
	 * @static
	 * @return Capabilites - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "Capabilites" );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		die( __FUNCTION__ . __( 'Cloning is forbidden.', 'capabilites' ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		core_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'capabilites' ), '2.1' );
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
		// $this->loader = new Capabilites_Loader();
		$this->init_hooks();

		do_action( 'capabilites_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		// register_activation_hook( WC_PLUGIN_FILE, array( 'Capabilites_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'admin_init', array( $this, 'hide_menu_items' ), 0 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );
		add_action( 'admin_menu', __CLASS__ . '::admin_menu' );
		add_action( 'toggle_role', __CLASS__ . '::toggle_role' );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		GetSqlConn( ReconnectDb() );
//		add_action( 'init', array( 'Capabilites_Emails', 'init_transactional_emails' ) );
		// add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'init', array( $this, 'add_image_sizes' ) );
		// add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );

		// Use once:
//		self::CreateRoles();
	}

	static function hide_menu_items()
	{
		// Business
		if (! current_user_can('promote_users')) {
			remove_menu_page('wpcf7');
			remove_menu_page('upload.php');
			remove_submenu_page('admin.php', 'extension');
		}
	}
	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
//		if ( in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ) ) ) {
//			$logger = capabilites_get_logger();
//			$logger->critical(
//			/* translators: 1: error message 2: file name and path 3: line number */
//				sprintf( __( '%1$s in %2$s on line %3$s', 'capabilites' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
//				array(
//					'source' => 'fatal-errors',
//				)
//			);
//			do_action( 'capabilites_shutdown_error', $error );
//		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'CAPABILITIES_WC_ABSPATH', dirname( CAPABILITES_PLUGIN_FILE ) . '/' );
		$this->define( 'CAPABILITIES_PLUGIN_BASENAME', plugin_basename( CAPABILITES_PLUGIN_FILE ) );
		$this->define( 'CAPABILITIES_VERSION', $this->version );
		$this->define( 'CAPABILITIES_INCLUDES', plugins_url() . '/includes/' );
		$this->define( 'CAPABILITIES_INCLUDES_URL', plugins_url() . '/capabilities/includes/' ); // For js
		$this->define( 'FLAVOR_INCLUDES_URL', plugins_url() . '/flavor/includes/' ); // For js
		$this->define( 'FLAVOR_INCLUDES_ABSPATH', plugin_dir_path( __FILE__ ) . '../../flavor/includes/' );  // for php
		$this->define( 'CAPABILITIES_DELIMITER', '|' );
		$this->define( 'CAPABILITIES_LOG_DIR', $upload_dir['basedir'] . '/capabilites-logs/' );
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

	function handle_operation( $operation ) {
		$result = apply_filters( $operation, null, null);
		if ( $result !== null) return $result;
		return false;
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/class-core-autoloader.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/data/sql.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/wp.php';

		/**
		 * Interfaces.
		 */
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-abstract-order-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-coupon-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-customer-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-customer-download-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-customer-download-log-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-object-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-order-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-order-item-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-order-item-product-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-order-item-type-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-order-refund-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-payment-token-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-product-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-product-variable-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-shipping-zone-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-logger-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-log-handler-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-webhooks-data-store-interface.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/interfaces/class-wc-queue-interface.php';

		/**
		 * Abstract classes.
		 */
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-data.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-object-query.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-payment-token.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-product.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-order.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-settings-api.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-shipping-method.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-payment-gateway.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-integration.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-log-handler.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-deprecated-hooks.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-session.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/abstracts/abstract-wc-privacy.php';

		/**
		 * Core classes.
		 */
//		include_once WC_CAPABILITES_INCLUDES . 'includes/wc-core-functions.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-datetime.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-post-types.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-install.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-geolocation.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-download-handler.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-comments.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-post-data.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-ajax.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-emails.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-data-exception.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-query.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-meta-data.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-order-factory.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-order-query.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-product-factory.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-product-query.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-payment-tokens.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-shipping-zone.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/gateways/class-wc-payment-gateway-cc.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/gateways/class-wc-payment-gateway-echeck.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-countries.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-integrations.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-cache-helper.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-https.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-deprecated-action-hooks.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-deprecated-filter-hooks.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-background-emailer.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-discounts.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-cart-totals.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/customizer/class-wc-shop-customizer.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-regenerate-images.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-privacy.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-structured-data.php';
		include_once FLAVOR_INCLUDES_ABSPATH . 'core/class-core-shortcodes.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-logger.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/queue/class-wc-action-queue.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/queue/class-wc-queue.php';

		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-data-store-wp.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-coupon-data-store-cpt.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-product-data-store-cpt.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-product-grouped-data-store-cpt.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-product-variable-data-store-cpt.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-product-variation-data-store-cpt.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/abstract-wc-order-item-type-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-order-item-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-order-item-coupon-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-order-item-fee-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-order-item-product-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-order-item-shipping-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-order-item-tax-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-payment-token-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-customer-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-customer-data-store-session.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-customer-download-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-customer-download-log-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-shipping-zone-data-store.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/abstract-wc-order-data-store-cpt.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-order-data-store-cpt.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-order-refund-data-store-cpt.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/data-stores/class-wc-webhook-data-store.php';

		/**
		 * REST API.
		 */
//		include_once WC_CAPABILITES_INCLUDES . 'includes/legacy/class-wc-legacy-api.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-api.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-auth.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-register-wp-admin-settings.php';

		/**
		 * Libraries
		 */
//		include_once WC_CAPABILITES_INCLUDES . 'includes/libraries/action-scheduler/action-scheduler.php';
//
//		if ( defined( 'WP_CLI' ) && WP_CLI ) {
//			include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-cli.php';
//		}
//
//		if ( $this->is_request( 'admin' ) ) {
//			include_once WC_CAPABILITES_INCLUDES . 'includes/admin/class-wc-admin.php';
//		}
//
//		if ( $this->is_request( 'frontend' ) ) {
//			$this->frontend_includes();
//		}
//
//		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'woocommerce_allow_tracking', 'no' ) ) {
//			include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-tracker.php';
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
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-ten.php';
//					break;
//				case 'twentyeleven':
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-eleven.php';
//					break;
//				case 'twentytwelve':
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-twelve.php';
//					break;
//				case 'twentythirteen':
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-thirteen.php';
//					break;
//				case 'twentyfourteen':
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-fourteen.php';
//					break;
//				case 'twentyfifteen':
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-fifteen.php';
//					break;
//				case 'twentysixteen':
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-sixteen.php';
//					break;
//				case 'twentyseventeen':
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-seventeen.php';
//					break;
//				case 'twentynineteen':
//					include_once WC_CAPABILITES_INCLUDES . 'includes/theme-support/class-wc-twenty-nineteen.php';
//					break;
//			}
//		}
//	}
//
//	/**
//	 * Include required frontend files.
//	 */
//	public function frontend_includes() {
//		include_once WC_CAPABILITES_INCLUDES . 'includes/wc-cart-functions.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/wc-notice-functions.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/wc-template-hooks.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-template-loader.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-frontend-scripts.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-form-handler.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-cart.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-tax.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-shipping-zones.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-customer.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-embed.php';
//		include_once WC_CAPABILITES_INCLUDES . 'includes/class-wc-session-handler.php';
//	}
//
//	/**
//	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
//	 */
	public function include_template_functions() {
//		include_once WC_CAPABILITES_INCLUDES . 'includes/capabilites-template-functions.php';
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
//		print "init " . __CLASS__ . "<br/>";
		// Before init action.
		do_action( 'before_capabilites_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();
		$shortcodes = Core_Shortcodes::instance();
		$shortcodes->add( array( 'capabilities' => array( __CLASS__ . '::main', "edit_users" ) ) );

		// Init action.
		do_action( 'capabilites_init' );
	}

	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'capabilites' );

//		unload_textdomain( 'im-haadama' );
//		load_textdomain( 'capabilites', FERSH_LANG_DIR . '/capabilites/capabilites-' . $locale . '.mo' );
//		load_plugin_textdomain( 'capabilites', false, plugin_basename( dirname( CAPABILITES_PLUGIN_FILE ) ) . '/i18n/languages' );
	}
//
//	/**
//	 * Ensure theme and server variable compatibility and setup image sizes.
//	 */
	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		$this->define( 'CAPABILITES_TEMPLATE_PATH', $this->template_path() );

		// $this->add_thumbnail_support();
	}

	public function template_path() {
		return apply_filters( 'capabilites_template_path', 'capabilites/' );
	}

	public function run() {
		// $this->loader->run();
	}

	static public function RolesAndCapabilities()
	{
		if (! im_user_can("promote_users")) return;
		$result = Core_Html::GuiHeader(1, "Roles and capabilities");
		$roles = [];
		$tabs = array(array("Roles", "roles", self::roles($roles)),
			array("Capabilites", "capabilities", self::capabilites($roles)));

		$args = array("tabs_load_all" => true);
		$result .= Core_Html::GuiTabs("roles", $tabs, $args);

		print $result;
	}

	static function capabilites($roles)
	{
		if (is_string($roles)) $roles = array($roles);
		$result = Core_Html::GuiHeader(2, "Capabilities per role");

		global $wp_roles;

		$all_roles = $wp_roles->roles;
//		$caps = array("wpcf7_edit_contact_forms", "wpcf7_read_contact_forms");

		foreach ($roles as $role => $not_used) {
			$result .= Core_Html::GuiHeader( 3, "role $role" );
			if (! isset($all_roles[$role])) continue;
//			var_dump($all_roles[$role]);
//			die (1);
//			$editable_role = apply_filters('editable_roles', $all_roles[$role]);
			foreach ($all_roles[$role]['capabilities'] as $cap => $enabled) {
//				if (in_array($cap, $caps))
					$result .= $cap . " $enabled<br/>";
			}
		}

		return $result;
	}

	static public function roles(&$role_types) {
		$result = Core_Html::GuiHeader(2, "Roles");
		// 2D array of capabilithes[$user][$cap];
//		$role_types               = [];
		$users                   = [];
		$roles = [];
		// First pass - collect capablities and users
		foreach ( SqlQueryArray( "select user_id, meta_value from wp_usermeta where meta_key = 'wp_capabilities'" ) as $info ) {
			foreach ( unserialize( $info[1] ) as $capability => $flag ) $role_types[ $capability ] = 1;
			$users[ $info[0] ] = 1;
		}
		if (Flavor_Roles::getRoles()) foreach (Flavor_Roles::getRoles() as $role) $role_types[$role] = 1;

		$roles["header"]["users"] = "";
		foreach ( $role_types as $cap => $not_used ) {
			$roles["header"][ $cap ] = $cap;
		}
		foreach ( $users as $user => $not_used ) {
			 $u = new WP_User( $user );
			 if ((count($u->roles) == 1) and (array_intersect($u->roles, array('subscriber', 'customer')))) continue;
			$roles[ $user ]["users"] = get_user_displayname($user);
			foreach ( $role_types as $cap => $not_used ) {
				$roles[ $user ][ $cap ] = Core_Html::GuiCheckbox( "chk_${user}_$cap", user_can( $user, $cap ),
					array( "events" => 'onchange="toggle_role(\'' . Capabilites::getPost() . '\', ' . $user . ', \'' . $cap . '\')"' ) );
			}
		}

		$result .= Core_Html::gui_table_args( $roles );

		return $result;
	}

	static function admin_menu() {
		$menu = new Core_Admin_Menu();

		$menu->AddSubMenu( "users.php", "promote_users",
			array( 'page_title' => 'Site admins', 'function' => array( __CLASS__, 'RolesAndCapabilities' ) ) );

//		$menu->AddSubMenu( "users.php", "edit_shop_orders",
//			array( 'page_title' => 'Payment methods', 'function' => array( "Finance_Payments", 'payment_methods' ) ) );

	}

	static function getPost() {
		return "/wp-content/plugins/capabilities/post.php";
	}

	static function toggle_role() {
		if ( ! im_user_can( 'promote_users' ) ) {
			MyLog(get_current_user()  . " can't promote");
			die ( "no permissions" );
		}
		$user_id = GetParam( "user", true );
		$role  = GetParam( "role", true );
		$set = GetParam("set", true);
		MyLog(__FUNCTION__, "$user_id $role $set");

		$user = new WP_User($user_id);

		if ($set) {
			$user->add_role( $role );
		} else {
			$user->remove_role( $role );
		}
		return true;

/*		global $wp_roles;

		$wp_roles->add_role( "staff", "Worker", array( "working_hours_self" => "true" ) );*/

	}

	public function admin_scripts() {
		$file = CAPABILITIES_INCLUDES_URL . 'js/admin.js';
		wp_enqueue_script( 'capablities', $file, null, $this->version, false );
	}

	public function CreateRoles() {
//		print 1 / 0;
		global $wp_roles; // global class wp-includes/capabilities.php
		$wp_roles->remove_cap( 'shop_manager', 'wpcf7_read_contact_forms' );
	}

}