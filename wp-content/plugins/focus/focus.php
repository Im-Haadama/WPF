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

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

add_action('plugin_loaded', 'init_focus', 20);

function init_focus() {
	if ( ( ! class_exists( "Focus" ) ) and class_exists( "WPF_Flavor" )) {
		run_focus();
	}
}

// Include the main WooCommerce class.
/**
 * Main instance of Focus.
 * @return Focus
 */

function run_focus() {
	if ( ! class_exists( 'Focus' ) ) {
		include_once dirname( __FILE__ ) . '/includes/class-focus.php';
	}

	$plugin = Focus::instance();
	$plugin->run();
}

