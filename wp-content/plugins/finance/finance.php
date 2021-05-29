<?php
/**
 * Plugin Name: WPF  finance (full)
 * Plugin URI: https://e-fresh.co.il
 * Description: Delivery missions, finance transactions.
 * Version: 1.0
 * Author: agla
 * Author URI: http://e-fresh.co.il
 * Text Domain: e-fresh
 *
 * @package Finance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'FINANCE_PLUGIN_FILE' ) ) {
	define( 'FINANCE_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'FINANCE_PLUGIN_DIR' ) ) {
	define( 'FINANCE_PLUGIN_DIR', dirname(FINANCE_PLUGIN_FILE) );
}
require_once(ABSPATH . 'wp-admin/includes/plugin.php');

if ( ! is_plugin_active( 'wpf_flavor/wpf_flavor.php' ) /* and current_user_can( 'activate_plugins' ) */ ) {
	// Stop activation redirect and show error
	deactivate_plugins(__FILE__);
	return;
}

/**
 * Main instance of Finance.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Finance
 */


//function finance() {
//	return Finance::instance();
//}

// Global for backwards compatibility.
//$GLOBALS['finance'] = finance();

function run_finance() {
//	print "runner: ". FINANCE_INCLUDES . "<br/>";
	if (! class_exists("Finance"))
	{
		include_once dirname( __FILE__ ) . '/includes/class-finance.php';
	}
	$instance = Finance::instance();

	$instance->run();
}

add_action('plugin_loaded', 'init_finance', 20);

add_filter( 'wc_order_statuses', 'add_awaiting_shipment_to_order_statuses' );
add_action( 'init', 'register_awaiting_shipment_order_status' );

function add_awaiting_shipment_to_order_statuses( $order_statuses ) {
	$new_order_statuses = array();

	// add new order status after processing
	foreach ( $order_statuses as $key => $status ) {

		$new_order_statuses[ $key ] = $status;

		if ( 'wc-processing' === $key ) {
			$new_order_statuses['wc-awaiting-shipment'] = 'ממתין למשלוח';
			$new_order_statuses['wc-awaiting-document'] = 'ממתין לתעודת משלוח';
		}
	}

	return $new_order_statuses;
}

function register_awaiting_shipment_order_status() {
	register_post_status( 'wc-awaiting-shipment', array(
		'label'                     => 'ממתין למשלוח',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'ממתין למשלוח <span class="count">(%s)</span>', 'Awaiting shipment <span class="count">(%s)</span>' )
	) );

	register_post_status( 'wc-awaiting-document', array(
		'label'                     => 'Awaiting shipment document',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Awaiting shipment document<span class="count">(%s)</span>', 'Awaiting shipment <span class="count">(%s)</span>' )
	) );
}

add_filter( 'wc_product_sku_enabled', '__return_false' );

function init_finance() {
	if ( ( ! class_exists( "Finance" ) ) and class_exists( "WPF_Flavor" ) and class_exists( 'WC_Customer')) {
		run_finance();
	}
}

add_action('admin_notices', 'finance_depend', 20);

function finance_depend()
{
	if (! class_exists(('WC_Customer')))
		echo '<div class="notice notice-warning">Finance plugin needs Woocommerce. Make sure it\'s running</div>';
}