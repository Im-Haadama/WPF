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
		error_reporting( E_ALL );
		ini_set( 'display_errors', 'on' );

		$this->plugin_name = $plugin_name;
		$this->define_constants();
		$this->includes(); // Loads class autoloader
		$this->loader = new Fresh_Loader();
		$this->init_hooks();

		do_action( 'fresh_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		// register_activation_hook( WC_PLUGIN_FILE, array( 'Fresh_Install', 'install' ) );
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
//		add_action( 'woocommerce_after_cart_table', 'wc_after_cart' );
		add_action( 'woocommerce_checkout_process', 'wc_minimum_order_amount' );
		add_action( 'woocommerce_before_cart', 'wc_minimum_order_amount' );
		add_action( 'woocommerce_checkout_order_processed', 'wc_minimum_order_amount' );
		add_action( 'admin_menu', 'im_admin_menu' );
		add_filter( 'woocommerce_available_shipping_methods', 'hide_shipping_if_cat_is_orange', 10, 1 );
		add_action( 'woocommerce_before_calculate_totals', 'im_woocommerce_update_price', 99 );
		add_filter( 'woocommerce_cart_item_price', 'im_show_nonsale_price', 10, 2 );
		add_filter( 'woocommerce_order_button_text', 'im_custom_order_button_text' );
		add_action( 'init', 'custom_add_to_cart_quantity_handler' );
		add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );
		add_action( 'init', 'register_awaiting_shipment_order_status' );

		add_filter( 'wc_order_statuses', 'add_awaiting_shipment_to_order_statuses' );


//		add_action( 'wp_footer', 'im_footer' );
//		if (get_user_id() == 1) print __CLASS__ ."<br/>";


		get_sql_conn(reconnect_db());
//		add_action( 'init', array( 'Fresh_Emails', 'init_transactional_emails' ) );
		// add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'init', array( $this, 'add_image_sizes' ) );
		// add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		$orders = new Fresh_Order_Management( $this->get_plugin_name(), $this->get_version() );
		$inventory = new Fresh_Inventory( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $orders, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_enqueue_scripts', $inventory, 'enqueue_scripts' );
	}


	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ) ) ) {
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

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'FRESH_WC_ABSPATH', dirname( FRESH_PLUGIN_FILE ) . '/' );
		$this->define( 'FRESH_PLUGIN_BASENAME', plugin_basename( FRESH_PLUGIN_FILE ) );
		$this->define( 'FRESH_VERSION', $this->version );
		$this->define( 'FRESH_INCLUDES', FRESH_WC_ABSPATH . '/includes/' );
		$this->define( 'FRESH_DELIMITER', '|' );
		$this->define( 'FRESH_LOG_DIR', $upload_dir['basedir'] . '/fresh-logs/' );

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
		$module = strtok($operation, "_");
		if ($module === "data")
			return Core_Data::handle_operation($operation);

		if (strstr($operation, "inv"))
			return Fresh_Inventory::handle_operation($operation);

		switch ($operation)
		{
			case "order_set_mission":
				$order_id = get_param("order_id", true);
				$mission_id = get_param("mission_id", true);
				$order = new Order($order_id);
				$order->setMissionID($mission_id);
				return "done";

			case "update":
				return handle_data_operation($operation);

			case "new_customer":
				$order_id = get_param("order_id", true);
				return self::new_customer($order_id);

			case "fresh_nav_add":
				$module = get_param("module", true);
				return self::AddNav($module);
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

		$result .= GuiHyperlink("לפתיחת ההזמנה", add_to_url(array("operation" => "show_order", "order_id" => $order_id)));

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

//		var_dump(Fresh_Nav::instance());
//		print "nav = " . Focus_Nav::instance()->get_nav() ."<br/>";

		// Init action.
		do_action( 'fresh_init' );
	}

	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'fresh' );

		unload_textdomain( 'wpf' );
		$file = FRESH_WC_ABSPATH . 'languages/wpf-' . $locale . '.mo';
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

	public function run ()
	{
		$this->loader->run();
	}

	public function SettingPage()
	{
		$result = "";
		$module_list = array( "Suppliers" => array(),
		                      "Orders" => array(array("Total ordered", "total_ordered")));

		$result .= Flavor::ClassSettingPage($module_list);
		return $result;
	}

	static private function getPost()
	{
		return "/wp-content/plugins/flavor/post.php";
	}

	public function AddNav($module, $sub_menu = null)
	{
		print "add nav $module<br/>";
		$flavor = Flavor::instance();
		$nav = $flavor->getNav();
		$menu_item = array("title" =>$module, 'url' => "/$module");

		print "about to add main<br/>";
		$module_id = $nav->AddMain($menu_item);

		if (! $module_id) return $module_id; // failed.

		return $nav->AddSub($module_id, array('title' => $sub_menu, 'url' => "/$module&operation=" . $sub_menu));
	}

	public function enqueue_scripts() {
		$file = plugin_dir_url( __FILE__ ) . 'inventory.js';
		wp_enqueue_script( $this->plugin_name, $file, array( 'jquery' ), $this->version, false );
	}
}

add_shortcode( 'basket-content', 'content_func' );

// [category-content id=18]
add_shortcode( 'category-content', 'category_content_func' );

function category_content_func($atts, $content, $tag)
{
	if (! file_exists(ROOT_DIR . '/fresh/wp/Product.php')) return "not installed";

	require_once (ROOT_DIR . '/fresh/wp/Product.php');

	$my_atts = shortcode_atts( [ 'id' => get_the_ID() ], $atts, $tag );
//
	$id = $my_atts['id'];

	$iter = new ProductIterator();
	$iter->iterateCategory( $id );

	$result = "";
	while ( $prod_id = $iter->next()) $result .= get_product_name($prod_id) . ", ";

	return rtrim($result, ", ");
}

function content_func( $atts, $contents, $tag ) {
	require_once( ROOT_DIR . '/fresh/catalog/Basket.php' );

	$my_atts = shortcode_atts( [ 'id' => get_the_ID() ], $atts, $tag );
//
	$id = $my_atts['id'];

	$text = "תכולת הסל: ";
	$text .= get_basket_content( $id );

//
	return $text;
}

function get_minimum_order() {
	global $woocommerce;

	$value = 85;

	$country  = $woocommerce->customer->get_shipping_country();
	// $state    = $woocommerce->customer->get_shipping_state();
	$postcode = $woocommerce->customer->get_shipping_postcode();

	$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
		'destination' => array(
			'country'  => $country,
			'state'    => '',
			'postcode' => $postcode,
		),
	) );
//    my_log ("zone_id = " . $zone1->get_id());

	$sql    = "SELECT min_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone1->get_id();
	$result = sql_query( $sql );
	if ( $result ) {
		$row = mysqli_fetch_assoc( $result );
		//    my_log($row["min_order"]);

		if ( is_numeric( $row["min_order"] ) ) {
			$value = $row["min_order"];
		}
	}

	return $value;
}

function im_woocommerce_update_price() {

	my_log( "cart start" );
	// TWEEK. Don't know why menu_op calls this method.
	// DONT remove without trying menu.php and cart.
	if (! sql_query_single_scalar("select 1")) {
		my_log ("not connected to db");
		return;
	}
	$client_type = customer_type( get_user_id() );
//	if (get_user_id() == 1) $client_type = "siton";

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$prod_id = $cart_item['product_id'];
		$variation_id = $cart_item['variation_id'];
		if ( ! ( $prod_id > 0 ) ) {
			my_log( "cart - no prod_id" );
			continue;
		}
		$q          = $cart_item['quantity'];
		$sell_price = Fresh_Pricing::get_price_by_type( $prod_id, $client_type, $q, $variation_id );
		//my_log("set " . $sell_price);
		$cart_item['data']->set_sale_price( $sell_price );
		$cart_item['data']->set_price( $sell_price );
		my_log( $prod_id . " " . $q );

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

if (!function_exists('customer_type')) {
function customer_type( $client_id ) {
	$key = get_user_meta( $client_id, '_client_type', true );

	if ( is_null( $key ) ) {
		return 0;
	}

	return $key;
}
}

function im_admin_menu() {
//	add_menu_page( 'Fresh Store', 'Fresh Store', 'manage_options', 'im-haadama/admin.php', 'fresh_store_admin_page',
//        'dashicons-tickets', 6 );
	add_menu_page( 'Fresh Store', 'תפריט אריזה', 'manage_options', 'im-haadama/packing.php', 'fresh_store_packing_page',
		'dashicons-tickets', 6 );
	add_menu_page( 'Fresh Store', 'ניהול ספקים', 'manage_options', 'im-haadama/supplier_account.php', 'fresh_store_supplier_account_page',
		'dashicons-tickets', 6 );
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
