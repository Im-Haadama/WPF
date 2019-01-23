<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 12:54
 */
require_once( ROOT_DIR . "/agla/gui/inputs.php" );
require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );

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