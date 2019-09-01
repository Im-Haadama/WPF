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
$date = get_param("date", false, date('y-m-j'));

if ( $op == 'check' ) { // would run on conductor server
	$fail = false;
	// Check if we have new files from backuped servers.
	// print "0 valid backups"; // No attention needed. All ok.

	$results = array( array( "hostname", "result" ) );

	foreach ( $hosts_to_sync as $key => $host_info ) {
		$output = "";
		$url    = $host_info[2] . "/../utils/backup_manager.php?tabula=145db255-79ea-4e9c-a51d-318a86c999bf";
		$get_name = $url . "&op=name&date=" . $date;
		$file_name = curl_get( $get_name );
		if ( strstr( $file_name, "Fatal" ) or strlen($file_name) < 5) {
			array_push( $results, array( $host_info[1], "error file name: " . $file_name ) );
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
			$output .= "missing file $full_path";
			array_push( $results, array( $host_info[1], $file_name, $output ) );
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

	 print gui_table_args( $results );

//	if ($fail) print im_file_get_html("http://tabula.aglamaz.com/utils/daily.log." . date('d') . ".html");

//	print "going to die";
	die(0);
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

	$file_name = curl_get( $url . "&op=name&date=" . date('Y-m-d') );
	if ( strstr( $file_name, "Fatal" ) or strlen($file_name) < 2) {
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
	if (! is_writable (IM_BACKUP_FOLDER))
	{
		array_push($results, "Folder " . IM_BACKUP_FOLDER . "   is not writable. run from shell");
		continue;

	}
	$file   = fopen( $full_path, "w" );
	if (! $file)
	{
		array_push($results, "Can't open file. check disk usage and permission.");
		continue;
	}
	$backup = curl_get( $url . "&op=file&name=" . $file_name);
	fwrite( $file, $backup );
	fclose( $file );
	$output .= "done<br/>";

	$size   = filesize( $full_path );
	$output .= "result size: " . $size . "<br/>";

	if ( $size > 500000 ) {
		$output .= "delete in origin<br/>";
		curl_get( $url . "&op=delete" );
	}
	array_push( $results, array( $host_info[1], $output ) );
}

print gui_table_args( $results );
