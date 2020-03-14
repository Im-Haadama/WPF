<?php

	class Fresh_Settings {
		static private $_instance;

		public function __construct() {
			$this->id        = 'fresh';
			$this->label     = __( 'Fresh' );
			self::$_instance = $this;

//			parent::__construct();
		}

		public function get_sections() {
			$sections = array(
				'' => __( 'General', 'fresh' ),
//			'inventory'    => __( 'Inventory', 'woocommerce' ),
//			'downloadable' => __( 'Downloadable products', 'woocommerce' ),
			);

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

		// Product comments
//	static function product_comments_tab()
//	{
//		print 1/0;
//		$settings_tab['product_comment'] = __( 'Enable Product Comments ' );
//		return $settings_tab;
//	}

}
