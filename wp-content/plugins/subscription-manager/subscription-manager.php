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

if ( ! defined( 'SUBSCRIPTION_MANAGER_PLUGIN_FILE' ) ) {
	define( 'SUBSCRIPTION_MANAGER_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SUBSCRIPTION_MANAGER_PLUGIN_DIR' ) ) {
	define( 'SUBSCRIPTION_MANAGER_PLUGIN_DIR', dirname(SUBSCRIPTION_MANAGER_PLUGIN_FILE) );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Require parent plugin
if ( ! is_plugin_active( 'wpf_flavor/wpf_flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Deactivate this plugin
	deactivate_plugins(__FILE__);
	return;
}

function run_subscription_manager()
{
	if (! class_exists("Subscription_Manager"))
	{
		include_once dirname( __FILE__ ) . '/includes/class-subscription-manager.php';
	}
	$instance = Subscription_Manager::instance();

	$instance->run();
}

add_action('plugin_loaded', 'init_subscription_manager', 20);

function init_subscription_manager() {
	if ( ( ! class_exists( "Subscription_Manager" ) ) and class_exists( "WPF_Flavor" ) ) {
		run_subscription_manager();
	}
}

