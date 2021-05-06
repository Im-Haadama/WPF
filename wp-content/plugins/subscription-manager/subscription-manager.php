<?php

/**
 * Plugin Name: Subscription Manager
 * Plugin URI: https://e-subscription_manager.co.il
 * Description:  subscription manager
 * Version: 1.0
 * Author: agla
 * Author URI: http://e-subscription_manager.co.il
 * Text Domain: wpf
 *
 * @package subscription-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if (! class_exists("Core_Database")) {
	print( "<a href='/wp-content/plugins/subscription_manager/subscription_manager.php'>must load flavor before subscription_manager</a>" );

	return;
}

if ( ! defined( 'SUBSCRIPTION_MANAGER_PLUGIN_FILE' ) ) {
	define( 'SUBSCRIPTION_MANAGER_PLUGIN_FILE', __FILE__ );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Require parent plugin
if ( ! is_plugin_active( 'wpf_flavor/wpf_flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Deactivate this plugin
	deactivate_plugins(__FILE__);
	return;
}


// Include the main WooCommerce class.
if ( ! class_exists( 'Subscription_manager' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-subscription-manager.php';
}
/**
 * Main instance of Subscription_manager.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Subscription_manager
 */

function Subscription_Manager() {
	return Subscription_manager::instance();
}

function run_subscription_manager() {
	$plugin = new Subscription_Manager("Subscription_Manager");
	$plugin->run();
}

run_subscription_manager();
