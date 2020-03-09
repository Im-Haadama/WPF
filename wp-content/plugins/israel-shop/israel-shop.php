<?php

/**
 * Plugin Name: Israel zones
 * Plugin URI: https://aglamaz.com
 * Description:  wp-f backoffice for fresh goods store management.
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
if ( ! defined( 'ISRAEL_ZONES_PLUGIN_FILE' ) ) {
	define( 'ISRAEL_ZONES_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Israel_Zones' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-israel-zones.php';
}
/**
 * Main instance of Fresh.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Fresh
 */

function israelZones() {
	return Israel_Zones::instance();
}

function run_israel_zones() {
	$i = new Israel_Zones("Fresh");
	$i->run(5); // find next 5 zipcodes
}

run_israel_zones();

