<?php

/*
 * Created 25 Dec 2019
 * Plugin Name: flavor
 * Plugin URI: https://aglamaz.com
 * Description: flavor customize other wp-f plugins.
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: im-haadama
 *
 * @package Fresh
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'FLAVOR_PLUGIN_FILE' ) ) {
	define( 'FLAVOR_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Flavor' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-flavor.php';
}
/**
 * Main instance of Flavor.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Flavor
 */

function flavor() {
	return Flavor::instance();
}

// Global for backwards compatibility.
//$GLOBALS['flavor'] = flavor();

function run_flavor() {
	$plugin = new Flavor("Flavor");
	$plugin->run();
}

run_flavor();

//if (function_exists('get_user_id') and (get_user_id() == 1)) {
//	show_errors();
//}

add_action( 'wp_head', 'add_viewport_meta_tag' , '1' );

function add_viewport_meta_tag() {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
}

//function change_wordpress_locale( $lang ) {
//	global $post;
//
////	if ($post ) MyLog($post->ID);
////	if ($post and ($post->ID == 15391)) return 'en_US';
////	return 'he_IL';
//}
//
//add_filter( 'locale', 'change_wordpress_locale' );
