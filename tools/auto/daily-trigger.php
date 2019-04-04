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
require_once( ROOT_DIR . "/niver/gui/inputs.php" );

if ( ! isset( $hosts_to_sync ) ) {
	die ( "define hosts_to_sync in config file" );
}

if ( ! defined( 'IM_BACKUP_FOLDER' ) ) {
	die ( "define IM_BACKUP_FOLDER" );
}

$m = new MultiSite( $hosts_to_sync, $master, 3 );

print gui_header( 1, "Running daily on master" );

// Run daily on master.
print $m->Run( "auto/daily-master.php", $master );

print gui_header( 1, "Syncing to slaves" );

// Now sync to slaves.
print $m->GetAll( "multi-site/sync-from-master.php", true );
print $m->GetAll( "auto/daily-all.php", true );

function curl_get( $url ) {
	$handle = curl_init();
	curl_setopt( $handle, CURLOPT_URL, $url );
	curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );

	$data = curl_exec( $handle );
	curl_close( $handle );

	return $data;
}

print gui_header( 2, "Handling backup collection" );

foreach ( $hosts_to_sync as $key => $host_info ) {
	print "collecting backup from " . $host_info[1] . "<br/>";

	$url = $host_info[2] . "/../utils/backup_manager.php?tabula=145db255-79ea-4e9c-a51d-318a86c999bf";

	$file_name = curl_get( $url . "&op=name" );
	print "filename = " . $file_name . "<br/>";
	print "reading...";

	$file   = fopen( IM_BACKUP_FOLDER . '/' . $file_name, "w" );
	$backup = curl_get( $url . "&op=file" );
	print "done<br/>";
	fwrite( $file, $backup );
	fclose( $file );

	$size = filesize( $file_name );
	print "result size: " . $size . "<br/>";

	if ( $size > 2000000 ) {
		print "delete it origin<br/>";
		curl_get( $url . "&op=delete" );
	}
}
