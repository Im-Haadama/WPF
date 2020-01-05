<?php

/**
 * Plugin Name: focus (full)
 * Plugin URI: https://aglamaz.com
 * Description: Task management tool
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: im-haadama
 *
 * @package Focus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'FOCUS_PLUGIN_FILE' ) ) {
	define( 'FOCUS_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Focus' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-focus.php';
}
/**
 * Main instance of Focus.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Focus
 */

// Global for backwards compatibility.
// $GLOBALS['focus'] = focus();

function run_focus() {
	$plugin = new Focus("Focus");
	$plugin->run();
}

run_focus();

