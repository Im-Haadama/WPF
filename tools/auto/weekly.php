<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/19
 * Time: 20:49
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( "ROOT_DIR", dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/niver/MultiSite.php" );
require_once( ROOT_DIR . "/utils/config.php" );

if ( ! isset( $hosts_to_sync ) ) {
	die ( "define hosts_to_sync in config file" );
}

$m = new MultiSite( $hosts_to_sync, $master, 3 );

// Run daily on master.
print $m->Run( "auto/weekly-master.php", $master );

// Now sync to slaves.
print $m->GetAll( "multi-site/sync-from-master.php" );

