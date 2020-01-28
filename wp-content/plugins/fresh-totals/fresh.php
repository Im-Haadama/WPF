<?php
/**
 * Plugin Name: fresh-totals
 * Plugin URI: http://www.aglamaz.com/fresh
 * Description: Addon to woocommerce to summerize open orders - how many products are in active orders. Use shortcode [fresh-totals].
 * Version: 1.0
 * Author: WPF
 * Author URI: http://wordpress-f.com
 * Text Domain: wpf
 *
 * @package fresh
 */

/**
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define WC_PLUGIN_FILE.
if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
	define( 'WC_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Fresh' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-fresh.php';
}

/**
 * Main instance of WooCommerce.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return WooCommerce
 * @since  2.1
 */
function fresh() {
	return fresh::instance();
}

function run_fresh() {
	$plugin = new Fresh("Fresh");
	$plugin->run();
}

run_fresh();

