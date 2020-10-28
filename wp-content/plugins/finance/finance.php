<?php
/**
 * Plugin Name: finance (full)
 * Plugin URI: https://e-fresh.co.il
 * Description: Bank info and detail.
 * Version: 1.0
 * Author: agla
 * Author URI: http://e-fresh.co.il
 * Text Domain: e-fresh
 *
 * @package Finance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'FINANCE_PLUGIN_FILE' ) ) {
	define( 'FINANCE_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'FINANCE_PLUGIN_DIR' ) ) {
	define( 'FINANCE_PLUGIN_DIR', dirname(FINANCE_PLUGIN_FILE) );
}
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
if ( ! is_plugin_active( 'flavor/flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Stop activation redirect and show error
	deactivate_plugins(__FILE__);
	return;
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Finance' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-finance.php';
}
/**
 * Main instance of Finance.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Finance
 */


//function finance() {
//	return Finance::instance();
//}

// Global for backwards compatibility.
//$GLOBALS['finance'] = finance();

function run_finance() {
//	print "runner: ". FINANCE_INCLUDES . "<br/>";
	$instance = Finance::instance();

	$instance->run();
}

run_finance();
