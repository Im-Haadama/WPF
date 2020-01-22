<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 12:46
 */

// Run from crontab.
$html = false;

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( "ROOT_DIR", dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/niver/MultiSite.php" );
require_once( ROOT_DIR . "/utils/config.php" );
require_once( ROOT_DIR . "/niver/system/backup.php" );

if (0)
	require_once( ROOT_DIR . "/niver/gui/inputs.php" );
else
	require_once( ROOT_DIR . "/niver/gui/text_inputs.php" );

print header_text( false, true, false );

if ( ! isset( $hosts_to_sync ) ) {
	die ( "define hosts_to_sync in config file" );
}

if ( ! defined( 'IM_BACKUP_FOLDER' ) ) {
	die ( "define IM_BACKUP_FOLDER" );
}

$m = new Core_MultiSite( $hosts_to_sync, $master, 3 );

$op = GetParam( "op" );
// $backup_run_time = shell_exec(`crontab -l | grep backup.sh | cut -f 2 -d' '`);
$backup_run_time = 12;

$d =  (date("H") > $backup_run_time) ? date('y-m-d') : date('y-m-d',strtotime("-1 days"));
$date = GetParam("date", false, $d);
$debug = GetParam("debug", false, false);

if ( $op == 'check' ) { // would run on conductor server
}

print gui_header( 1, "Running daily on master" );

// Run daily on master.
print $m->Run( "auto/daily-master.php", $master );

print gui_header( 1, "Syncing to slaves" );

// Now sync to slaves.
print $m->GetAll( "multi-site/sync-from-master.php", true );

print gui_header( 1, "Running daily-all" );
print $m->GetAll( "auto/daily-all.php", true, false, true );

print gui_header( 2, "Handling backup collection!" );
print "backup folder: " . IM_BACKUP_FOLDER . PHP_EOL;

$results = array( array( "hostname", "result" ) );

foreach ( $hosts_to_sync as $key => $host_info ) {
	$output = "";
		$url    = $host_info[2] . "/utils/backup_manager.php?tabula=145db255-79ea-4e9c-a51d-318a86c999bf";

	$file_name = CurlGet( $url . "&op=name&date=" . date('Y-m-d') );
	if ( strstr( $file_name, "not found" ) or strlen($file_name) < 2) {
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
	$backup = CurlGet( $url . "&op=file&name=" . $file_name);
	fwrite( $file, $backup );
	fclose( $file );
	$output .= "done<br/>";

	$size   = filesize( $full_path );
	$output .= "result size: " . $size . "<br/>";

	if ( $size > 500000 ) {
		$output .= "delete in origin<br/>";
		CurlGet( $url . "&op=delete" );
	}
	array_push( $results, array( "hostname" => $host_info[1], "result" => $output ) );
}

print gui_table_args( $results );

delete_backup(IM_BACKUP_FOLDER);