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
if ( ! is_plugin_active( 'flavor/flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Stop activation redirect and show error
	deactivate_plugins(__FILE__);
	return;
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Focus' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-focus.php';
}
/**
 * Main instance of Focus.
 * @return Focus
 */

function run_focus() {
	$plugin = new Focus("Focus");
	$plugin->run();
}

run_focus();


//if (get_user_id()== 1){
//	$t = new Focus_Tasklist(8);
//	$t->run();
//}
