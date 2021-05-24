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
if ( ! defined( 'FRESH_PLUGIN_FILE' ) ) {
	define( 'FRESH_PLUGIN_FILE', __FILE__ );
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

//if (1 == get_user_id())
//	show_errors();
// Include the main WooCommerce class.
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
	if ( ! class_exists( 'Fresh' ) ) {
		include_once dirname( __FILE__ ) . '/includes/class-fresh.php';
	}
	$plugin = new Fresh("Fresh");
	$plugin->run();
}

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

//if (get_user_id() == 1) add_filter('pre_get_posts', 'query_test');

function query_test($query)
{
		var_dump($query);
}

add_action('plugin_loaded', 'init_fresh', 30);

function init_fresh() {
//	if (!function_exists("MyLog"))
//		require_once(ABSPATH . "/wp-content/plugins/wpf_flavor/includes/core/fund.php");
	if (class_exists("WPF_Flavor") and class_exists('Finance')) {
		run_fresh();
	}

//	if ( ! is_plugin_active( 'wpf_flavor/wpf_flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
//		// Deactivate this plugin
//		deactivate_plugins(__FILE__);
//		return;
//	}
//
//	if ( ! is_plugin_active( 'finance/finance.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
//		// Deactivate this plugin
//		deactivate_plugins(__FILE__);
//		return;
//	}

//	run_fresh();
}
