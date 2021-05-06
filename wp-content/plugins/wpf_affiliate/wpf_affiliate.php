<?php
/**
 * Plugin Name: WPF Affiliate
 * Plugin URI: https://aglamaz.com
 * Description: analize traffic
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 * @package Fresh
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! is_plugin_active( 'wpf_flavor/wpf_flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Deactivate this plugin
	deactivate_plugins(__FILE__);
	return;
}

require_once(ABSPATH . '/wp-content/plugins/flavor.php');

if (! class_exists("Core_Database")) {
	print( "<a href='/wp-content/plugins/fresh/fresh.php'>must load flavor before fresh</a>" );

	return;
}

if ( ! defined( 'FRESH_PLUGIN_FILE' ) ) {
	define( 'FRESH_PLUGIN_FILE', __FILE__ );
}

$plugin = new WPF_Affiliate();
$plugin->run();
