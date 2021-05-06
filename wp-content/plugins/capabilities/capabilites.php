<?php

/**
 * Plugin Name: capabilites (full)
 * Plugin URI: https://aglamaz.com
 * Description: Show who has certain capabilites
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: im-haadama
 *
 * @package Capabilites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
// Define IM_PLUGIN_FILE.
if ( ! defined( 'CAPABILITES_PLUGIN_FILE' ) ) {
	define( 'CAPABILITES_PLUGIN_FILE', __FILE__ );
}
// Include the main plugin class.
if ( ! class_exists( 'Capabilites' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-capabilites.php';
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Require parent plugin
if ( ! is_plugin_active( 'wpf_flavor/wpf_flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Stop activation redirect and show error
	deactivate_plugins(__FILE__);
	return;
}

/**
 * Main instance of Capabilites.
 *
 * Returns the main instance of plugin to prevent the need to use globals.
 *
 * @return Capabilites
  */
function capabilites() {
	return Capabilites::instance();
}

//
//// Global for backwards compatibility.
//$GLOBALS['capabilites'] = capabilites();

function run_capabilites() {
	$plugin = Capabilites::instance();
		// new Capabilites("Capabilites");
	$plugin->run();
}

run_capabilites();
