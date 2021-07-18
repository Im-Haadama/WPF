<?php
/*
 * Created 25 Dec 2019
 * Plugin Name: WPF_flavor
 * Plugin URI: https://e-fresh.co.il
 * Description: base class for wpf plugins
 * Version: 1.0
 * Author: agla
 * Author URI: https://e-fresh.co.il
 * Text Domain: im-haadama
 *
 * @package Fresh
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$prev_time         = microtime(true);

if ( ! defined( 'FLAVOR_PLUGIN_FILE' ) ) {
	define( 'FLAVOR_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'WPF_Flavor' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wpf-flavor.php';
}

/**
 * Main instance of Flavor.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return WPF_Flavor
 */

function flavor() {
	return WPF_Flavor::instance();
}


// Global for backwards compatibility.
//$GLOBALS['flavor'] = flavor();

function run_flavor() {
	$plugin = WPF_Flavor::instance();
	$plugin->run();
}

run_flavor();

add_action( 'wp_head', 'add_viewport_meta_tag' , '1' );

function add_viewport_meta_tag() {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
}

abstract class FlavorDbObjects
{
	const users = 1;
	const team = 2;
	const project = 3;
	const company = 4;
	const sender = 5; // Who can send a task to a team.
}

register_activation_hook(__FILE__, 'wpf_flavor_activate');

function wpf_flavor_activate()
{
	$plugins_s = SqlQuerySingleScalar("select option_value from wp_options where option_name='active_plugins'");
	$plugins = unserialize($plugins_s);

	$p_i = array();
	foreach ($plugins as $plugin)
	{
		$pri = 5;
//	print substr($plugin, 0, 5) . "<br/>";
		switch (substr($plugin, 0, 5))
		{
			case "wooco":
				$pri = 1;
				break;
			case "wpf_fl":
				$pri = 2;
				break;
		}
		array_push($p_i, array($pri, $plugin));
	}
	usort($p_i, function($a, $b) { return $a[0] > $b[0]; });
	$plugins = [];
	foreach ($p_i as $p)
		if (! in_array($p[1], $plugins))
			array_push($plugins, $p[1]);
	$sql = "update wp_options set option_value = " .
	       QuoteText(serialize($plugins)) . " where option_name='active_plugins'";

	SqlQuery($sql);
}

function FlavorLog($message)
{
	MyLog($message, '', 'flavor.log');
}
