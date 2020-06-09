<?php
/*
Plugin Name: Quantity Field on Shop Page for WooCommerce
Plugin URI:  http://wooassist.com
Description: Adds a ‘Quantity Field’ on the shop and category pages of your Woocommerce store. This allows the user to change the quantity of a product before adding it to the cart.
Version:     1.3.0
Author:      Wooassist
Author URI:  http://wooassist.com
*/

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'woa_wqfsp' ) ) :

class woa_wqfsp {

	/**
	 * @var The single instance of the class		 
	 */
	private static $_instance = null;

	/**
	 * Main woa_wqfsp Instance
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *	 
	 * @static
	 * @see woa_wqfsp()
	 * @return woa_wqfsp main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * woa_wqfsp Constructor.
	 */
	public function __construct() {	
		add_action( 'wp_enqueue_scripts', array( $this, 'woa_add_quantity_style' ) );
		add_action( 'init', array( $this, 'woa_quantity_handler' ) );
		add_action( 'init', array( $this, 'woa_confirm_add' ) );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'woa_add_quantity_fields' ), 10, 2 );
	}
	
	/**
	 * Add quantity fields.
	 */
	public function woa_add_quantity_fields($html, $product) {
		//add quantity field only to simple products
	if ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually() ) {
		//rewrite form code for add to cart button
		$html = '<form action="' . esc_url( $product->add_to_cart_url() ) . '" class="cart" method="post" enctype="multipart/form-data">';
		$html .= woocommerce_quantity_input( array(), $product, false );
		$html .= '<button type="submit" data-quantity="1" data-product_id="' . $product->get_id() . '" class="button alt ajax_add_to_cart add_to_cart_button product_type_simple">' . esc_html( $product->add_to_cart_text() ) . '</button>';
		$html .= '</form>';
	}
	return $html;
	}
	
	/**
	 * woa_wqfsp add stylesheet for quantity field.
	 */
	public function woa_add_quantity_style() {
	
		wp_enqueue_style( 'WQFSP_style', plugins_url( '/css/style.css', __FILE__ ) );
		
	}
	
	/**
	 * add AJAX support.
	 * synchs quantity field
	 */
	public function woa_quantity_handler() {
		wc_enqueue_js( '
		jQuery(function($) {
		$("form.cart").on("change", "input.qty", function() {
        $(this.form).find("[data-quantity]").attr("data-quantity", this.value);  //used attr instead of data, for WC 4.0 compatibility
		});
		' );

		wc_enqueue_js( '
		$(document.body).on("adding_to_cart", function() {
			$("a.added_to_cart").remove();
		});
		});
		' );
	}

	/**
	 * add checkmark
	 *
	 */
	public function woa_confirm_add() {
		wc_enqueue_js( '
		jQuery(document.body).on("added_to_cart", function( data ) {

		jQuery(".added_to_cart").after("<p class=\'confirm_add\'></p>");
});

		' );
	}
	
}

endif; // ! class_exists()

/**
 * Returns the main instance of woa_wqfsp.
 */
function woa_wqfsp_run() {
	return woa_wqfsp::instance();
}

/**
 * WC Detection
 *
 * @since  1.5.4
 * @return boolean
 */
if ( ! function_exists( 'woa_is_woocommerce_active' ) ) {
	function woa_is_woocommerce_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		
		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ;
	}
}

/*
 * Initialize
 */
if ( woa_is_woocommerce_active() ) {
	
	woa_wqfsp_run();
	
}