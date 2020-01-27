<?php

/**
 * Plugin Name: finance (full)
 * Plugin URI: https://aglamaz.com
 * Description: Bank info and detail.
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: im-haadama
 *
 * @package Finance
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );


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


function finance() {
	return Finance::instance();
}

// Global for backwards compatibility.
$GLOBALS['finance'] = finance();

function run_finance() {
//	print "runner: ". FINANCE_INCLUDES . "<br/>";
	$instance = finance();

//	$instance->run();
}

//run_finance();
