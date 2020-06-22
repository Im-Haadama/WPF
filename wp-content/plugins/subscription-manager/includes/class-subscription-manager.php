<?php


class Subscription_Manager
{
	protected $loader;
	protected $database;
	protected $version;

	/**
	 * Subscription_Manager constructor.
	 */
	public function __construct() {
		$this->version = '1.0';
		$this->define_constants();
		$this->loader = new Core_Autoloader(SUBSCRIPTION_MANAGER_ABSPATH);
		self::init_hooks();
	}

	function admin_menu() {
		$menu = new Core_Admin_Menu();

		$menu->AddMenu( 'Subs', 'Subscription Manager', 'show_subs', 'subs', array( $this, 'main' ) );

//		$menu->AddSubMenu( "finance", "finance_bank",
//			array(
//				'page_title' => 'Bank pages',
//				'function'   => array( Finance_Bank::instance(), 'show_bank_accounts_wrap' )
//			) );
	}

	public function run()
	{

	}

	public function main()
	{
		$result = Core_Html::GuiHeader(1, "Subscriptions");
		$args = [];
		$result .= Core_Gem::GemTable("subscriptions", $args);

		print $result;
	}

	public function getRoles()
	{
		return "show_subs";
	}

	public function init_hooks()
	{
		add_action( 'admin_menu',array($this, 'admin_menu') );
		Flavor_Roles::instance()->addRole("show_subs");
		self::install();
	}

	public function install()
	{
		 $this->database = new Subscription_Manager_Database();
		 $this->database->install($this->version);
	}

	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		define( 'SUBSCRIPTION_MANAGER_ABSPATH', dirname( SUBSCRIPTION_MANAGER_PLUGIN_FILE ) . '/' );
		define( 'SUBSCRIPTION_MANAGER_PLUGIN_BASENAME', plugin_basename( SUBSCRIPTION_MANAGER_PLUGIN_FILE ) );
//		define( 'SUBSCRIPTION_MANAGER_VERSION', $this->version );
		define( 'SUBSCRIPTION_MANAGER_INCLUDES_URL', plugins_url() . '/subscription_manager/includes/' ); // For js
		define( 'SUBSCRIPTION_MANAGER_INCLUDES_ABSPATH', plugin_dir_path(__FILE__) . '../../subscription_manager/includes/' );  // for php
		define( 'SUBSCRIPTION_MANAGER_DELIMITER', '|' );
		define( 'SUBSCRIPTION_MANAGER_LOG_DIR', $upload_dir['basedir'] . '/subscription_manager-logs/' );
	}

}