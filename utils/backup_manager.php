<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/02/18
 * Time: 19:16
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . '/im-config.php' );
require_once( ROOT_DIR . '/niver/fund.php' );
require_once( ROOT_DIR . '/niver/system/backup.php' );

$backup_dir = IM_BACKUP_FOLDER;

if ( ! isset( $_GET["tabula"] ) ) {
	die ( "key" );
}

$tabula = $_GET["tabula"];
if ( $tabula != "145db255-79ea-4e9c-a51d-318a86c999bf" ) {
	die( "wrong key" );
}

if ( ! isset( $_GET["op"] ) ) {
	die ( 2 );
}

$debug = get_param("debug", false, false);

$op = $_GET["op"];
if ($debug) print "op=$op<br/>";
$op or die ("no operation given");

$backup_count = 3;

// print $folder;
if (! file_exists($backup_dir)){
	die ("backup directory not found");
}

	switch ( $op ) {
		case "name":
			$date = get_param("date", true);
			$name = DB_NAME . '-' . $date . '.sql.gz';
			if (file_exists($backup_dir . '/' . $name)) { print $name; return; }
			print "not found - $name";
			exit( 0 );
		case "file":
			$file_name = get_param("name", true);
			$file_path = $backup_dir . '/' . $file_name;
			if (! file_exists($file_path))
			{
				print "Error: file $file_name not found";
				exit(1);
			}
			header( "Content-Disposition: attachment; filename=" . basename( $file_path ) . '"' );
			header( "Content-Length: " . filesize( $file_path ) );
			header( "Content-Type: application/octet-stream;" );
			print readfile_chunked( $file_path );
			exit( 0 );
		case "delete":
			delete_backup($backup_dir);
			exit( 0 );
	}
	// print $file_name;

