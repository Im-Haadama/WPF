<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 12:46
 */

// Run from crontab.

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
print $m->Run( "auto/daily-master.php", $master );

// Now sync to slaves.
print $m->GetAll( "multi-site/sync-from-master.php" );
print $m->GetAll( "auto/daily-all.php" );