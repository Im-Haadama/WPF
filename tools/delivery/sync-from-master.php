<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/04/18
 * Time: 15:55
 */


require_once( "../im_tools.php" );
require_once( "../multi-site/multi-site.php" );
require_once( "../gui/inputs.php" );

print header_text( false, true, true );

print gui_header( 1, "מסנכרן מיקומים" );
MultiSite::UpdateFromRemote( "wp_woocommerce_shipping_zone_locations", "location_id" );

print gui_header( 1, "מסנכרן שיטות משלוח" );
MultiSite::UpdateFromRemote( "wp_woocommerce_shipping_zone_methods", "instance_id" );

print gui_header( 1, "מסנכרן איזורי משלוח" );
MultiSite::UpdateFromRemote( "wp_woocommerce_shipping_zones", "zone_id" );

print gui_header( 1, "מסנכרן משימות" );
MultiSite::UpdateFromRemote( "im_missions", "id" );

print gui_header( 1, "מסנרכן שיטות משלוח" );
MultiSite::UpdateFromRemote( "wp_options", "option_name", 0, "option_name like 'woocommerce_flat_rate_%_settings'", array( 'option_id' ) );
