<?php

/**
 * Plugin Name: fresh (full)
 * Plugin URI: https://aglamaz.com
 * Description: Backoffice for store that sells fresh goods.
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: im-haadama
 *
 * @package Fresh
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'FRESH_PLUGIN_FILE' ) ) {
	define( 'FRESH_PLUGIN_FILE', __FILE__ );
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

// Global for backwards compatibility.
$GLOBALS['fresh'] = fresh();

function run_fresh() {
	$plugin = new Fresh("Fresh");
	$plugin->run();
}

run_fresh();

function fresh_cron_hook()
{
	$f = fopen("temp_log", "a");
	fwrite ($f, "cron running" . date('y-m-t'));
}
