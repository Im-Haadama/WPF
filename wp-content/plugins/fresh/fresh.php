<?php

/**
 * Plugin Name: Fresh
 * Plugin URI: https://aglamaz.com
 * Description:  fresh store management
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

if (! class_exists("Core_Database")) {
	print( "<a href='/wp-content/plugins/fresh/fresh.php'>must load flavor before fresh</a>" );

	return;
}

if ( ! defined( 'FRESH_PLUGIN_FILE' ) ) {
	define( 'FRESH_PLUGIN_FILE', __FILE__ );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Require parent plugin
if ( ! is_plugin_active( 'flavor/flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Deactivate this plugin
	deactivate_plugins(__FILE__);
	return;
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
