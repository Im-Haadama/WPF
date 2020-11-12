<?php


class Subscription_Manager {
	protected static $_instance = null;

	protected $loader;
	protected $database;
	protected $version;

	/**
	 * Subscription_Manager constructor.
	 */
	public function __construct( $class ) {
		$this->version = '1.0';
		$this->define_constants();
		$this->loader = new Core_Autoloader( SUBSCRIPTION_MANAGER_ABSPATH );
		self::init_hooks();
	}


	function getPost() {
		return Flavor::getPost();
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( __CLASS__ );
		}

		return self::$_instance;
	}

	function admin_menu() {
		$menu = Core_Admin_Menu::instance();

		$menu->AddMenu( 'Subs', 'Subscription Manager', 'show_subs', 'subs', array( $this, 'main' ) );

//		$menu->AddSubMenu( "finance", "finance_bank",
//			array(
//				'page_title' => 'Bank pages',
//				'function'   => array( Finance_Bank::instance(), 'show_bank_accounts_wrap' )
//			) );
	}

	public function run() {

	}

	public function main() {
		$result            = Core_Html::GuiHeader( 1, "Subscriptions" );
		$args              = array( "post_file" => Subscription_Manager::getPost() );
		$args["selectors"] = array( "user_id" => 'Core_Users::gui_select_user' );
		$args["edit"]      = false;
		$result            .= Core_Gem::GemTable( "subscriptions", $args );

		print $result;
	}

	public function getRoles() {
		return "show_subs";
	}

	public function init_hooks() {
		Core_Gem::AddTable( "subscriptions" );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		Flavor_Roles::instance()->addRole( "show_subs" );
		self::install();
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts' ));
		AddAction('subs_create_user', array($this, 'subs_create_user'));
	}

	public function subs_create_user($email)
	{
	}

	public function install() {
		$this->database = new Subscription_Manager_Database();
		$this->database->install( $this->version );
	}

	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		Define_if_needed( 'SUBSCRIPTION_MANAGER_ABSPATH', dirname( SUBSCRIPTION_MANAGER_PLUGIN_FILE ) . '/' );
		Define_if_needed( 'SUBSCRIPTION_MANAGER_PLUGIN_BASENAME', plugin_basename( SUBSCRIPTION_MANAGER_PLUGIN_FILE ) );
//		Define( 'SUBSCRIPTION_MANAGER_VERSION', $this->version );
		Define_if_needed( 'SUBSCRIPTION_MANAGER_INCLUDES_URL', plugins_url() . '/subscription-manager/includes/' ); // For js
		Define_if_needed( 'SUBSCRIPTION_MANAGER_INCLUDES_ABSPATH', plugin_dir_path( __FILE__ ) . '../../subscription_manager/includes/' );  // for php
		Define_if_needed( 'SUBSCRIPTION_MANAGER_DELIMITER', '|' );
		Define_if_needed( 'SUBSCRIPTION_MANAGER_LOG_DIR', $upload_dir['basedir'] . '/subscription_manager-logs/' );
	}

	public function enqueue_scripts() {
		$file = SUBSCRIPTION_MANAGER_INCLUDES_URL . '/js/subscriptions.js';
		SubsLog("trying to load $file");
		wp_enqueue_script( 'subscriptions', $file, null, $this->version, false );
	}

	public function add_subscription(Core_Users $u, $time_period, $subscription_name, $capability)
	{
		$table_name = "subscriptions";
		$db_prefix = GetTablePrefix($table_name);
		return  SqlQuery("insert into ${db_prefix}$table_name (user_id, subscription_name, start_date, valid_date) " .
		" VALUES (" . $u->getId() . ", '" . $subscription_name . "', CURDATE(), CURDATE() + INTERVAL $time_period)
		on duplicate key update start_date = curdate() and valid_date = CURDATE() + INTERVAL $time_period") and
		         $u->addCapability($capability);
	}
}

function SubsLog($message)
{
	return MyLog($message, '', 'subscriptions.log');
}