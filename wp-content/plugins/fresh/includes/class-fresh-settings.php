<?php

	class Fresh_Settings {
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

		public function init()
		{
			add_filter( 'woocommerce_get_sections_products' , array(__CLASS__, 'product_comment_add_settings_tab') );
			add_filter( 'woocommerce_get_settings_products' , array(__CLASS__, 'product_comment_get_settings'), 10, 2);
		}

		static public function product_comment_add_settings_tab( $settings_tab ){
			$settings_tab['product_comment'] = __( 'Enable Product Comments ' );
			return $settings_tab;
		}

		static public function product_comment_get_settings( $settings, $current_section )
		{
			if ('product_comment' == $current_section ) {
				$custom_settings =  array(

					array(
						'name' => __( '' ),
						'type' => 'checkbox',
						'desc' => __( 'Enable Product Comments on Cart'),
						'id'	=> 'product_comment_view'
					),
					array(
						'name' => __( 'Activate' ),
						'type' => 'button',
						'desc' => __( 'Activate plugin'),
						'desc_tip' => true,
						'class' => 'button-secondary',
						'id'	=> 'activate'
					),

					array(
						'name' => __( '' ),
						'type' => 'checkbox',
						'desc' => __( 'Show Product Without pictures'),
						'id'	=> 'product_no_pictures_view'
					),
					array(
						'name' => __( 'Activate' ),
						'type' => 'button',
						'desc' => __( 'Show products without pictures'),
						'desc_tip' => true,
						'class' => 'button-secondary',
						'id'	=> 'activate'
					)
				);

				return $custom_settings;
			} else {
				return $settings;
			}
		}

		static function admin_menu()
		{
			$menu = new Core_Admin_Menu();

			// General Settings
			$menu->AddSubMenu('edit.php?post_type=product', 'edit_shop_orders',
				array('page_title' => 'Missing pictures',
				            'menu_title' => 'Missing pictures',
				            'menu_slug' => 'missing',
				            'function' => "Fresh_Catalog::missing_pictures"));

			$menu->AddSubMenu('users.php', 'edit_shop_orders',
					array('page_title' => 'Payment List',
					      'menu_title' => 'Payment list',
					      'menu_slug' => 'payment_list',
					      'function' => 'payment_list')
				);

			/////////////////////
			// WOO menu        //
			////////////////////

			// Suppliers
			$menu->AddSubMenu("woocommerce", "edit_shop_orders",
				array('page_title' => 'Suppliers', 'function' => array("Fresh_Suppliers" , 'admin_page' )));

			// Needed products
			$menu->AddSubMenu("woocommerce", "edit_shop_orders",
				array('page_title' => 'Needed', 'function' => array("Fresh_Packing" , 'needed_products' )));

			// Needed products
			$menu->AddSubMenu("woocommerce", "edit_shop_orders",
				array('page_title' => 'Packing', 'function' => array("Fresh_Packing" , 'table' )));

			// Deliveries
			$menu->AddSubMenu("woocommerce", "edit_shop_orders",
				array('page_title' => 'Deliveries', 'function' => array("Fresh_Delivery" , 'admin_page' )));


			//////////////
			// Products //
			//////////////

			// Baskets
			$menu->AddSubMenu("edit.php?post_type=product", "edit_shop_orders",
				array('page_title' => 'Baskets', 'function' => array("Fresh_Basket" , 'SettingsWrap' )));

		}

		static function suppliers() {
			$result = '<h3>Suppliers</h3>';

			print $result;
		}

		static private function getPost()
		{
			return "/wp-content/plugins/fresh/post.php";
		}

//		static function pictures()
//		{
//			$result = ""; // Core_Html::gui_header(1, "general settings");
//			$tabs = [];
//			$args = [];
//			$args["post_file"] = self::getPost();
//
//			$url = GetUrl();// . "?page=settings&tab=";
//
//			$tabs["suppliers"] = array(
//				"Suppliers",
//				$url . "suppliers",
//				Fresh_Suppliers::admin_page()
//				//Fresh_Suppliers::SuppliersTable()
//			);
//
//			$tabs["missing_pictures"] = array(
//				"Missing Pictures",
//				$url . "missing_pictures",
//				Fresh_Catalog::missing_pictures()
//			);
//
////		array_push( $tabs, array(
////			"workers",
////			"Workers",
////			self::company_workers( $company, $args )
////		) );
//
//			$args["btn_class"] = "nav-tab";
//			$args["tabs_load_all"] = true;
//			$args["nav_tab_wrapper"] = "nav-tab-wrapper woo-nav-tab-wrapper";
//
//			$result .= Core_Html::NavTabs($tabs, $args);
////			$result .= $tabs[$tab][2];
//
//			print $result;
//		}

	}
