<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 12:54
 * Run in slave sites. Sync data from master.
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( "ROOT_DIR", dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( ROOT_DIR . "/fresh/multi-site/imMulti-site.php" );


function sync_from_master($debug) {
	print header_text( false, true, true );

	$i = new ImMultiSite();
	if ( $i->isMaster() ) {
		return "Master, no need to sync";
	}

	// Check if master is available.
	try {
		$i->Run( "/fresh/about.php", $i->getMaster(), false, $debug );
	} catch (Exception $e) {
		print "Server not responding. Try later. Operation aborted";
		die (1);
	}

	print gui_header( 1, "מסנכרן מיקומים" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zone_locations", "location_id", 0, null, null, $debug );

	print gui_header( 1, "מסנכרן שיטות משלוח" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zone_methods", "instance_id", 0, null, null, $debug );

	print gui_header( 1, "מסנכרן איזורי משלוח" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zones", "zone_id", 0, null, null, $debug );

	print gui_header( 1, "מסנכרן משימות" );
	$i->UpdateFromRemote( "im_missions", "id", 0, null, null, $debug );

	print gui_header( 1, "מסנרכן שיטות משלוח" );
	$i->UpdateFromRemote( "wp_options", "option_name", 0, "option_name like 'woocommerce_flat_rate_%_settings'", array( 'option_id' ), $debug);
}