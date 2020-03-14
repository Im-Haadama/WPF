<?php


class Fresh_Settings extends  WC_Settings_Page {
	public function __construct() {
		$this->id    = 'fresh';
		$this->label = __( 'Fresh' );

		parent::__construct();
	}

	public function get_sections() {
		$sections = array(
			''             => __( 'General', 'fresh' ),
//			'inventory'    => __( 'Inventory', 'woocommerce' ),
//			'downloadable' => __( 'Downloadable products', 'woocommerce' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

}