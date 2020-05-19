<?php

/**
 * Plugin Name: Israel shop
 * Plugin URI: https://aglamaz.com
 * Description: handle israel zones and categories for vat (מע"מ)
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 * @package Israel Shop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'ISRAEL_ZONES_PLUGIN_FILE' ) ) {
	define( 'ISRAEL_ZONES_PLUGIN_FILE', __FILE__ );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

if ( ! is_plugin_active( 'flavor/flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Stop activation redirect and show error
	deactivate_plugins(__FILE__);
	return;
}

// Include the main class.
if ( ! class_exists( 'Israel_Shop' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-israel-shop.php';
}

/**
 * Main instance of Fresh.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Fresh
 */

function run_israel_shop() {
	$i = new Israel_Shop("israel_shop");
	$i->init();
}

run_israel_shop();
