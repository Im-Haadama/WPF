<?php

class Fresh {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Delivery_Drivers_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	protected $auto_loader;
	protected $delivery_manager;
	protected $suppliers;
	protected $supplier_balance;
	protected $totals;
	protected $shortcodes;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.1';

	private $plugin_name;

	/**
	 * The single instance of the class.
	 *
	 * @var Fresh
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * fresh instance.
	 *
	 */
	public $fresh = null;

	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

	/**
	 * Main Fresh Instance.
	 *
	 * Ensures only one instance of Fresh is loaded or can be loaded.
	 *
	 * @static
	 * @return Fresh - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self("Fresh");
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		die( __FUNCTION__ .  __( 'Cloning is forbidden.', 'fresh' ));
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		fresh_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'fresh' ), '2.1' );
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
	    self::$_instance = $this;
		$this->plugin_name = $plugin_name;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		$this->loader = new Fresh_Loader();
		$this->auto_loader = new Core_Autoloader(FRESH_ABSPATH);

		$this->init_hooks();

		do_action( 'fresh_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
	    // Admin scripts and styles. Todo: Check if needed.
		add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

		// Can't make that work: register_activation_hook( __FILE__, array( $this, 'install' ) );
        self::install($this->version);

		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'Fresh_Shortcodes', 'init' ) );
		add_shortcode('pay-page', 'pay_page');
		add_shortcode( 'im-page', 'im_page' );
		add_action( 'init', 'register_awaiting_shipment_order_status' );
		add_action( 'woocommerce_checkout_process', 'wc_minimum_order_amount' );
		add_action( 'woocommerce_before_cart', 'wc_minimum_order_amount' );
		add_action( 'woocommerce_checkout_order_processed', 'wc_minimum_order_amount' );
		add_filter( 'woocommerce_available_shipping_methods', 'hide_shipping_if_cat_is_orange', 10, 1 );
		add_action( 'woocommerce_before_calculate_totals', 'im_woocommerce_update_price', 99 );
		add_filter( 'woocommerce_cart_item_price', 'im_show_nonsale_price', 10, 2 );
//		add_filter( 'woocommerce_order_button_text', 'im_custom_order_button_text' );
		add_action( 'init', 'custom_add_to_cart_quantity_handler' );
		add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );
		add_action( 'init', 'register_awaiting_shipment_order_status' );
		add_action('woocommerce_order_status_completed', 'Fresh_Order_Management::order_complete');

		add_filter( 'wc_order_statuses', 'add_awaiting_shipment_to_order_statuses' );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );
		// add_filter( 'woocommerce_package_rates' , 'im_sort_shipping_services_by_date', 10, 2 );

        // Handle basket
		add_shortcode( 'basket-content', 'content_func' );

		// Category content
		add_shortcode( 'category-content', 'category_content_func' );

		// Don't show "do you have coupon?"
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		// Make sessions longer
		add_filter( 'auth_cookie_expiration', 'wcs_users_logged_in_longer' );

		// Save payment info
		add_action('woocommerce_thankyou', 'insert_payment_info', 10, 1);
		add_action('admin_init', 'wp_payment_list_admin_script');
		add_action("admin_init", array(__CLASS__, "admin_load"));
		add_action('admin_print_styles', 'wp_payment_list_admin_styles');

		// Admin menu
		add_action('admin_menu', __CLASS__ . '::admin_menu');

		// Product order in category
		add_filter( 'woocommerce_default_catalog_orderby_options', 'sm_custom_woocommerce_catalog_orderby' );
		add_filter( 'woocommerce_catalog_orderby', 'sm_custom_woocommerce_catalog_orderby' );
		add_filter( 'woocommerce_get_catalog_ordering_args', 'sm_alphabetical_woocommerce_shop_ordering' );
		// remove unneeded sorting
		add_filter( 'woocommerce_catalog_orderby', 'sm_remove_sorting_option_woocommerce_shop' );

		add_action( 'init', array( $this, 'fresh_quantity_handler' ) );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'fresh_add_quantity_fields' ), 10, 2 );

		/* - Start Product Comment Hooks-- */
        add_action( 'woocommerce_update_cart_action_cart_updated', 'on_action_cart_updated', 20, 1 );
		add_action( 'woocommerce_checkout_create_order_line_item', 'checkout_create_order_line_item', 10, 4 );
        /* -- End Product Comment Hooks-- */

		get_sql_conn(ReconnectDb());
//		add_action( 'init', array( 'Fresh_Emails', 'init_transactional_emails' ) );
		// add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'init', array( $this, 'add_image_sizes' ) );
		// add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		$orders = new Fresh_Order_Management( $this->get_plugin_name(), $this->get_version() );
		$inventory = new Fresh_Inventory( $this->get_plugin_name(), $this->get_version(), self::getPost());

		$this->loader->add_action( 'wp_enqueue_scripts', $orders, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_enqueue_scripts', $inventory, 'enqueue_scripts' );

		Fresh_Packing::init_hooks();
		Fresh_Suppliers::init_hooks();
		Fresh_Order_Management::init_hooks();
		Fresh_Catalog::init_hooks();
		Fresh_Client::init_hooks();
		// if (get_user_id() == 1) wp_set_current_user(474);
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( isset($error['type']) and  in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ) ) ) {
			$logger = fresh_get_logger();
			$logger->critical(
			/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'fresh' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'fresh_shutdown_error', $error );
		}
	}

	static function admin_menu()
	{
	    Fresh_Settings::admin_menu();

		//               parent_slug, page_title,    menu_title,      capability,        memu_slug,     function, position
//			add_submenu_page('fresh',    'Packing',      'Packing',      'edit_shop_orders', 'fresh_packing', __CLASS__ . '::fresh_store_packing_page',
//				'dashicons-tickets', 6 );
//			add_menu_page( 'Fresh Store', 'ניהול ספקים', 'manage_options', 'im-haadama/supplier_account.php', 'fresh_store_supplier_account_page',
//				'dashicons-tickets', 6 );

			//	add_menu_page( 'Fresh Store', 'Fresh Store', 'manage_options', 'im-haadama/admin.php', 'fresh_store_admin_page',
//        'dashicons-tickets', 6 );
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'FRESH_ABSPATH', dirname( FRESH_PLUGIN_FILE ) . '/' );
		$this->define( 'FRESH_PLUGIN_BASENAME', plugin_basename( FRESH_PLUGIN_FILE ) );
		$this->define( 'FRESH_VERSION', $this->version );
		$this->define( 'FRESH_INCLUDES', FRESH_ABSPATH . '/includes/' );
		$this->define( 'FRESH_DELIMITER', '|' );
		$this->define( 'FRESH_LOG_DIR', $upload_dir['basedir'] . '/fresh-logs/' );
		$this->define( 'FRESH_INCLUDES_URL', plugins_url() . '/fresh/includes/' ); // For js
		$this->define( 'WC_URL', plugins_url() . '/woocommerce/' ); // For css

		$this->define( 'FLAVOR_INCLUDES_URL', plugins_url() . '/flavor/includes/' ); // For js
		$this->define( 'FLAVOR_INCLUDES_ABSPATH', plugin_dir_path(__FILE__) . '../../flavor/includes/' );  // for php
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
		$input = null;
		$result = apply_filters( $operation, $input, null);
		if ( $result ) return $result;

		$module = strtok($operation, "_");
		if ($module === "data")
			return Core_Data::handle_operation($operation);

		if ($module === "order")
			return Fresh_Order::handle_operation($operation);

		if (strstr($operation, "inv"))
			return Fresh_Inventory::handle_operation($operation);

		switch ($operation)
		{

			case "update":
				return handle_data_operation($operation);

			case "new_customer":
				$order_id = GetParam("order_id", true);
				return self::new_customer($order_id);

				// Robot operations:
			case "update_shipping_methods_anonymous":
				return $this->delivery_manager->update_shipping_methods();

		}
		return false;
	}

	static function new_customer($order_id)
	{
		$result = "";
		$result .= Core_Html::gui_header( 1, "לקוח חדש" );

		$O         = new Order( $order_id );
		$client_id = $O->getCustomerId();


		$result .= "1) צור קשר טלפוני עם הלקוח. עדכן אותו שהתקבלה ההזמנה.<br/>";
		$result .= "2) אמת את השם לחשבונית.<br/>";
		$result .= "3) אמת את הכתובת למשלוח. בדוק האם יש אינטרקום או קוד לגישה לדלת.<br/>";

		$step      = 4;

		$invoice_client_id = get_user_meta( $client_id, 'invoice_id', 1 );

		$result .= gui_table_args( array(
			$O->info_right_box_input( "shipping_city", true, "עיר" ),
			$O->info_right_box_input( "shipping_address_1", true, "רחוב ומספר" ),
			$O->info_right_box_input( "shipping_address_2", true, "כניסה, קוד אינטרקום, קומה ומספר דירה" )
		) );

		if ( ! $invoice_client_id ) {
			$result .=$step ++ . ") לחץ על צור משתמש - במערכת invoice4u";
			$result .=Core_Html::GuiButton( "btn_create_user", "create_user()", "צור משתמש" );
			$result .=Core_Html::GuiButton( "btn_update_user", "update_user()", "קשר משתמש" );
			$result .="<br/>";
		}

		$result .=$step ++ . ") קח/י פרטי תשלום" . Core_Html::GuiHyperlink( "כאן", "https://private.invoice4u.co.il/he/Customers/CustomerAddNew.aspx?type=edit&id=" . $client_id . "#tab-tokens" ) . "<br/>";
		$result .="<br/>";

		$result .=$O->infoBox();

		$result .= GuiHyperlink("לפתיחת ההזמנה", AddToUrl(array( "operation" => "show_order", "order_id" => $order_id)));

		print $result;
	}
	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		require_once FRESH_INCLUDES . 'class-fresh-autoloader.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php';
//
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/fund.php';
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/data/sql.php';
//		require_once FLAVOR_INCLUDES_ABSPATH . 'core/wp.php';

		/**
		 * Interfaces.
		 */

		/**
		 * Abstract classes.
		 */

		/**
		 * Core classes.
		 */
		include_once FRESH_INCLUDES . 'class-fresh-shortcodes.php';

		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-data-store.php';

		/**
		 * REST API.
		 */
//		include_once WC_FRESH_INCLUDES . 'includes/legacy/class-wc-legacy-api.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-api.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-auth.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-register-wp-admin-settings.php';

		/**
		 * Libraries
		 */
//		include_once WC_FRESH_INCLUDES . 'includes/libraries/action-scheduler/action-scheduler.php';
//
//		if ( defined( 'WP_CLI' ) && WP_CLI ) {
//			include_once WC_FRESH_INCLUDES . 'includes/class-wc-cli.php';
//		}
//
//		if ( $this->is_request( 'admin' ) ) {
//			include_once WC_FRESH_INCLUDES . 'includes/admin/class-wc-admin.php';
//		}
//
//		if ( $this->is_request( 'frontend' ) ) {
//			$this->frontend_includes();
//		}
//
//		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'woocommerce_allow_tracking', 'no' ) ) {
//			include_once WC_FRESH_INCLUDES . 'includes/class-wc-tracker.php';
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
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-ten.php';
//					break;
//				case 'twentyeleven':
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-eleven.php';
//					break;
//				case 'twentytwelve':
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-twelve.php';
//					break;
//				case 'twentythirteen':
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-thirteen.php';
//					break;
//				case 'twentyfourteen':
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-fourteen.php';
//					break;
//				case 'twentyfifteen':
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-fifteen.php';
//					break;
//				case 'twentysixteen':
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-sixteen.php';
//					break;
//				case 'twentyseventeen':
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-seventeen.php';
//					break;
//				case 'twentynineteen':
//					include_once WC_FRESH_INCLUDES . 'includes/theme-support/class-wc-twenty-nineteen.php';
//					break;
//			}
//		}
//	}
//
//	/**
//	 * Include required frontend files.
//	 */
//	public function frontend_includes() {
//		include_once WC_FRESH_INCLUDES . 'includes/wc-cart-functions.php';
//		include_once WC_FRESH_INCLUDES . 'includes/wc-notice-functions.php';
//		include_once WC_FRESH_INCLUDES . 'includes/wc-template-hooks.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-template-loader.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-frontend-scripts.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-form-handler.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-cart.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-tax.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-shipping-zones.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-customer.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-embed.php';
//		include_once WC_FRESH_INCLUDES . 'includes/class-wc-session-handler.php';
//	}
//
//	/**
//	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
//	 */
	public function include_template_functions() {
//		include_once WC_FRESH_INCLUDES . 'includes/fresh-template-functions.php';
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
//		print __CLASS__ . ':' . __FUNCTION__ . "<br/>";
		do_action( 'before_fresh_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();
		$this->delivery_manager = new Fresh_Delivery_Manager();
		$this->suppliers = new Fresh_Suppliers();
		$this->supplier_balance = Fresh_Supplier_Balance::instance();
		$this->totals = Fresh_Totals::instance();

		$shortcodes = Core_Shortcodes::instance();
		$shortcodes->add($this->suppliers->getShortcodes());
		$shortcodes->add($this->supplier_balance->getShortcodes());
		$shortcodes->add($this->totals->getShortcodes());

		$this->suppliers->init();
		Fresh_Basket::init();
		$this->delivery_manager->init();

		$this->enqueue_scripts();
		Core_Importer::instance();

		// Init action.
		do_action( 'fresh_init' );
	}

	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'fresh' );

		unload_textdomain( 'wpf' );
		if ($locale == 'en_US') return;

		$file = FRESH_ABSPATH . 'languages/wpf-' . $locale . '.mo';
		$rc = load_textdomain( 'wfp', $file );
//		print "loaded $file $rc <br/>";
//		$rc1 = load_plugin_textdomain( 'wfp');
		if (get_user_id() == 1) {
			if (! $rc) print "can't load textdomain";
//			if (! $rc1) print "can't load plugin_textdomain";
			if (! file_exists($file)) print "file $file not found";
//			print $file . "<br/>";
//			print "Rc= $rc";
		}
	}

	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		$this->define( 'FRESH_TEMPLATE_PATH', $this->template_path() );
	}

	public function template_path() {
		return apply_filters( 'fresh_template_path', 'fresh/' );
	}

	public function run()
	{
		$this->loader->run();

		// Install tables
		self::register_activation(dirname(__FILE__) . '/class-fresh-database.php', array('Fresh_Database', 'payment_info_table'));

		// Temp migration. run once on each installation
        // Fresh_Database::convert_supplier_name_to_id();

		// Create functions, tables, etc.
	}

	static function register_activation($file, $function)
	{
		if (! file_exists($file)){
			print "file $file not exists";
			return;
		}
		if (! is_callable($function)){
			print "function is not callable";
			return;
		}
		register_activation_hook($file, $function);
	}

	static public function SettingPage()
	{
		$result = "";
//		$pages = array(array("name" => "Suppliers", "target" => "/suppliers", "shortcode" => "fresh_suppliers"));
//		foreach ($pages as $page)
//			Core_Pages::CreateIfNeeded($page['name'], $page['target'], $page['shortcode']);
//
//		$module_list = array( "Suppliers" => array("target"=>"/suppliers"),
//		                      "Orders" => array(array("Total ordered", "total_ordered")));

//		$result .= Flavor::ClassSettingPage($module_list);
		return $result;
	}

	static function getPost()
	{
		return "/wp-content/plugins/fresh/post.php";
	}

	public function enqueue_scripts() {
		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

	//		$file = plugin_dir_url( __FILE__ ) . 'inventory.js';
	//		wp_enqueue_script( $this->plugin_name, $file, array( 'jquery' ), $this->version, false );

//		wp_register_script('add_to_cart_on_search', plugin_dir_url( __FILE__ ) . 'js/add_to_cart_on_search.js');
//		wp_localize_script('add_to_cart_on_search', 'wc_add_to_cart_params', array());
		wp_enqueue_script('add_to_cart_on_search', plugin_dir_url( __FILE__ ) . 'js/add_to_cart_on_search.js', array("jquery"));

//		$rc1 = wp_enqueue_script( 'my_custom_script', plugin_dir_url( __FILE__ ) . 'js/add_to_cart_on_search.js', array('jquery') );
		wp_enqueue_script( 'custom_script', plugin_dir_url( __FILE__ ) . 'js/custom_script.js' );
//		MyLog(__FUNCTION__ . ": $rc1 $rc2");
	}

	public function admin_scripts()
    {
        $file = FRESH_INCLUDES_URL . 'js/admin.js';
	    wp_register_script( 'fresh_admin', $file);

	    $params = array(
	    	'admin_post' => get_site_url() . '/wp-content/plugins/fresh/post.php'
	    );
	    wp_localize_script('fresh_admin', 'fresh_admin_params', $params);

	    wp_enqueue_script('fresh_admin');

	    wp_register_script( 'orders', FRESH_INCLUDES_URL . 'js/orders.js');
	    wp_enqueue_script('orders');

	    if (defined('WC_VERSION')){
            wp_register_style( 'woocommerce_admin_menu_styles', WC_URL . '/assets/css/menu.css', array(), WC_VERSION );
            wp_register_style( 'woocommerce_admin_styles', WC_URL . '/assets/css/admin.css', array(), WC_VERSION );

            wp_enqueue_style('woocommerce_admin_menu_styles');
            wp_enqueue_style('woocommerce_admin_styles');
	    }

	    $file = FRESH_INCLUDES_URL . 'js/delivery.js';
	    wp_enqueue_script( 'delivery', $file, null, $this->version, false );
    }

	/*-- Start product quantity +/- on listing -- */
	public function fresh_add_quantity_fields($html, $product) {
		if ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually() ) {
			$html = '<form action="' . esc_url( $product->add_to_cart_url() ) . '" class="cart" method="post" enctype="multipart/form-data">';
			$html .= woocommerce_quantity_input( array(), $product, false );
			$html .= '<button type="submit" data-quantity="1" data-product_id="' . $product->id . '" class="button alt ajax_add_to_cart add_to_cart_button product_type_simple">' . esc_html( $product->add_to_cart_text() ) . '</button>';
			$html .= '</form>';
		}
		return $html;
	}

	public function fresh_quantity_handler() {
	    if (! function_exists('wc_enqueue_js')) return;
		wc_enqueue_js( '
		jQuery(function($) {
		$("form.cart").on("change", "input.qty", function() {
        $(this.form).find("[data-quantity]").data("quantity", this.value);
		});
		' );

		wc_enqueue_js( '
		$(document.body).on("adding_to_cart", function() {
			$("a.added_to_cart").remove();
		});
		});
		' );
	}
	/*-- End product quantity +/- on listing -- */

	function install($version, $force = false)
	{
        if (Fresh_Database::CheckInstalled("Fresh", $this->version) == $version and ! $force) return;

        // Install common tables
        Fresh_Database::install($this->version);

        // Install more specific
	}

	static public function admin_load()
	{
		new Fresh_Settings();
	}
}

function category_content_func($atts, $content, $tag)
{
//	if (! file_exists(FRESH_ABSPATH . '/fresh/wp/Product.php')) return "not installed";

//	require_once (FRESH_ABSPATH . '/fresh/wp/Product.php');
//
	$my_atts = shortcode_atts( [ 'id' => get_the_ID() ], $atts, $tag );
//
	$id = $my_atts['id'];

	new Fresh_Product(1); // To load the class.
	$iter = new Fresh_ProductIterator();
	$iter->iterateCategory( $id );

	$result = "";
	while ( $prod_id = $iter->next()) {
		$p = new Fresh_Product($prod_id);
		$prod_name = $p->getName(true);
		$result .= trim($prod_name) . ", ";
	}

	return rtrim($result, ", ");
}

function content_func( $atts, $contents, $tag )
{

	$my_atts = shortcode_atts( [ 'id' => get_the_ID() ], $atts, $tag );
//
	$id = $my_atts['id'];

	$b = new Fresh_Basket($id);

	$text = "תכולת הסל: ";
	$text .= $b->get_basket_content( $id );

	return $text;
}

function im_woocommerce_update_price()
{
	MyLog( "cart start" );
	// TWEEK. Don't know why menu_op calls this method.
	// DONT remove without trying menu.php and cart.
	if (! sql_query_single_scalar("select 1")) {
		MyLog ("not connected to db");
		return;
	}
	if (! function_exists('get_user_id') or ! get_user_id()) return;
	$user_id = get_user_id();
	$user = new Fresh_Client($user_id );
	$client_type = $user->customer_type( );

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$prod_id = $cart_item['product_id'];
		$variation_id = $cart_item['variation_id'];
		if ( ! ( $prod_id > 0 ) ) {
			MyLog( "cart - no prod_id" );
			continue;
		}
		$q          = $cart_item['quantity'];
		$sell_price = Fresh_Pricing::get_price_by_type( $prod_id, $client_type, $q, $variation_id );
		//my_log("set " . $sell_price);
		$cart_item['data']->set_sale_price( $sell_price );
		$cart_item['data']->set_price( $sell_price );
		MyLog( $prod_id . " " . $q );

	}
	//		ob_start();
}

function im_show_nonsale_price( $newprice, $product ) {
	global $site_id;
	if ( $site_id != 4 ) {
		return $newprice;
	}
	$_product   = $product['data'];
	$sale_price = $_product->get_sale_price();
	if ( ( $sale_price > 0 ) and ( $_product->get_sale_price() < $_product->get_regular_price() ) ) {
		$newprice = '';
		$newprice .= '<del><small style="color:#000000;">';
		$newprice .= wc_price( $_product->get_regular_price() );
		$newprice .= '</small></del> <strong>';
		$newprice .= wc_price( $sale_price );
		$newprice .= '</strong>';

		return $newprice;
	} else {
		$newprice = wc_price( $_product->price );

		return $newprice;
	}
}

function custom_add_to_cart_quantity_handler() {
	if ( function_exists( 'wc_enqueue_js' ) ) {
		wc_enqueue_js( '
		jQuery( ".input-text.qty.text" ).on( "change input", ".quantity", function() {
			var add_to_cart_button = jQuery( this ).parents( ".product" ).find( ".add_to_cart_button" );

			// For AJAX add-to-cart actions
			add_to_cart_button.attr( "data-quantity", jQuery( this ).val() );
			alert("XX");

			// For non-AJAX add-to-cart actions
			add_to_cart_button.attr( "href", "?add-to-cart=" + add_to_cart_button.attr( "data-product_id" ) + "&XXXX&quantity=" + jQuery( this ).val() );
		});
	' );
	}
}

function register_awaiting_shipment_order_status() {
	register_post_status( 'wc-awaiting-shipment', array(
		'label'                     => 'ממתין למשלוח',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'ממתין למשלוח <span class="count">(%s)</span>', 'Awaiting shipment <span class="count">(%s)</span>' )
	) );

	register_post_status( 'wc-awaiting-document', array(
		'label'                     => 'Awaiting shipment document',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Awaiting shipment document<span class="count">(%s)</span>', 'Awaiting shipment <span class="count">(%s)</span>' )
	) );
}

// Add to list of WC Order statuses
function add_awaiting_shipment_to_order_statuses( $order_statuses ) {

	$new_order_statuses = array();

	// add new order status after processing
	foreach ( $order_statuses as $key => $status ) {

		$new_order_statuses[ $key ] = $status;

		if ( 'wc-processing' === $key ) {
			$new_order_statuses['wc-awaiting-shipment'] = 'ממתין למשלוח';
			$new_order_statuses['wc-awaiting-document'] = 'ממתין לתעודת משלוח';
		}
	}

	return $new_order_statuses;
}

function im_sort_shipping_services_by_date($rates, $package)
{
	$logger = new Core_Logger("check");
	if ( ! $rates )  return;

	$rate_date = array();
	foreach( $rates as $rate ) {
		preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $rate->label,$matches);
		if (isset($matches[0][0])) {
			$date = str_replace( '/', '-', $matches[0][0] );
		} else $date = '1/2/2030'; // Show local pickup in the end of the list.
		$rate_date[] = strtotime($date);
	}

	// using rate_cost, sort rates.
	array_multisort( $rate_date, $rates );

	return $rates;
}

function PayPage($atts, $content = null)
{
	if (get_user_id()) {
		print do_shortcode("[woocommerce_checkout]");
		return;
	}
	print ImTranslate("In order to complete your order, register to this site.") . "<br/>";
	print ImTranslate("You can use existing user or create local user in the form below.") . "<br/>";
	print do_shortcode('[miniorange_social_login shape="longbuttonwithtext" theme="default" space="8" width="180" height="35" color="000000"]');

	print do_shortcode('[woocommerce_checkout]');

	print ImTranslate("Or with one of the following.") . "<br/>";

	return;
}

// in functions_im thema
function wc_minimum_order_amount() {
	global $woocommerce;

	$minimum = Fresh_Order::get_minimum_order($woocommerce->customer->get_id());

	if ( WC()->cart->total - WC()->cart->shipping_total < $minimum ) {
		if ( is_cart() ) {
			wc_print_notice(
				sprintf( 'הזמנת מינימום לאזורך %s. סך ההזמנה עד כה  %s.',
					wc_price( $minimum ),
					wc_price( WC()->cart->total - WC()->cart->shipping_total )
				), 'error'
			);

		} else {
			wc_add_notice(
				sprintf( 'הזמנת מינימום לאזורך %s. סך ההזמנה עד כה  %s.',
					wc_price( $minimum ),
					wc_price( WC()->cart->total - WC()->cart->shipping_total )
				), 'error'
			);
		}
	}
}

add_shortcode('pay-page', 'pay_page');

function pay_page($atts, $content = null)
{
	if (get_user_id()) {
		print do_shortcode("[woocommerce_checkout]");
		return;
	}
	print ImTranslate("In order to complete your order, register to this site.") . "<br/>";
	print ImTranslate("You can use existing user or create local user in the form below.") . "<br/>";
	print do_shortcode('[miniorange_social_login shape="longbuttonwithtext" theme="default" space="8" width="180" height="35" color="000000"]');

	print do_shortcode('[woocommerce_checkout]');

	print ImTranslate("Or with one of the following.") . "<br/>";

	return;
}

/*-- Start product search filter --*/
function searchfilter($query)
{
    return $query; // Agla - include post in the messages.
	if ($query->is_search && !is_admin() ) {
		$query->set('post_type',array('product'));
	}

	return $query;
}

add_filter('pre_get_posts','searchfilter');

/*-- End product search filter --*/


/*-- Start custom add to cart search product --*/

add_action('wp_ajax_ql_woocommerce_ajax_add_to_cart', 'sm_woocommerce_ajax_add_to_cart');
add_action('wp_ajax_nopriv_ql_woocommerce_ajax_add_to_cart', 'sm_woocommerce_ajax_add_to_cart');

function sm_woocommerce_ajax_add_to_cart() {

	$product_id = apply_filters('ql_woocommerce_add_to_cart_product_id', absint($_POST['product_id']));

	$quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);

	$variation_id = absint($_POST['variation_id']);

	$passed_validation = apply_filters('ql_woocommerce_add_to_cart_validation', true, $product_id, $quantity);

	$product_status = get_post_status($product_id);

	if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id) && 'publish' === $product_status) {

		do_action('ql_woocommerce_ajax_added_to_cart', $product_id);

		if ('yes' === get_option('ql_woocommerce_cart_redirect_after_add')) {

			wc_add_to_cart_message(array($product_id => $quantity), true);

		}

		WC_AJAX :: get_refreshed_fragments();

	} else {

		$data = array(

			'error' => true,

			'product_url' => apply_filters('ql_woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id));

		echo wp_send_json($data);

	}
	wp_die();
}

/*-- End custom add to cart search product --*/

/*-- Start remove product sorting option --*/

function sm_remove_sorting_option_woocommerce_shop( $options ) {
	unset( $options['rating'] );
	unset( $options['price'] );
	unset( $options['date'] );
	unset( $options['price-desc'] );
	return $options;
}
/*-- End remove product sorting option --*/

/*-- Start add alphabetical product sort option --*/

function sm_alphabetical_woocommerce_shop_ordering( $sort_args ) {
	$orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

	if ( 'alphabetical' == $orderby_value ) {
		$sort_args['orderby'] = 'title';
		$sort_args['order'] = 'asc';
		$sort_args['meta_key'] = '';
	}

	return $sort_args;
}

function sm_custom_woocommerce_catalog_orderby( $sortby ) {
	$sortby['alphabetical'] = 'Sort by name';
	return $sortby;
}

/*-- End add alphabetical product sort option --*/


/*-- Start add menu page-- */
function payment_list() {
	include( FRESH_INCLUDES . 'payment_list.php' );
}
/*-- end menu page-- */

/*-- Start add css & js-- */
function wp_payment_list_admin_styles()
{
	if (isset($_GET['page']) && $_GET['page'] == 'payment_list')
	{
		wp_register_style('jquery_ui4', plugins_url().'/fresh/css/jquery-ui.min4.css');
		wp_enqueue_style('jquery_ui4');

		wp_register_style('bootstrap.min', plugins_url().'/fresh/css/bootstrap.min.css');
		wp_enqueue_style('bootstrap.min');

		wp_register_style('dataTables.bootstrap.min', plugins_url().'/fresh/css/dataTables.bootstrap.min.css',array(), '1.10.16');
		wp_enqueue_style('dataTables.bootstrap.min');

		wp_register_style('custom', plugins_url().'/fresh/css/custom.css');
		wp_enqueue_style('custom');

		wp_register_style('jquery-ui', plugins_url().'/fresh/css/jquery-ui.css');
		wp_enqueue_style('jquery-ui');
	}
}

function wp_payment_list_admin_script() {

	wp_enqueue_script( 'dataTables.min', plugins_url(). '/fresh/includes/js/jquery.dataTables.min.js',array('jquery') );

	wp_enqueue_script( 'dataTables.bootstrap.min', plugins_url(). '/fresh/includes/js/dataTables.bootstrap.min.js' );

	wp_enqueue_script( 'dataTables.buttons.min', plugins_url(). '/fresh/includes/js/dataTables.buttons.min.js' );

}
/*-- End add css & js-- */


/*-- Start save payment info --*/

function setCreditCard($cc)
{
	$cc_length = strlen($cc);

	for($i=0; $i<$cc_length-4; $i++){
		if($cc[$i] == '-'){continue;}
		$cc[$i] = 'X';
	}
	return $cc;
}

function insert_payment_info( $order_id )
{
	if ( ! $order_id ) return;
	if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {

		$order = wc_get_order( $order_id );


		$first_name = get_post_meta($order_id, '_billing_first_name', TRUE);
		$last_name = get_post_meta($order_id, '_billing_last_name', TRUE);
		$full_name = $first_name.' '.$last_name;
		$billing_email = get_post_meta($order_id, '_billing_email', TRUE);
		$card_number = get_post_meta($order_id, 'card_number', TRUE);
		

		$card_last_4_digit = setCreditCard($card_number);
		$card_type = get_post_meta($order_id, 'card_type', TRUE);
		$exp_date_month = get_post_meta($order_id, 'expdate_month', TRUE);
		$exp_date_year = get_post_meta($order_id, 'expdate_year', TRUE);
		$cvv_number = get_post_meta($order_id, 'cvv_number', TRUE);
		$billing_id_number = get_post_meta($order_id, 'id_number', TRUE);

		if($card_number != ''){
			global $wpdb;
			$table = 'im_payment_info';
			$data = array('full_name' => $full_name, 'email' => $billing_email, 'card_number' => $card_number, 'card_four_digit' => $card_last_4_digit, 'card_type' => $card_type, 'exp_date_month' => $exp_date_month, 'exp_date_year' => $exp_date_year, 'cvv_number' => $cvv_number,'id_number' => $billing_id_number );
			$wpdb->insert($table,$data);
			$last_id = $wpdb->insert_id;
			if($last_id){
				delete_post_meta($order_id, 'card_number');
				delete_post_meta($order_id, 'card_type');
				delete_post_meta($order_id, 'expdate_month');
				delete_post_meta($order_id, 'expdate_year');
				delete_post_meta($order_id, 'cvv_number');
				delete_post_meta($order_id, 'id_number');
			}
		}
	}
}
/*-- End save payment info --*/


/*-- Start payment gateway--*/
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(fresh_custom_payment_is_woocommerce_active()){
	add_filter('woocommerce_payment_gateways', 'add_other_payment_gateway');
	function add_other_payment_gateway( $gateways ){
		$gateways[] = 'WC_Other_Payment_Gateway';
		return $gateways;
	}

	add_action('plugins_loaded', 'init_other_payment_gateway');
	function init_other_payment_gateway(){
		 require FRESH_INCLUDES . 'class-fresh-payment-gateway.php';
	}

	add_action( 'plugins_loaded', 'other_payment_load_plugin_textdomain' );
	function other_payment_load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-other-payment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	function install()
    {
        print 1/0;
    }
}

function fresh_custom_payment_is_woocommerce_active()
{
	$active_plugins = (array) get_option('active_plugins', array());

	if (is_multisite()) {
		$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	}

	return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

/*-- End payment gateway--*/
function wcs_users_logged_in_longer( $expire ) {
	// 1 month in seconds
	return 2628000;
}

add_filter( 'woocommerce_register_post_type_product', 'cinch_add_revision_support' );

function cinch_add_revision_support( $args ) {
	$args['supports'][] = 'revisions';

	return $args;
}

/* - Start Product Comment-- */

function on_action_cart_updated( $cart_updated ){
    global $wpdb;

    $cart = WC()->cart;
    if ( ! $cart->is_empty()) {
		foreach ($cart->get_cart() as $cart_item_key => $cart_item ) {
			$product_comment = '';
            foreach($_REQUEST['cart'] as $key => $val){
	            if($key == $cart_item_key)	
                {
               	   $product_comment = $val['product_comment'];
                }
	        } 
            $cart_item['product_comment'] = $product_comment; 
            $cart->cart_contents[$cart_item_key] = $cart_item;
            $current_user = wp_get_current_user();
			$user_id = $current_user->ID;
			$remove_comment = delete_user_meta($user_id,'product_comment_'.$cart_item['product_id']);
		}
		$cart->set_session();
	}	
   
}

function checkout_create_order_line_item( $item, $cart_item_key, $values, $order ){
	foreach( $item as $cart_item_key=>$cart_item ) {
		if( isset( $cart_item['product_comment'] ) && $cart_item['product_comment'] != '') {
		   $item->add_meta_data( 'product_comment', $cart_item['product_comment'], true );
		    if(is_user_logged_in()){ 
	            $current_user = wp_get_current_user();
	            $user_id = $current_user->ID;
	            if($cart_item['product_comment'] != ''){
	                update_user_meta($user_id,'product_comment_'.$cart_item['product_id'],$cart_item['product_comment']);
	            }
            }
		   
		}
	}
}

//$y = new Yaad_Pay('b6589fc6ab0dc82cf12099d1c2d40ab994e8410c', '0010142214');
//
//if (!$y->SignIn()) die ("can't sign in");
//
////foreach($result as $k => $r)
////	print $k . "=" . $r . "<br/>";
//
//$token = "2818986124534250000";
//
////var_dump($y->GetToken("16123298"));
//foreach ($y->Pay($token, 333) as $k => $r)
//	print $k . "=" . $r . "<Br/>";

/* -- End Product Comment-- */

//add_filter('woocommerce_shipping_zone_shipping_methods', 'zone_shipping');
//
//function zone_shipping($zone)
//{
////    $zone['price'] = array("id"=>4);
//    var_dump($zone);
//    return $zone;
//}