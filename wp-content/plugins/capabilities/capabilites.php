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
	print "aasdfasdf";
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

// Require parent plugin
if ( require_once( dirname(dirname(__FILE__)) . '/flavor/flavor.php' ) ) ;
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
