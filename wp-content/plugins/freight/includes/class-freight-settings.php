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

		function admin_menu()
		{
			$menu = Core_Admin_Menu::instance();

			$menu->AddMenu('Freight Settings', 'Freight', 'shop_manager', 'freight', array($this, 'freight_general'));
			$menu->AddSubMenu('freight', 'edit_shop_orders',
				array('page_title' => 'Settings',
				            'menu_title' => 'Settings',
				            'menu_slug' => 'freight_settings',
				            'function' => array($this, 'freight_general')));

//			$menu->AddSubMenu('freight', 'edit_shop_orders',
//				array('page_title' => 'Dispatcher',
//				      'menu_title' => 'Dispatcher',
//				      'menu_slug' => 'dispatcher',
//				      'function' => 'Freight_Mission_Manager::dispatcher_wrap'));

			if ($legacy = Freight::instance()->getLegacy())
				$menu->AddSubMenu('freight', 'edit_shop_orders',
					array('page_title' => 'Legacy',
					      'menu_title' => 'Legacy',
					      'menu_slug' => 'legacy',
					      'function' => array($legacy, 'general_settings')));

//			add_options_page( 'Freight settings', 'Example Plugin Menu', 'manage_options', 'dbi-example-plugin', __CLASS__ . '::render_settings_page' );
//
//			self::render_settings_page();
		}

//		static function render_settings_page()
//		{
//			print "AAAA";
//		}

		static function suppliers() {
			$result = '<h3>Suppliers</h3>';

			print $result;
		}

		static private function getPost()
		{
			return WPF_Flavor::getPost();
		}

		public function freight_general()
		{
			$result = Core_Html::GuiHeader(1, "Freight settings");
			$tabs = [];
			$args = [];
			$args["post_file"] = self::getPost();

			$tab = GetParam("tab", false, "methods");
			$url = GetUrl(1) . "?page=freight_settings&tab=";

			$operation = GetParam("operation", false, null);
			if ($operation) {
				$args=array("operation"=>$operation, "post_file"=>WPF_Flavor::getPost());
				do_action( $operation, $args );
				return;
			}
			$tabs["help"] = array(
				"Help",
				$url . "help",
				self::help($args, $operation)
			);
			$tabs["zones"] = array(
				"Zones",
				$url . "zones",
				$tab == "zones" ? Freight_Zones::settings($args, $operation) : ""
			);
			$tabs["methods"] = array(
				"Methods",
				$url . "methods",
				$tab == "methods" ? Freight_Methods::settings($args, $operation) : ""
			);
			$tabs["mission_types"] = array(
				"Mission Types",
				$url . "mission_types",
				$tab == "mission_types" ? Freight_Methods::mission_types($args, $operation) : ""
			);
			$tabs["general"] = array(
				"General",
				$url . "general",
				$tab == "general" ? self::general($args, $operation) : ""
			);

//			$tabs["debug"] = array(
//				"Debug",
//				$url."debug",
//				$tab == "debug" ? self::debug() : ""
//			);

			$cities_tab = apply_filters("wpf_freight_cities", "");
			if ($cities_tab)
			$tabs["cities"] = array(
				"Zone cities",
				$url . "cities",
				$cities_tab
			);

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

		static function general($args, $operation) {
			$result = Core_Html::GuiHeader(2,"Switching day time");
			$result .= "This setting determines when to finished getting orders for the next day" . "<br/>";
			$result .= Core_Html::GuiInput("time", InfoGet("freight_switching_time", true, "12"), array("events"=>'onchange="update_switch_time()"'));
			return $result;
		}
		static function help($args, $operation)
		{
			$result = "";
		 $result .= Core_Html::GuiHeader(1, "Updating shipping times");
		 $result .= "<li>Create the zones" . Core_Html::GuiHyperlink("here: ", "/wp-admin/admin.php?page=wc-settings&tab=shipping") . "</li>";
		 $result .= "<li>In zones tab update minimum order, and default fee for delivery</li>";
		 $result .= "<li>To change the delivery day, in the Mission types set the day. Monday is 1, Tuesday is 2, etc. Than in Methods tab, press update!</li>";
//		 $result .= "<li>In paths create path = cluster of zones: set the zones and days of arrival. E.g Tel Aviv can be in zone center on Monday, and on zone Tel-Aviv on Thursday.</li>";

		 return $result;
		}

		static function debug()
		{
			$post = 18255;

			$meta = SqlQueryArray("select meta_key, meta_value from wp_postmeta where post_id = $post");
			foreach ($meta as $item){
				$array =  @unserialize($item[1]);
				if ($array) {
					print $item[0] . ":<br/>";
					print_r(unserialize($item[1]));
				}
			}
		}
	}

