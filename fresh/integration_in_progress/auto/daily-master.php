<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/19
 * Time: 19:05
 */

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) );
}

//require_once( '../r-shop_manager.php' );
//require_once( '../multi-site/imMulti-site.php' );
//require_once( '../orders/orders-common.php' );
//require_once( '../supplies/Supply.php' );
//require_once( '../pricelist/pricelist.php' );
//require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
require_once( "../delivery/missions.php" );
//require_once( "start.php" );

print header_text();

print "done";