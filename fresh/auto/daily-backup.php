<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/19
 * Time: 20:55
 */

require_once ("../../im-config.php");
require_once( ROOT_DIR . "/fresh/im_tools.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( ROOT_DIR . "/fresh/supplies/Supply.php" );
require_once( ROOT_DIR . "/fresh/multi-site/imMulti-site.php" );

$m = ImMultiSite::getInstance();

//$text = show_category_all( false, true, false, false);
//
//$rc = send_mail( "da." . date('h:mm'), 'info@im-haadama.co.il', $text );

ob_start();

backup_database();

print "done";

$buffer = ob_get_contents();
ob_end_clean();

print $buffer;
$log_file = ROOT_DIR . "/logs/run-" . date( 'd' ) . ".html";
$log      = fopen( $log_file, "w" );
fwrite( $log, $buffer );
fclose( $log );
return;


function backup_database() {
	if ( ! defined( 'IM_BACKUP_FOLDER' ) ) {
		print "define IM_BACKUP_FOLDER";
		die( 1 );
	}

	if ( ! defined ( 'DB_NAME' )  ) {
		print "define DB_NAME";
		die( 2 );
	}

	$folder  = IM_BACKUP_FOLDER;
	$success = '-- Dump completed on ' . date( 'Y-m-d' );

	// print "folder: " . $folder . "<br/>";

	if ( ! file_exists( $folder ) ) {
		print "creating folder... ";
		if ( ! mkdir( $folder, 0777, true ) ) {
			print "create folder " . $folder . "<br/>";
			die ( 1 );
		}
		print "<br/>";
	}
	print "last date: " . info_get( "backup_date" ) . "<br/>";
	print "last result: " . info_get( "backup_result" ) . "<br/>";

	if ( info_get( "backup_date" ) == date( 'z' ) &&
	     info_get( "backup_result" ) == $success
	) {
		print "has successful backup<br/>";

		return;
	}

	print "running backup<br/>";

	$param_file = $folder . "/." . DB_NAME;
	if ( ! file_exists( $param_file ) ) {
		$file = fopen( $param_file, "w" );
		if ( ! $file ) {
			die( "can't write" );
		}

		fwrite( $file, "[mysqldump]\n" );
		fwrite( $file, "user=" . IM_DB_USER . "\n" );
		fwrite( $file, "password=" . IM_DB_PASSWORD . "\n" );
//		fwrite( $file, "single-transaction\n" );

		fclose( $file );
	}

	$backup_file = $folder . "/" . DB_NAME . '-' . date( 'Y-m-d' ) . ".sql";

	print "backup file: " . $backup_file . "<br/>";

	$command = "cd " . $folder . " &&  mysqldump --defaults-extra-file=" . $param_file . "  --single-transaction " . DB_NAME . " > " . $backup_file . " 2> " . $backup_file . ".err";
	print $command . "<br/>";
	exec( $command );

	$result = exec( "tail -1 " . $backup_file );

	exec( "gzip " . $backup_file );

	// Server might gone because of the backup. Reconnect
	// $conn = new mysqli( IM_DB_HOST, DB_NAME, IM_DB_PASSWORD, DB_NAME );

	if ( substr( $result, 0, 31 ) == $success ) {
		print "success<br/>";
		info_update( "backup_date", date( 'z' ) );
//		print "s=" . $success ."<br/>";
		info_update( "backup_result", $success );
	} else {
		print $result;
	}

	// print $result;

	print "done\n";

//	print "folder: " . $folder;
}
