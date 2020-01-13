<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/04/18
 * Time: 15:55
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

init();

require_once( FRESH_INCLUDES . '/fresh/multi-site/sync.php' );

$debug = GetParam("debug", false, false);
sync_from_master($debug);
