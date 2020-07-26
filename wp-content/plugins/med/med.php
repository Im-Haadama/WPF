<?php
/**
 * Plugin Name: Med
 * Created by Im-haadama
+ * User: agla
 * Date: 8/7/2020
 * Time: 18:35
 * Previous version: im-haadama.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'MED_PLUGIN_FILE' ) ) {
	define( 'MED_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MED_PLUGIN_DIR' ) ) {
	define( 'MED_PLUGIN_DIR', dirname( MED_PLUGIN_FILE ) );
}
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( ! is_plugin_active( 'flavor/flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Stop activation redirect and show error
	deactivate_plugins( __FILE__ );

	return;
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Med' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-med.php';
}
/**
 * Main instance of Med.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Med
 */

new Med();
