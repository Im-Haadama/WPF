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
		$menu = new Core_Admin_Menu();

		$menu->AddMenu('Finance', 'Finance', 'show_finance', 'finance', array($this, 'main'));

		$menu->AddSubMenu( "finance", "finance_bank",
			array( 'page_title' => 'Bank pages', 'function' => array( Finance_Bank::instance(), 'show_bank_accounts_wrap' ) ) );

		$menu->AddSubMenu( "finance", "promote_users",
			array( 'page_title' => 'Multi site', 'function' => array( Core_Db_MultiSite::getInstance(), 'admin_page' ) ) );

		$menu->AddSubMenu( "users.php", "edit_shop_orders",
			array( 'page_title' => 'Client accounts', 'function' => array( "Finance_Clients", 'admin_page' ) ) );

		$menu->AddSubMenu( "users.php", "edit_shop_orders",
			array( 'page_title' => 'Payment methods', 'function' => array( "Finance_Payments", 'payment_methods' ) ) );

		$menu->AddSubMenu('users.php', 'edit_shop_orders', // Previous users.php
			array('page_title' => 'Payment List',
			      'menu_title' => 'Payment list',
			      'menu_slug' => 'payment_list',
			      'function' => 'payment_list')
		);


//		$menu->AddSubMenu( "finance", "working_hours_self",
//			array( 'page_title' => 'Hours entry', 'function' => array( "Finance_Salary", 'entry_wrapper' ) ) );

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
