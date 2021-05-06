<?php
/**
 * Plugin Name: Freight
 * Plugin URI: https://aglamaz.com
 * Description:  freight store management
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 * @package Freight
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'FREIGHT_PLUGIN_FILE' ) ) {
	define( 'FREIGHT_PLUGIN_FILE', __FILE__ );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Require parent plugin
if ( ! is_plugin_active( 'wpf_flavor/wpf_flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Deactivate this plugin
	deactivate_plugins(__FILE__);
	return;
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Freight' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-freight.php';
}
/**
 * Main instance of Freight.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Freight
  */

function freight() {
	return Freight::instance();
}

function run_freight() {
	$plugin = new Freight("Freight");
	$plugin->run();
}

add_action('init', 'init_freight', 20);

function init_freight() {
	run_freight();
}

run_freight();
