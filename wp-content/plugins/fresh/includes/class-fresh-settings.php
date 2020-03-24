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

			$menu->AddMenu('Fresh Settings', 'Fresh', 'show_manager', 'fresh', __CLASS__ . '::general_settings');
			$menu->AddSubMenu('fresh', 'edit_shop_orders',
				array('page_title' => 'Settings',
				            'menu_title' => 'Settings',
				            'menu_slug' => 'settings',
				            'function' => __CLASS__ . '::general_settings'));
			$menu->AddSubMenu('fresh', 'edit_shop_orders',
					array('page_title' => 'Payment List',
					      'menu_title' => 'Payment list',
					      'menu_slug' => 'payment_list',
					      'function' => 'payment_list')
				);

			$menu->AddSubMenu("woocommerce", "edit_suppliers",
				array('page_title' => 'Suppliers', 'function' => array("Fresh_Suppliers" , 'admin_page' )));

			$menu->AddSubMenu("woocommerce", "edit_suppliers",
				array('page_title' => 'Needed', 'function' => array("Fresh_Packing" , 'needed_products' )));

			Fresh_Packing::add_admin($menu);
		}

		static function suppliers() {
			$result = '<h3>Suppliers</h3>';

			print $result;
		}

		static private function getPost()
		{
			return "/wp-content/plugins/fresh/post.php";
		}

		static function general_settings()
		{
			$result = ""; // Core_Html::gui_header(1, "general settings");
			$tabs = [];
			$args = [];
			$args["post_file"] = self::getPost();

			$tab = GetParam("tab", false, "baskets");
			$url = GetUrl(1) . "?page=settings&tab=";

			$basket_url = $url . "baskets";

			$tabs["baskets"] = array(
				"Baskets",
				$basket_url,
				Fresh_Basket::settings($basket_url, $args)
			);

			$tabs["suppliers"] = array(
				"Suppliers",
				$url . "suppliers",
				Fresh_Suppliers::admin_page()
				//Fresh_Suppliers::SuppliersTable()
			);

			$tabs["missing_pictures"] = array(
				"Missing Pictures",
				$url . "missing_pictures",
				Fresh_Catalog::missing_pictures()
			);

//		array_push( $tabs, array(
//			"workers",
//			"Workers",
//			self::company_workers( $company, $args )
//		) );

			$args["btn_class"] = "nav-tab";
			$args["tabs_load_all"] = true;
			$args["nav_tab_wrapper"] = "nav-tab-wrapper woo-nav-tab-wrapper";

			$result .= Core_Html::NavTabs($tabs, $args);
			$result .= $tabs[$tab][2];

			print $result;
		}

	}
