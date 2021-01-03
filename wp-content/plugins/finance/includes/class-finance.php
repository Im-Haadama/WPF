<?php

require_once(ABSPATH . '/wp-content/plugins/flavor/flavor.php');

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

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
	protected $invoices;
	protected $post_file;
	protected $yaad;
	protected $clients;
	protected $admin_notices;
	protected $database;
	protected $subcontract;
	protected $salary;
	protected $message;

	/**
	 * @return mixed
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.7.10';

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
	/**
	 * @var Core_Autoloader
	 */
	private $auto_loader;

	public function get_plugin_name() {
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
			self::$_instance = new self( "Finance" );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		die( __FUNCTION__ . __( 'Cloning is forbidden.', 'finance' ) );
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
	private function __construct( $plugin_name ) {
		global $business_name;
		$hook_manager = Core_Hook_Handler::instance();
		$this->admin_notices = null;
		$this->plugin_name = $plugin_name;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		if ( ! defined( 'FINANCE_ABSPATH' ) ) {
			die ( "not defined" );
		}
		$this->auto_loader      = new Core_Autoloader( FINANCE_ABSPATH );
		$this->loader = Core_Hook_Handler::instance();
		$this->post_file   = Flavor::getPost();
		$this->yaad        = null;
		$this->clients     = new Finance_Client_Accounts();
		$this->subcontract = new Finance_Subcontract();
		$this->salary      = new Finance_Salary();

		$inventory = new Finance_Inventory( $this->get_plugin_name(), $this->get_version(), self::getPost());
		$this->loader->AddAction( 'admin_enqueue_scripts', $inventory, 'admin_scripts' );

		$finance_actions = new Finance_Actions();
		$finance_actions->init_hooks($this->loader);

		$bl = new Finance_Business_Logic();
		$bl->init_hooks($hook_manager);

		$this->init_hooks($this->loader);

		do_action( 'finance_loaded' );
	}

	/**
	 * @return string
	 */
	static public function getPostFile(): string {
		return self::instance()->post_file;
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks(Core_Hook_Handler $loader) {
		// Flavor::getInstance();
		// register_activation_hook( WC_PLUGIN_FILE, array( 'Finance_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
		self::register_payment();

		GetSqlConn( ReconnectDb() );

		self::install( $this->version );

		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 11 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );
		add_action( 'admin_notices', array($this, 'admin_notices') );

		// Admin menu
		add_action( 'admin_menu',array($this, 'admin_menu') );

		GetSqlConn( ReconnectDb() );
//		add_action( 'init', array( 'Finance_Emails', 'init_transactional_emails' ) );
		// add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'init', array( $this, 'add_image_sizes' ) );
		// add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		AddAction('finance_get_open_site_invoices', array($this, 'get_open_site_invoices'));

		if ( $this->yaad ) $this->yaad->init_hooks();
		if ( $this->clients ) $this->clients->init_hooks($loader);

		Finance_Order_Management::instance()->init_hooks($this->loader);

		$this->payments = Finance_Payments::instance();
		$this->payments->init_hooks();
		$this->subcontract->init_hooks();
		$this->salary->init_hooks();

		$this->loader->AddAction('multisite_connect', $this, 'multisite_connect');
		$this->loader->AddAction('multisite_validate', $this, 'multisite_validate');

		$this->loader->AddAction("get_open_invoices", $this, 'get_open_invoices');
		$this->loader->AddAction("get_open_trans", $this, 'get_open_trans');
		$this->loader->AddAction("exists_invoice", $this, 'exists_invoice');

		Finance_Inventory::instance()->init_hooks($this->loader);

		$i = Core_Db_MultiSite::getInstance();
		$i->AddTable("missions");
		$i->AddTable("cities");
		$i->AddTable("woocommerce_shipping_zones", "zone_id" );
		$i->AddTable("woocommerce_shipping_zone_methods", "instance_id" );
		$i->AddTable("woocommerce_shipping_zone_locations", "location_id" );
		Core_Gem::getInstance()->AddTable( "multisite" );

		Finance_Delivery::init_hooks($this->loader);
	}

	function register_payment()
	{
//		$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
		if(finance_custom_payment_is_woocommerce_active()){
			add_filter('woocommerce_payment_gateways', array($this, 'add_payment_gateway'));

			add_action('plugins_loaded', array($this, 'init_payment_gateway'));

			add_action( 'plugins_loaded', array($this, 'other_payment_load_plugin_textdomain'));
		}
	}

	function add_payment_gateway( $gateways ){
		$gateways[] = 'E_Fresh_Payment_Gateway';
		return $gateways;
	}

	function init_payment_gateway(){
		require FINANCE_INCLUDES . 'class-e-fresh-payment-gateway.php';
	}

	function other_payment_load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-other-payment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	function get_open_invoices()
	{
		$multi_site = Core_Db_MultiSite::getInstance();

		$debug       = GetParam( "debug" );
		$supplier_id = GetParam( "supplier_id", true );
		$site_id     = GetParam( "site_id", true );

		// $func, $site_id, $first = false, $debug = false ) {
		$url = Finance::getPostFile() . "?operation=finance_get_open_site_invoices&supplier_id=" . $supplier_id;
		print  $multi_site->Run( $url, $site_id, true, $debug );

	}

	/**
	 * @return bool true on sucess. If failed see message.
	 */

	function pay()
	{
		global $business_name;
		$this->yaad = new Finance_Yaad( YAAD_API_KEY, YAAD_TERMINAL, $business_name );
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( isset( $error['type'] ) and in_array( $error['type'], array(
				E_ERROR,
				E_PARSE,
				E_COMPILE_ERROR,
				E_USER_ERROR,
				E_RECOVERABLE_ERROR
			) ) ) {
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

		define_const( 'FINANCE_ABSPATH', dirname( FINANCE_PLUGIN_FILE ) . '/' );
		define_const( 'FINANCE_PLUGIN_BASENAME', plugin_basename( FINANCE_PLUGIN_FILE ) );
		define_const( 'FINANCE_VERSION', $this->version );
		define_const( 'FINANCE_INCLUDES', FINANCE_ABSPATH . 'includes/' );
		define_const( 'FINANCE_INCLUDES_URL', plugins_url() . '/finance/includes/' ); // For js
		define_const( 'FLAVOR_INCLUDES_ABSPATH', plugin_dir_path( __FILE__ ) . '../../flavor/includes/' );  // for php
		define_const( 'FINANCE_DELIMITER', '|' );
		define_const( 'FINANCE_LOG_DIR', $upload_dir['basedir'] . '/finance-logs/' );
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
		$ignore_list = array( "operation" );
		$input       = null;

		if ( strstr( $operation, 'gem' ) ) {
			Core_Gem::getInstance();
		}

		////////////////////////
		// called by post.php //
		////////////////////////
		$result = apply_filters( $operation, $input, GetParams( $ignore_list ) );
		if ( $result !== null ) {
			return $result;
		}

		$yaad = Finance::instance()->yaad;
		if ( $yaad ) {
			$yaad->setDebug( false );
		}
		$module     = strtok( $operation, "_" );
		$multi_site = Core_Db_MultiSite::getInstance();

		$result = apply_filters( $operation, "" );
		if ( $result ) {
			return $result;
		}

		print "opeartion $operation not handled</br>";

		return false;
	}

	function exists_invoice() {
		$bank_id = GetParam( "bank_id", true );
		$invoice = GetParam( "invoice", true );
		$b       = Finance_Bank_Transaction::createFromDB( $bank_id );

		return $b->Update( 0, $invoice, 0 );
	}

	static function get_open_trans() {
		$multi_site = Core_Db_MultiSite::getInstance();

		$client_id = GetParam( "client_id", true );
		$site_id   = GetParam( "site_id", false, null );
		if ( ! $site_id ) {
			print Fresh_Client_Views::show_trans( $client_id, TransView::not_paid );
			return true;
		}
// $data .= $this->Run( $func, $site_id, $first, $debug );
		$link = Finance::getPostFile() . "?operation=get_open_trans&client_id=" . $client_id;
//		print $link;
		print $multi_site->Run( $link, $site_id );

		return true;
	}

	static function get_open_site_invoices()
	{
		$debug = GetParam("debug");
		$sum         = array();
		$supplier_id = GetParam( "supplier_id", true );
		$sql         = "SELECT id, ref, amount, date FROM im_business_info WHERE part_id=" . $supplier_id .
		               " AND document_type = 4\n" .
		               " and pay_date is null " .
		               " order by 4 desc";

		$args = array();
		if ($debug) $args["debug"] = true;
		$args["add_checkbox"] = true;
		$args["checkbox_events"] = "onchange = \"update_display()\"";
		$args["checkbox_class"] = "trans_checkbox";
		print Core_Html::GuiTableContent("table_invoices", $sql, $args);
		die (0);
	}

	static function delete_transaction( $ref ) {
		$sql = "DELETE FROM im_business_info "
		       . " WHERE ref = " . $ref;

		MyLog( $sql, __FILE__ );
		if (SqlQuery( $sql )) return true;
		return false;
	}

	static function admin_menu() {
		Finance_Settings::instance()->admin_menu();
		Finance_Inventory::instance()->admin_menu();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		if ( ! class_exists( 'Core_Autoloader' ) ) {
			$f = FLAVOR_INCLUDES_ABSPATH . 'core/class-core-autoloader.php';
			if ( ! file_exists( $f ) ) {
				return false;
			}

			require_once $f;

		}
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/fund.php';
		require_once FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php';
//	collides with old pages.	require_once FLAVOR_INCLUDES_ABSPATH . 'core/fund.php';
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
	public function include_template_functions() {
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_finance_init' );

		$this->shortcodes = Core_Shortcodes::instance();

		// Set up localisation.
		$this->load_plugin_textdomain();

		$this->payments   = Finance_Payments::instance();
		$this->shortcodes->add( $this->payments->getShortcodes() );

		$this->invoices   = Finance_Invoices::instance();
		$this->shortcodes->add( $this->invoices->getShortcodes() );

		$this->shortcodes->do_init();

		$this->invoices->init( Flavor::getPost() );

//		InfoUpdate("finance_bank_enabled", 1);
		if (InfoGet("finance_bank_enabled")) {
			$this->bank = new Finance_Bank( self::getPostFile() );
			$this->bank->init_hooks($this->loader);
			$this->shortcodes = Core_Shortcodes::instance();
			$this->shortcodes->add( $this->bank->getShortcodes() );
		}
		self::addRoles();
//		if (get_user_id() == 1) wp_set_current_user(2); // e-fresh
		Finance_Delivery::init();
	}

	function addRoles()
	{
		Flavor_Roles::instance()->addRole("staff");
		Flavor_Roles::instance()->addRole("shop_manager");
		Flavor_Roles::instance()->addRole("hr", array("working_hours_report", "show_salary"));
		Flavor_Roles::instance()->addRole("cfo", array("finance_bank"));
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
		$rc = load_textdomain( 'finance', FINANCE_PLUGIN_DIR . '/languages/finance-' . $locale . '.mo' );
//		print "rc=$rc<br/>";
//		print "E=$locale " . _e("Credit Card", "finance");
//		load_plugin_textdomain( 'finance', false, plugin_basename( dirname( FINANCE_PLUGIN_FILE ) ) . '/i18n/languages' );
	}
//
//	/**
//	 * Ensure theme and server variable compatibility and setup image sizes.
//	 */
	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		define_const( 'FINANCE_TEMPLATE_PATH', $this->template_path() );

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
	}

	public function admin_scripts() {
		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

		$file = FINANCE_INCLUDES_URL . 'finance.js?v=1';
		wp_enqueue_script( 'finance', $file, null, $this->version, false );

		$file = FINANCE_INCLUDES_URL . 'business.js';
		wp_enqueue_script( 'business', $file, null, $this->version, false );

		$file = FINANCE_INCLUDES_URL . 'account.js';
		wp_enqueue_script( 'account', $file, null, $this->version, false );

		$file = FINANCE_INCLUDES_URL . 'multisite.js';
		wp_enqueue_script( 'multisite', $file, null, $this->version, false );

	    $file = FINANCE_INCLUDES_URL . 'delivery.js';
	    wp_enqueue_script( 'delivery', $file, null, $this->version, false );

	}

	public function run() {

	}

	function install( $version, $force = false ) {
		$this->database = new Finance_Database("Finance");
		$this->database->install($this->version);
	}

	static public function settingPage() {
		$result = "";
		//                     Top nav                  Sub nav             target,                              capability
//		$module_list = array( "Finance" => array(array("Bank transactions", "/finance_bank",                     "show_bank"),
//								                 array("Bank Receipts",     "/finance_bank?operation=receipts",  "show_bank"),
//										   		 array("Invoices",          "/invoices",  "edit_pricelist"),
//												 array("Bank payments",    "/finance_bank?operation=payments",  "show_bank"),
//												 array("Transactions types", "/finance_bank?operation=bank_transaction_types", "cfo")));

//		$result .= Flavor::ClassSettingPage($module_list);
		return $result;
	}

	static function add_transaction(
		$part_id, $date, $amount, $delivery_fee, $ref, $project, $net_amount = 0,
		$document_type = Finance_DocumentType::delivery,
		$document_file = null
	) {
		// print $date . "<br/>";
		$sunday = self::Sunday( $date );
		if ( ! $part_id ) {
			die ( "no supplier" );
		}

		$fields = "part_id, date, week, amount, delivery_fee, ref, project_id, net_amount, document_type ";
		$values = $part_id . ", \"" . $date . "\", " .
		          "\"" . $sunday->format( "Y-m-d" ) .
		          "\", " . ( $amount - $delivery_fee ) . ", " . $delivery_fee . ", '" . $ref . "', '" . $project . "', " .
		          $net_amount . ", " . $document_type;

		if ( $document_file ) {
			$fields .= ", invoice_file";
			$values .= ", " . QuoteText( $document_file );
		}
		$sql = "INSERT INTO im_business_info (" . $fields . ") "
		       . "VALUES (" . $values . " )";

		MyLog( $sql, __FILE__ );

		SqlQuery( $sql );

		return SqlInsertId();
	}

	static function update_transaction( $delivery_id, $total, $fee ) {
		$sql = "UPDATE im_business_info SET amount = " . $total . ", " .
		       " delivery_fee = " . $fee .
		       " WHERE ref = " . $delivery_id;

		MyLog( $sql, __FILE__ );
		SqlQuery( $sql );
	}

	static function Sunday( $date ) {
		$datetime = new DateTime( $date );
		$interval = new DateInterval( "P" . $datetime->format( "w" ) . "D" );
		$datetime->sub( $interval );

		return $datetime;
	}

	function CreateInvoiceUser() {
		$last_created = SqlQuerySingleScalar( "select max(user_id) from wp_usermeta where meta_key = 'invoice_id'" );

		$last = SqlQuerySingleScalar( "select max(user_id) from wp_usermeta" );

		MyLog(__FUNCTION__ . $last_created . " " . $last);
		for ( $user_id = $last_created + 1; $user_id <= $last; $user_id ++ ) {
			if ( SqlQuerySingleScalar( "select client_last_order($user_id)" ) ) {
				MyLog( "creating $user_id", __FUNCTION__ );
				$U = new Fresh_Client( $user_id );
				$U->createInvoiceUser();
				MyLog( get_user_meta( $user_id, 'invoice_id' ), __FUNCTION__ );

				return; // Do just one.
			}
		}
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

	function multisite_connect()
	{
		$server = GetParam("server", true);
		$user = GetParam("user", true);
		$password = GetParam("password", true);

		return Core_Db_MultiSite::getInstance()->DoConnectToMaster($server, $user, $password);
	}

	function multisite_validate()
	{
		print Core_Db_MultiSite::getInstance()->getLocalSiteID();
	}

	function credit_clear_token()
	{
		$client_id = GetParam("id");
		Finance_Yaad::ClearToken($client_id);
	}

	static function getPost()
	{
		return Flavor::getPost();
	}
}

/*-- Start payment gateway--*/

function finance_custom_payment_is_woocommerce_active()
{
	$active_plugins = (array) get_option('active_plugins', array());

	if (is_multisite()) {
		$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	}

	return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

function payment_list() {
	include( FINANCE_INCLUDES . 'payment_list.php' );
}

/*-- End payment gateway--*/

function FinanceLog($message, $print = false)
{
	if ($print) print $message;
	MyLog($message, '', 'finance.log');
}