<?php


require_once(ABSPATH . '/wp-content/plugins/wpf_flavor/wpf_flavor.php');

class FVideo {
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

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1';

	private $plugin_name;

	/**
	 * The single instance of the class.
	 *
	 * @var FVideo
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * fVideo instance.
	 *
	 */
	public $fVideo = null;
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
	 * Main FVideo Instance.
	 *
	 * Ensures only one instance of FVideo is loaded or can be loaded.
	 *
	 * @static
	 * @return FVideo - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "FVideo" );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		die( __FUNCTION__ . __( 'Cloning is forbidden.', 'fVideo' ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		fVideo_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'fVideo' ), '2.1' );
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
		WPF_Flavor::instance();

		$this->admin_notices = null;
		$this->plugin_name = $plugin_name;
		$this->define_constants();
		if ( ! defined( 'FVideo_ABSPATH' ) ) {
			die ( "not defined" );
		}
		$this->auto_loader      = new Core_Autoloader( FVideo_ABSPATH );
		$this->loader = Core_Hook_Handler::instance();
		$this->post_file   = WPF_Flavor::getPost();

		$this->init_hooks($this->loader);

		do_action( 'fVideo_loaded' );
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
		// register_activation_hook( WC_PLUGIN_FILE, array( 'FVideo_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );

		GetSqlConn( ReconnectDb() );

		self::install( $this->version );

		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'init', array( $this, 'init' ), 11 );
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );
		add_action( 'admin_notices', array($this, 'admin_notices') );

		// Admin menu
		add_action( 'admin_menu',array($this, 'admin_menu') );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// FVideo type.
		add_action('init', array($this, 'register_fvideo'));
//		add_action('add_meta_boxes', array($this, 'fvideo_box'));
		add_action( 'save_post', array($this, 'fvideo_box_save'));

		$loader->AddAction("get_video", $this);
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
			$logger = fVideo_get_logger();
			$logger->critical(
			/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'fVideo' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'fVideo_shutdown_error', $error );
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		define_const( 'FVideo_ABSPATH', dirname(dirname( __FILE__ )) . '/' );
		define_const( 'FVideo_PLUGIN_DIR', FVideo_ABSPATH);
			// plugin_basename( FVideo_PLUGIN_FILE ) );
		define_const( 'FVideo_VERSION', $this->version );
		define_const( 'FVideo_INCLUDES', FVideo_ABSPATH . 'includes/' );
		define_const( 'FVideo_INCLUDES_URL', plugins_url() . '/fVideo/includes/' ); // For js
		define_const( 'FVideo_DELIMITER', '|' );
		define_const( 'FVideo_LOG_DIR', $upload_dir['basedir'] . '/fVideo-logs/' );
		define_const("FVideo_Torrent_Folder", $upload_dir['basedir'] . '/torrents/');
		define_const("FVideo_TEMP_Folder", $upload_dir['basedir'] . '/temp/');
	}

	static function admin_menu() {
		FVideo_Settings::instance()->admin_menu();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		FVideoLog(__FUNCTION__);
		// Before init action.
		do_action( 'before_fVideo_init' );

		$this->shortcodes = Core_Shortcodes::instance();
		$this->shortcodes->add($this->getShortcodes());


		// Set up localisation.
		$this->load_plugin_textdomain();

		$this->shortcodes->do_init();
		if (!file_exists(FVideo_Torrent_Folder)) {
			FVideoLog("mkdir torrent");
			mkdir(FVideo_Torrent_Folder, 0777, true);
		}

		if (!file_exists(FVideo_TEMP_Folder)) {
			FVideoLog("mkdir temp");
			mkdir(FVideo_TEMP_Folder, 0777, true);
		}

		self::addRoles();
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'fvideo_player'      => array( 'FVideo::player',    null ));          // Payments data entry
	}

	static function player($atts) // [fvideo_player]
	{
	    FVideoLog(__FUNCTION__);
		$video_id = get_the_ID();
		$v = new FVideo_Video($video_id);
		$torrent_url = $v->get_torrent();
		print "tor=$torrent_url<br/>";

		if (! $torrent_url) return "Sorry, video not found";

		$result = "<div id='video''></div>
    <script>
    var client = new WebTorrent();
	client.on('error', (err) => { alert (err); });

    var torrentId = \"$torrent_url\";
    client.add(torrentId, function (torrent) {
        // Torrents can contain many files. Let's use the .mp4 file
        var file = torrent.files.find(function (file) {
            return file.name.endsWith('.mp4')
        })

        // Display the file by adding it to the DOM.
        // Supports video, audio, image files, and more!
        let video = document.getElementById(\"video\");
        file.appendTo(video);
    });
</script>
";
		FVideoLog($result);

        return $result;
}
	function addRoles()
	{
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
		$locale = apply_filters( 'plugin_locale', $locale, 'fVideo' );

//		unload_textdomain( 'fVideo' );
		$rc = load_textdomain( 'fVideo', FVideo_PLUGIN_DIR . '/languages/fVideo-' . $locale . '.mo' );
//		print "rc=$rc<br/>";
//		print "E=$locale " . _e("Credit Card", "fVideo");
//		load_plugin_textdomain( 'fVideo', false, plugin_basename( dirname( FVideo_PLUGIN_FILE ) ) . '/i18n/languages' );
	}
//
//	/**
//	 * Ensure theme and server variable compatibility and setup image sizes.
//	 */
	public function setup_environment() {
		/* @deprecated 2.2 Use WC()->template_path() instead. */
		define_const( 'FVideo_TEMPLATE_PATH', $this->template_path() );

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
		return apply_filters( 'fVideo_template_path', 'fVideo/' );
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
		$file = dirname(dirname(plugins_url())). '/vendor/webtorrent/webtorrent.min.js';
		wp_enqueue_script( 'webtorrent', $file, null, $this->version, false );
	}

	public function admin_scripts() {
		// Should be loaded by flavor
//		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
//		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

//		$file = FVideo_INCLUDES_URL . 'fVideo.js?v=1';
//		wp_enqueue_script( 'fVideo', $file, null, $this->version, false );
	}

	public function run() {
//		$this->loader->run();
	}

	function install( $version, $force = false ) {
		$this->database = new FVideo_Database("FVideo");
		$this->database->install($this->version);
	}

	static public function settingPage() {
		$result = "";
		//                     Top nav                  Sub nav             target,                              capability
//		$module_list = array( "FVideo" => array(array("Bank transactions", "/fVideo_bank",                     "show_bank"),
//								                 array("Bank Receipts",     "/fVideo_bank?operation=receipts",  "show_bank"),
//										   		 array("Invoices",          "/invoices",  "edit_pricelist"),
//												 array("Bank payments",    "/fVideo_bank?operation=payments",  "show_bank"),
//												 array("Transactions types", "/fVideo_bank?operation=bank_transaction_types", "cfo")));

//		$result .= Flavor::ClassSettingPage($module_list);
		return $result;
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

	function register_fvideo()
	{
		$labels = array(
			'name'               => _x( 'Videos', 'post type general name' ),
			'singular_name'      => _x( 'Video', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'book' ),
			'add_new_item'       => __( 'Add New Video' ),
			'edit_item'          => __( 'Edit Video' ),
			'new_item'           => __( 'New Video' ),
			'all_items'          => __( 'All Videos' ),
			'view_item'          => __( 'View Video' ),
			'search_items'       => __( 'Search Videos' ),
			'not_found'          => __( 'No videos found' ),
			'not_found_in_trash' => __( 'No videos found in the Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Videos'
		);
		$args = array(
			'labels'        => $labels,
			'description'   => 'Holds our videos and video specific data',
			'public'        => true,
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
			'has_archive'   => true,
			'taxonomies' => array('category')
		);
		register_post_type( 'fvideo', $args );
	}

	function fvideo_box() {
		add_meta_box(
			'fvideo_box',
			__( 'FVideo link', 'myplugin_textdomain' ),
			array($this, 'fvideo_box_content'),
			'fvideo',
			'side',
			'high'
		);
	}

	function fvideo_box_content($post)
	{
		$post_id = $post->ID;
		wp_nonce_field( plugin_basename( __FILE__ ), 'fvideo_box_content_nonce' );
		echo '<label for="video_link"></label>';
		$video_link = 'Enter url to video torrent';
		if ($post_id) $value = get_post_meta($post_id, 'video_link', 1);
		echo '<input type="text" id="video_link" name="video_link"';
		if ($value)  echo "value=\"$value\"";
		else echo 'placeholder="' . $video_link . '"';
		echo '/>';
	}

	function fvideo_box_save( $post_id )
	{
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( ! isset($_POST['fvideo_box_content_nonce']) or !wp_verify_nonce( $_POST['fvideo_box_content_nonce'], plugin_basename( __FILE__ ) ) )
			return;
		if ( 'fvideo' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
		}
		$video_link = $_POST['video_link'];
		update_post_meta( $post_id, 'video_link', $video_link );
		FVideoLog("update $post_id $video_link");
	}

	function get_video()
	{
		$file_name = GetParam("file_name");

		if (strstr($file_name, "torrent")) {
			$f = new FVideo_Video(538);
			$f->create_if_needed();
//			print $file_name;

			$content = file_get_contents(FVideo_Torrent_Folder  . $file_name);
			start_download($file_name, strlen($content));
			print $content;
			return;
		}
		$content = file_get_contents("https://video1.weact.live/"  . $file_name);
//		print $content;
		start_download($file_name, strlen($content));
		print $content;
	}

}

function FVideoLog($message, $print = false)
{
	if ($print) print $message;
	MyLog($message, '', 'fVideo.log');
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


