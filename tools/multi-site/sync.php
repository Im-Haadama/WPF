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

print "a";

if ( ! defined( "ROOT_DIR" ) ) {
	define( "ROOT_DIR", dirname( dirname( dirname( __FILE__ ) ) ) );
}

print "b";

require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( ROOT_DIR . "/tools/multi-site/imMulti-site.php" );

function sync_from_master() {
	print header_text( false, true, true );

	$i = new ImMultiSite();
	print gui_header( 1, "מסנכרן מיקומים" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zone_locations", "location_id" );

	print gui_header( 1, "מסנכרן שיטות משלוח" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zone_methods", "instance_id" );

	print gui_header( 1, "מסנכרן איזורי משלוח" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zones", "zone_id" );

	print gui_header( 1, "מסנכרן משימות" );
	$i->UpdateFromRemote( "im_missions", "id" );

	print gui_header( 1, "מסנרכן שיטות משלוח" );
	$i->UpdateFromRemote( "wp_options", "option_name", 0, "option_name like 'woocommerce_flat_rate_%_settings'", array( 'option_id' ) );
}