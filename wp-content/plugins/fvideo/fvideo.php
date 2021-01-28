<?php

/**
 * Plugin Name: Fvideo
 * Plugin URI: https://aglamaz.com
 * Description:  fvideo site management
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 * @package Fvideo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//require_once(ABSPATH . '/wp-content/plugins/woocommerce/woocommerce.php');
if (! class_exists("Core_Database")) {
	print( "<a href='/wp-content/plugins/fvideo/fvideo.php'>must load flavor before fvideo</a>" );

	return;
}

if ( ! defined( 'FVIDEO_PLUGIN_FILE' ) ) {
	define( 'FVIDEO_PLUGIN_FILE', __FILE__ );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Require parent plugin
if ( ! is_plugin_active( 'flavor/flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Deactivate this plugin
	deactivate_plugins(__FILE__);
	return;
}

if ( ! class_exists( 'Fvideo' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-fvideo.php';
}

function fvideo() {
	return Fvideo::instance();
}

function run_fvideo() {
	$plugin = FVideo::instance();
	$plugin->run();
}

run_fvideo();
