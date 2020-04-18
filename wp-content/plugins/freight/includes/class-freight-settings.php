<?php

	class Freight_Settings {
		static private $_instance;

		public function __construct() {
			$this->id        = 'freight';
			$this->label     = __( 'Freight' );
			self::$_instance = $this;

			self::init();
		}

		public function get_sections() {
			$sections = array(
				'' => __( 'General', 'freight' ),
//			'inventory'    => __( 'Inventory', 'woocommerce' ),
//			'downloadable' => __( 'Downloadable products', 'woocommerce' ),
			);

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

		public function init()
		{
//			add_filter( 'woocommerce_get_sections_products' , array(__CLASS__, 'product_comment_add_settings_tab') );
//			add_filter( 'woocommerce_get_settings_products' , array(__CLASS__, 'product_comment_get_settings'), 10, 2);
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

			$menu->AddMenu('Freight Settings', 'Freight', 'show_manager', 'freight', __CLASS__ . '::general_settings');
			$menu->AddSubMenu('freight', 'edit_shop_orders',
				array('page_title' => 'Settings',
				            'menu_title' => 'Settings',
				            'menu_slug' => 'settings',
				            'function' => __CLASS__ . '::general_settings'));

			$menu->AddSubMenu('freight', 'edit_shop_orders',
				array('page_title' => 'Dispatcher',
				      'menu_title' => 'Dispatcher',
				      'menu_slug' => 'dispatcher',
				      'function' => 'Freight_Mission_Manager::dispatcher_wrap'));

			$menu->AddSubMenu('freight', 'edit_shop_orders',
				array('page_title' => 'Missions',
				      'menu_title' => 'Missions',
				      'menu_slug' => 'missions',
				      'function' => 'Freight_Mission_Manager::missions'));

			//			$menu->AddSubMenu('freight', 'edit_shop_orders',
//					array('page_title' => 'Payment List',
//					      'menu_title' => 'Payment list',
//					      'menu_slug' => 'payment_list',
//					      'function' => 'payment_list')
//				);

//			$menu->AddSubMenu("woocommerce", "edit_suppliers",
//				array('page_title' => 'Suppliers', 'function' => array("Freight_Suppliers" , 'admin_page' )));
//
//			$menu->AddSubMenu("woocommerce", "edit_suppliers",
//				array('page_title' => 'Needed', 'function' => array("Freight_Packing" , 'needed_products' )));

//			Freight_Packing::add_admin($menu);
		}

		static function suppliers() {
			$result = '<h3>Suppliers</h3>';

			print $result;
		}

		static private function getPost()
		{
			return "/wp-content/plugins/freight/post.php";
		}

		static function general_settings()
		{
			$result = ""; // Core_Html::gui_header(1, "general settings");
			$tabs = [];
			$args = [];
			$args["post_file"] = self::getPost();

			$tab = GetParam("tab", false, "methods");
			$url = GetUrl(1) . "?page=settings&tab=";
			$operation = GetParam("operation", false, null);

			$tabs["help"] = array(
				"Help",
				$url . "help",
				self::help($args, $operation)
			);

			$tabs["zones"] = array(
				"Zones",
				$url . "zones",
				Freight_Zones::settings($args, $operation)
			);

			$tabs["methods"] = array(
				"Methods",
				$url . "methods",
				Freight_Methods::settings($args, $operation)
			);

//			$tabs["suppliers"] = array(
//				"Suppliers",
//				$url . "suppliers",
//"bbbb"
////				Freight_Suppliers::admin_page()
//				//Freight_Suppliers::SuppliersTable()
//			);
//
//			$tabs["missing_pictures"] = array(
//				"Missing Pictures",
//				$url . "missing_pictures",
//"ccc"
////				Freight_Catalog::missing_pictures()
//			);

//		array_push( $tabs, array(
//			"workers",
//			"Workers",
//			self::company_workers( $company, $args )
//		) );

			$args["btn_class"] = "nav-tab";
			$args["tabs_load_all"] = true;
			$args["nav_tab_wrapper"] = "nav-tab-wrapper woo-nav-tab-wrapper";

			$result .= Core_Html::NavTabs($tabs, $args);
			if (isset($tabs[$tab][2]))
				$result .= $tabs[$tab][2];
			else
				$result .= "array index 2 is missing";

			print $result;
		}

		static function help()
		{
			$result = "";
		 $result .= Core_Html::GuiHeader(1, "Updating shipping times");
		 $result .= "<li>Create the zones" . Core_Html::GuiHyperlink("here: ", "/wp-admin/admin.php?page=wc-settings&tab=shipping") . "</li>";
		 $result .= "<li>In zones tab update minimum order, and default fee for delivery</li>";
		 $result .= "<li>In paths create path = cluster of zones: set the zones and days of arrival. E.g Tel Aviv can be in zone center on Monday, and on zone Tel-Aviv on Thursday.</li>";

		 return $result;
		}
	}
