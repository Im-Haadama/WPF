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
						'id'	=> 'activate',

					)
				);

				return $custom_settings;
			} else {
				return $settings;
			}
		}

		// Product comments
//	static function product_comments_tab()
//	{
//		print 1/0;
//		$settings_tab['product_comment'] = __( 'Enable Product Comments ' );
//		return $settings_tab;
//	}

}
