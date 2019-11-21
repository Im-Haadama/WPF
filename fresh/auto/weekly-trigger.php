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
require_once(ROOT_DIR . "/niver/gui/text_inputs.php");

if ( ! isset( $hosts_to_sync ) ) {
	die ( "define hosts_to_sync in config file" );
}

$m = new MultiSite( $hosts_to_sync, $master, 3 );

// Run daily on master.
print gui_header(1, "Running on master") . gui_br();
print $m->Run( "fresh/auto/weekly-master.php", $master );

// Now sync to slaves.
print gui_header(1, "Syncing from master") . gui_br();
print $m->GetAll( "fresh/multi-site/sync-from-master.php" );

// print $m->GetAll( "auto/weekly.php" );
