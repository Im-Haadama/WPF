<?php

/**
 * Plugin Name: israel shop
 * Plugin URI: https://aglamaz.com
 * Description:  Israel shop matters: 1) manage vat-free categories, 2) Cities, zipcodes and zones.
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 * @package Fresh
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'FRESH_PLUGIN_FILE' ) ) {
	define( 'FRESH_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Fresh' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-fresh.php';
}
/**
 * Main instance of Fresh.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Fresh
  */

function fresh() {
	return Fresh::instance();
}

function run_fresh() {
	$plugin = new Fresh("Fresh");
	$plugin->run();
}

run_fresh();

