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

print header_text( false, true, false );

if ( ! isset( $hosts_to_sync ) ) {
	die ( "define hosts_to_sync in config file" );
}

if ( ! defined( 'IM_BACKUP_FOLDER' ) ) {
	die ( "define IM_BACKUP_FOLDER" );
}

$m = new MultiSite( $hosts_to_sync, $master, 3 );

$op = get_param( "op" );

if ( $op == 'check' ) { // would run on conductor server
	$fail = false;
	// Check if we have new files from backuped servers.
	// print "0 valid backups"; // No attention needed. All ok.

	$results = array( array( "hostname", "result" ) );

	foreach ( $hosts_to_sync as $key => $host_info ) {
		$output = "";
		$url    = $host_info[2] . "/../utils/backup_manager.php?tabula=145db255-79ea-4e9c-a51d-318a86c999bf";

		$file_name = curl_get( $url . "&op=name" );
		if ( strstr( $file_name, "Fatal" ) ) {
			array_push( $results, array( $host_info[1], "error: " . $file_name ) );
			$fail = true;
			continue;
		}
//	print "len = " . strlen($file_name) . "<br/>";
//		$output .= "filename = " . $file_name . "<br/>";
//
//		$output .=  "checking size...";

		$full_path = IM_BACKUP_FOLDER . '/' . $file_name;

		if ( ! file_exists( $full_path ) ) {
			$fail   = true;
			$output .= "missing file";
			array_push( $results, array( $host_info[1], $output ) );
			continue;
		}

		if ( time() - filemtime( $full_path ) > 24 * 3600 ) {
			$fail   = true;
			$output .= "old file: " . date( 'Y-m-d', filemtime( $full_path ) );
		} else {
			$output .= date( 'Y-m-d', filemtime( $full_path ) ) . " ";
		}

		if ( filesize( $full_path ) < 20000 ) {
			$fail   = true;
			$output .= "small file: " . filesize( $full_path );
		} else {
			$output .= "size= " . filesize( $full_path );
		}

		array_push( $results, array( $host_info[1], $output ) );
	}

	// Todo: Delete old ones.
	print ( $fail ? "1 failed" : "0 all ok" );

	print gui_table( $results );

	return;
}


print gui_header( 1, "Running daily on master" );

// Run daily on master.
print $m->Run( "auto/daily-master.php", $master );

print gui_header( 1, "Syncing to slaves" );

// Now sync to slaves.
print $m->GetAll( "multi-site/sync-from-master.php", true );

print gui_header( 1, "Running daily-all" );
print $m->GetAll( "auto/daily-all.php", true, false, true );

function curl_get( $url ) {
	$handle = curl_init();
	curl_setopt( $handle, CURLOPT_URL, $url );
	curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );

	$data = curl_exec( $handle );
	curl_close( $handle );

	return $data;
}

print gui_header( 2, "Handling backup collection!" );

$results = array( array( "hostname", "result" ) );

foreach ( $hosts_to_sync as $key => $host_info ) {
	$output = "";
	$url    = $host_info[2] . "/../utils/backup_manager.php?tabula=145db255-79ea-4e9c-a51d-318a86c999bf";

	$file_name = curl_get( $url . "&op=name" );
	if ( strstr( $file_name, "Fatal" ) ) {
		array_push( $results, array( $host_info[1], "error: " . $file_name ) );
		continue;
	}
//	print "len = " . strlen($file_name) . "<br/>";
	$output .= "filename = " . $file_name . "<br/>";
	$output .= "reading...";

	$full_path = IM_BACKUP_FOLDER . '/' . $file_name;

	if ( file_exists( $full_path ) ) {
		array_push( $results, array( $host_info[1], "file exists: " . $file_name . ". not fetching" ) );
		continue;
	}
	$file   = fopen( $full_path, "w" );
	$backup = curl_get( $url . "&op=file" );
	fwrite( $file, $backup );
	fclose( $file );
	$output .= "done<br/>";

	$size   = filesize( $full_path );
	$output .= "result size: " . $size . "<br/>";

	if ( $size > 2000000 ) {
		$output .= "delete it origin<br/>";
		curl_get( $url . "&op=delete" );
	}
	array_push( $results, array( $host_info[1], $output ) );
}

print gui_table( $results );
