<?php

class Finance_Settings {
	static private $_instance;

	/**
	 * @return Finance_Settings
	 */
	public static function instance(): Finance_Settings {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		self::$_instance = $this;

		self::init();
	}

	public function get_sections() {
		$sections = array(
			'' => __( 'General', 'fresh' ),
//			'inventory'    => __( 'Inventory', 'woocommerce' ),
//			'downloadable' => __( 'Downloadable products', 'woocommerce' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	public function init() {
//		add_filter( 'woocommerce_get_sections_products', array( __CLASS__, 'product_comment_add_settings_tab' ) );
//		add_filter( 'woocommerce_get_settings_products', array( __CLASS__, 'product_comment_get_settings' ), 10, 2 );
	}

	static public function product_comment_add_settings_tab( $settings_tab ) {
		$settings_tab['product_comment'] = __( 'Enable Product Comments ' );

		return $settings_tab;
	}

	static public function product_comment_get_settings( $settings, $current_section )
	{
		if ( 'product_comment' == $current_section ) {
			$custom_settings = array(

				array(
					'name' => __( '' ),
					'type' => 'checkbox',
					'desc' => __( 'Enable Product Comments on Cart' ),
					'id'   => 'product_comment_view'
				),
				array(
					'name'     => __( 'Activate' ),
					'type'     => 'button',
					'desc'     => __( 'Activate plugin' ),
					'desc_tip' => true,
					'class'    => 'button-secondary',
					'id'       => 'activate'
				)
			);

			return $custom_settings;
		} else {
			return $settings;
		}
	}

	function admin_menu() {
		$menu = Core_Admin_Menu::instance();

		$menu->AddMenu('Finance', 'Finance', 'show_finance', 'finance', array($this, 'main'));

		$menu->AddSubMenu( "finance", "finance_bank",
			array( 'page_title' => 'Bank pages', 'function' => array( Finance_Bank::instance(), 'show_bank_accounts_wrap' ) ) );

		$menu->AddSubMenu( "finance", "promote_users",
			array( 'page_title' => 'Multi site', 'function' => array( Core_Db_MultiSite::getInstance(), 'admin_page' ) ) );

//		$menu->AddSubMenu( "finance", "promote_users",
//			array( 'page_title' => 'Manage inbox', 'function' => array( 'Finance_Accounting', 'manage_inbox' ) ) );

		$menu->AddSubMenu( "users.php", "edit_shop_orders",
			array( 'page_title' => 'Client accounts', 'function' => array( "Finance_Client_Views", 'admin_page' ) ) );

		if (im_user_can("edit_shop_orders"))
			$menu->AddSubMenu('finance', 'edit_shop_orders',
				array('page_title' => 'Missions',
				      'menu_title' => 'Missions',
				      'menu_slug' => 'missions',
				      'function' => 'Flavor_Mission_Views::show_missions'));
		else
			$menu->AddSubMenu('finance', 'read',
				array('page_title' => 'Install woocommerce',
				      'menu_title' => 'Missions',
				      'menu_slug' => 'missions',
				      'function' => 'Flavor_Mission_Views::install_woocommerce'));

		WPF_Flavor::AddTop("client_accounts", "Client accounts", "/wp-admin/users.php?page=client-accounts");

		$menu->AddSubMenu( "users.php", "edit_shop_orders",
			array( 'page_title' => 'Payment methods', 'function' => array( "Finance_Payments", 'payment_methods' ) ) );

		$menu->AddSubMenu('users.php', 'edit_shop_orders', // Previous users.php
			array('page_title' => 'Payment List',
			      'menu_title' => 'Payment list',
			      'menu_slug' => 'payment_list',
			      'function' => 'payment_list')
		);

		// Deliveries
		$menu->Add("woocommerce", "edit_shop_orders", "deliveries", array("Finance_Delivery" , 'deliveries' ));
		// On Top
		WPF_Flavor::AddTop("deliveries", "Deliveries", "/wp-admin/admin.php?page=deliveries");

		if (class_exists("WC_Order")) {
			WPF_Flavor::AddTop( 'orders', 'Orders', '/wp-admin/edit.php?post_type=shop_order&post_status=wc-processing' );
			WPF_Flavor::AddTop( 'orders_all', 'All orders', '/wp-admin/edit.php?post_type=shop_order', 'orders' );
			WPF_Flavor::AddTop( 'orders_print', 'Print', '/wp-admin/admin.php?page=printing', 'orders' );
		}

		WPF_Flavor::AddTop( 'missions', 'Missions', '/wp-admin/admin.php?page=missions' );

	}

	function main()
	{
		$result = Core_Html::GuiHeader(1, "Finance");

		print $result;
	}

	static function suppliers() {
		$result = '<h3>Suppliers</h3>';

		print $result;
	}
}
