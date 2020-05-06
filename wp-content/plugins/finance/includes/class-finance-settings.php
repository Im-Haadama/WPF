<?php

class Finance_Settings {
	static private $_instance;

	public function __construct() {
		$this->id        = 'fresh';
		$this->label     = __( 'Fresh' );
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

	static function admin_menu() {
		$menu = new Core_Admin_Menu();
//		new Finance_Clients(); // Just load

//		$menu->AddMenu( 'Fresh Settings', 'Fresh', 'show_manager', 'fresh', __CLASS__ . '::general_settings' );

		$menu->AddSubMenu( "users.php", "edit_shop_orders",
			array( 'page_title' => 'Client accounts', 'function' => array( "Finance_Clients", 'admin_page' ) ) );

		$menu->AddSubMenu( "users.php", "edit_shop_orders",
			array( 'page_title' => 'Payment methods', 'function' => array( "Finance_Payments", 'payment_methods' ) ) );

	}

	static function suppliers() {
		$result = '<h3>Suppliers</h3>';

		print $result;
	}
}
