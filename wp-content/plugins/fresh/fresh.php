<?php

/**
 * Plugin Name: Fresh
 * Plugin URI: https://aglamaz.com
 * Description:  fresh store management
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

//require_once(ABSPATH . '/wp-content/plugins/woocommerce/woocommerce.php');
if (! class_exists("Core_Database")) {
	print( "<a href='/wp-content/plugins/fresh/fresh.php'>must load flavor before fresh</a>" );

	return;
}

if ( ! defined( 'FRESH_PLUGIN_FILE' ) ) {
	define( 'FRESH_PLUGIN_FILE', __FILE__ );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Require parent plugin
if ( ! is_plugin_active( 'flavor/flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Deactivate this plugin
	deactivate_plugins(__FILE__);
	return;
}

//if (1 == get_user_id())
//	show_errors();
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

function run_fresh() {
	$plugin = new Fresh("Fresh");
	$plugin->run();
}

run_fresh();

//add_filter( 'wp_calculate_image_sizes', 'sizes', 10, 2 );
//
//function sizes( $sizes, $size ) {
//	$width = $size[0];
//
////	print 1/0;
////	MyLog(get_post_type());
//	if ( 840 <= $width ) {
//		$sizes = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 1362px) 62vw, 840px';
//	}
//
//	if ( 'page' === get_post_type() ) {
//		if ( 840 > $width ) {
//			$sizes = '(max-width: ' . $width . 'px) 85vw, ' . $width . 'px';
//		}
//	} else {
//		if ( 840 > $width && 600 <= $width ) {
//			$sizes = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 984px) 61vw, (max-width: 1362px) 45vw, 600px';
//		} elseif ( 600 > $width ) {
//			$sizes = '(max-width: ' . $width . 'px) 85vw, ' . $width . 'px';
//		}
//	}
//
//	return $sizes;
//}
