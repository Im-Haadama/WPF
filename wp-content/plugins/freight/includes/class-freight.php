<?php


class Freight {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Delivery_Drivers_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $auto_loader;
	protected $settings;
	protected $database;
//	protected $order;

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
	    self::$_instance = $this;
		$this->plugin_name = $plugin_name;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		$this->auto_loader = new Core_Autoloader(FREIGHT_ABSPATH);
		$this->settings = new Freight_Settings();

		$this->init_hooks();

		do_action( 'freight_loaded' );
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
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );

		// Admin menu
		add_action('admin_menu', array($this->settings, 'admin_menu'));

		// get local deliveries


		GetSqlConn(ReconnectDb());
		Freight_Methods::init();
		Freight_Mission_Manager::init_hooks();
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

	function handle_operation($operation) {
		$input = null;

		$result = apply_filters( $operation, "", "", null, null );

		if ( $result ) 
			return $result;

		$module = strtok( $operation, "_" );
		if ( $module === "data" ) {
			return Core_Data::handle_operation( $operation );
		}
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
//		print __CLASS__ . ':' . __FUNCTION__ . "<br/>";
		do_action( 'before_freight_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();
//		$this->delivery_manager = new Freight_Delivery_Manager();

		$shortcodes = Core_Shortcodes::instance();
//		$shortcodes->add($this->delivery_manager->getShortcodes());


		$this->enqueue_scripts();

		// Init action.
		do_action( 'freight_init' );
	}

	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'freight' );

		unload_textdomain( 'wpf' );
		if ($locale == 'en_US') return;

		$file = FREIGHT_ABSPATH . 'languages/wpf-' . $locale . '.mo';
		$rc = load_textdomain( 'wfp', $file );
//		print "loaded $file $rc <br/>";
//		$rc1 = load_plugin_textdomain( 'wfp');
		if (0 and get_user_id() == 1) {
			if (! $rc) print "can't load textdomain";
//			if (! $rc1) print "can't load plugin_textdomain";
			if (! file_exists($file)) print "file $file not found";
//			print $file . "<br/>";
//			print "Rc= $rc";
		}
	}

	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		$this->define( 'FREIGHT_TEMPLATE_PATH', $this->template_path() );
	}

	public function template_path() {
		return apply_filters( 'freight_template_path', 'freight/' );
	}

	public function run()
	{
		// Install tables
		self::register_activation(dirname(__FILE__) . '/class-freight-database.php', array('Freight_Database', 'install'));

		// Temp migration. run once on each installation
        // Freight_Database::convert_supplier_name_to_id();

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
			print debug_trace();
			return;
		}
		register_activation_hook($file, $function);
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
		return "/wp-content/plugins/freight/post.php";
	}

	public function enqueue_scripts() {
		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

	}

	public function admin_scripts()
    {
        $file = FREIGHT_INCLUDES_URL . 'js/admin.js';
	    wp_register_script( 'freight_admin', $file);

//	    $params = array(
//	    	'admin_post' => get_site_url() . '/wp-content/plugins/freight/post.php'
//	    );
//	    wp_localize_script('freight_admin', 'freight_admin_params', $params);
//
	    wp_enqueue_script('freight_admin');

    }

	/*-- Start product quantity +/- on listing -- */
	public function freight_add_quantity_fields($html, $product) {
		if ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually() ) {
			$html = '<form action="' . esc_url( $product->add_to_cart_url() ) . '" class="cart" method="post" enctype="multipart/form-data">';
			$html .= woocommerce_quantity_input( array(), $product, false );
			$html .= '<button type="submit" data-quantity="1" data-product_id="' . $product->id . '" class="button alt ajax_add_to_cart add_to_cart_button product_type_simple">' . esc_html( $product->add_to_cart_text() ) . '</button>';
			$html .= '</form>';
		}
		return $html;
	}

	public function freight_quantity_handler() {
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
//        if (Freight_Database::CheckInstalled("Freight", $this->version) == $version and ! $force) return;

        $this->database = new Freight_Database();
        $this->database->install($this->version);

        // Install more specific
	}

	static public function admin_load()
	{
		new Freight_Settings();
	}
}
