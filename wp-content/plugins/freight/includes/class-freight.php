<?php


class Freight extends WPF_Plugin {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Delivery_Drivers_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */

	/**
	 * @return Freight_Legacy
	 */
	public function getLegacy(): ?Freight_Legacy {
		return $this->legacy;
	}

	protected $legacy;
	protected $views;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */

	/**
	 * The single instance of the class.
	 *
	 * @var Freight
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * freight instance.
	 *
	 */
	public $freight = null;

	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

	/**
	 * Main Freight Instance.
	 *
	 * Ensures only one instance of Freight is loaded or can be loaded.
	 *
	 * @static
	 * @return Freight - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self("Freight");
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		die( __FUNCTION__ .  __( 'Cloning is forbidden.', 'freight' ));
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		freight_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'freight' ), '2.1' );
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
		if (! class_exists('Core_Autoloader')) return;
	    self::$_instance = $this;
		$this->plugin_name = $plugin_name;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		$this->auto_loader = Core_Autoloader::instance();
		$this->auto_loader->add_path(FREIGHT_INCLUDES);
		$this->loader = Core_Hook_Handler::instance();
		$this->settings = new Freight_Settings();
		$this->views = new Freight_Views();

		// For testing define('FREIGHT_LEGACY_USER', 1);
		if (defined('FREIGHT_LEGACY_USER')) {
			$this->legacy = new Freight_Legacy(FREIGHT_LEGACY_USER);
		}

		$this->init_hooks($this->loader);

		do_action( 'freight_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @param Core_Hook_Handler $loader
	 *
	 * @throws Exception
	 * @since 2.3
	 */
	private function init_hooks(Core_Hook_Handler $loader) {
	    // Admin scripts and styles. Todo: Check if needed.
		add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

		// Can't make that work: register_activation_hook( __FILE__, array( $this, 'install' ) );
        self::install($this->version);

		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );

		// Admin menu
		add_action('admin_menu', array($this->settings, 'admin_menu'));

		$loader->AddFilter("mission_actions", $this);
		$loader->AddAction("mission_dispatch", $this);
		$loader->AddAction("mission_markers", $this);
		$loader->AddAction("mission_driver", $this);
		// get local deliveries

		GetSqlConn(ReconnectDb());
		Freight_Methods::instance()->init($loader);
		Freight_Actions::instance()->init_hooks($loader);
		$this->views->init_hooks($loader);
		if ($this->legacy) $this->legacy->init_hooks($loader);

		add_action('rest_api_init', function() {
			register_rest_route( 'freight/v1', '/freight/',
				array( 'methods' => 'GET', 'callback' => array($this, 'json_api' )) );
		});
	}

	function mission_markers()
	{
		$id = GetParam("id");
		$m = Freight_Mission_Manager::get_mission_manager($id);
		print $m->markers($id);
	}

	function mission_dispatch_wrap($id)
	{
		FreightLog(__FUNCTION__);
		$m = Freight_Mission_Manager::get_mission_manager($id);

		if (! $m) return false;

		FreightLog("before");
		print $m->dispatcher();
		FreightLog("after");
	}

	function mission_actions($actions)
	{
		$actions['Plan'] = Core_Html::GuiHyperlink("Plan", "/wp-content/plugins/freight/plan.php?id=%d");
		$actions['Dispatch'] = Core_Html::GuiHyperlink("Dispatch", AddToUrl("operation", "mission_dispatch&id=%d"));
		$actions['Clean'] = Core_Html::GuiButton("btn_Clean", "Clean", "execute_url('".AddToUrl("operation", "mission_clean&id=%d") . "')");
		$actions['Driver'] = Core_Html::GuiHyperlink("Driver", WPF_Flavor::getPost("mission_driver&id=%d"));

		return $actions;
	}
	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( isset($error['type']) and  in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ) ) ) {
			$logger = freight_get_logger();
			$logger->critical(
			/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'freight' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'freight_shutdown_error', $error );
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'FREIGHT_ABSPATH', dirname( FREIGHT_PLUGIN_FILE ) . '/' );
		$this->define( 'FREIGHT_PLUGIN_BASENAME', plugin_basename( FREIGHT_PLUGIN_FILE ) );
		$this->define( 'FREIGHT_VERSION', $this->version );
		$this->define( 'FREIGHT_INCLUDES', FREIGHT_ABSPATH . '/includes/' );
		$this->define( 'FREIGHT_DELIMITER', '|' );
		$this->define( 'FREIGHT_LOG_DIR', $upload_dir['basedir'] . '/freight-logs/' );
		$this->define( 'FREIGHT_INCLUDES_URL', plugins_url() . '/freight/includes/' ); // For js
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

	function handle_operation($operation) {
		$input = null;
		$debug = false; // (get_user_id() == 2);
		if ($debug)
			print __FUNCTION__;

		$result = apply_filters( $operation, "", "", null, null );

		if ( $result ) 
			return $result;

		$module = strtok( $operation, "_" );
		if ( $module === "data" ) {
			if ($debug)
				print "mod=$module op = $operation<br/>";
			return Core_Data::handle_operation( $operation );
		}
	}

	static function new_customer($order_id)
	{
		$result = "";
		$result .= Core_Html::GuiHeader( 1, "לקוח חדש" );

		$O         = new Order( $order_id );
		$client_id = $O->getCustomerId();


		$result .= "1) צור קשר טלפוני עם הלקוח. עדכן אותו שהתקבלה ההזמנה.<br/>";
		$result .= "2) אמת את השם לחשבונית.<br/>";
		$result .= "3) אמת את הכתובת למשלוח. בדוק האם יש אינטרקום או קוד לגישה לדלת.<br/>";

		$step      = 4;

		$invoice_client_id = get_user_meta( $client_id, 'invoice_id', 1 );

		$result .= Core_Html::gui_table_args( array(
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
		if (! defined('FLAVOR_INCLUDES')) return;
		require_once FLAVOR_INCLUDES . 'class-core-autoloader.php';
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

		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-data-store.php';

		/**
		 * REST API.
		 */
//		include_once WC_FREIGHT_INCLUDES . 'includes/legacy/class-wc-legacy-api.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-api.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-auth.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-register-wp-admin-settings.php';

		/**
		 * Libraries
		 */
//		include_once WC_FREIGHT_INCLUDES . 'includes/libraries/action-scheduler/action-scheduler.php';
//
//		if ( defined( 'WP_CLI' ) && WP_CLI ) {
//			include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-cli.php';
//		}
//
//		if ( $this->is_request( 'admin' ) ) {
//			include_once WC_FREIGHT_INCLUDES . 'includes/admin/class-wc-admin.php';
//		}
//
//		if ( $this->is_request( 'frontend' ) ) {
//			$this->frontend_includes();
//		}
//
//		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'woocommerce_allow_tracking', 'no' ) ) {
//			include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-tracker.php';
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
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-ten.php';
//					break;
//				case 'twentyeleven':
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-eleven.php';
//					break;
//				case 'twentytwelve':
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-twelve.php';
//					break;
//				case 'twentythirteen':
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-thirteen.php';
//					break;
//				case 'twentyfourteen':
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-fourteen.php';
//					break;
//				case 'twentyfifteen':
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-fifteen.php';
//					break;
//				case 'twentysixteen':
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-sixteen.php';
//					break;
//				case 'twentyseventeen':
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-seventeen.php';
//					break;
//				case 'twentynineteen':
//					include_once WC_FREIGHT_INCLUDES . 'includes/theme-support/class-wc-twenty-nineteen.php';
//					break;
//			}
//		}
//	}
//
//	/**
//	 * Include required frontend files.
//	 */
//	public function frontend_includes() {
//		include_once WC_FREIGHT_INCLUDES . 'includes/wc-cart-functions.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/wc-notice-functions.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/wc-template-hooks.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-template-loader.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-frontend-scripts.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-form-handler.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-cart.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-tax.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-shipping-zones.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-customer.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-embed.php';
//		include_once WC_FREIGHT_INCLUDES . 'includes/class-wc-session-handler.php';
//	}
//
//	/**
//	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
//	 */
	public function include_template_functions() {
//		include_once WC_FREIGHT_INCLUDES . 'includes/freight-template-functions.php';
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_freight_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Init action.
		do_action( 'freight_init' );
	}

	public function load_plugin_textdomain() {
	}

	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		$this->define( 'FREIGHT_TEMPLATE_PATH', $this->template_path() );
	}

	public function template_path() {
		return apply_filters( 'freight_template_path', 'freight/' );
	}

	static public function SettingPage()
	{
		$result = "";
//		$pages = array(array("name" => "Suppliers", "target" => "/suppliers", "shortcode" => "freight_suppliers"));
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
		return WPF_Flavor::getPost();
	}

	public function admin_scripts()
    {
	    // Should be loaded by flavor
//	    $file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
//	    wp_enqueue_script( 'data', $file, null, $this->version, false );

	    $file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
	    wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

        $file = FREIGHT_INCLUDES_URL . 'js/admin.js';
	    wp_register_script( 'freight_admin', $file, null, $this->version, false);

		$params = array(
			'admin_post' => get_site_url() . Freight::getPost()
		);
		wp_localize_script('freight_admin', 'freight_admin_params', $params);

		wp_enqueue_script('freight_admin');
		wp_add_inline_script('freight_admin', 'let freight_post="' . self::getPost() . '";', 'before');

		$file = FREIGHT_INCLUDES_URL . 'js/legacy.js';
		wp_register_script( 'legacy', $file);
		wp_enqueue_script( 'legacy', $file, null, $this->version, false );

	}

	function install($version, $force = false)
	{
//        if (Freight_Database::CheckInstalled("Freight", $this->version) == $version and ! $force) return;

        $this->database = new Freight_Database();
        $this->database->install($this->version);

        // Install more specific
	}

	static public function admin_load()
	{
		new Freight_Settings();
	}

	function mission_driver()
	{
		$args = [];
		$args["viewport"] = true;
		$result = Core_Html::HeaderText($args);
		$mission_id = GetParam("id", true);
		$current_point = GetParam("current_point", false, 0);
		$m = Freight_Mission_Manager::get_mission_manager($mission_id);

		$result .= $m->driver_page($current_point);

		print $result;
	}

	function json_api()
	{
		$m = Freight_Mission_Manager::get_mission_manager(1134);
		print $m->json();

	}
}

function FreightLog($message, $print = false)
{
	if ($print) print $message;
	MyLog($message, '', 'freight.log');
}

