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

$backup_dir = IM_BACKUP_FOLDER;

// require_once( 'backup_config.php' );

// print "dir= " . $backup_dir . "<br/>";

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

$op = $_GET["op"];
$backup_count = 3;

// print $folder;
if (! file_exists($backup_dir)){
	die ("backup directory not found");
}
$content = scandir( $backup_dir, SCANDIR_SORT_DESCENDING );

if ( count( $content ) > 2 /* ( $backup_count + 3 )*/ ) // Files . .. tmp
{
//	$file_name = $content[0];
//	$file      = $backup_dir . '/' . $file_name;
	switch ( $op ) {
		case "name":
			foreach ($content as $c)
			{
				if (substr($c, 0, 1) != ".") {
					print $c;
					return;
				}
			}
			exit( 0 );
		case "file":
			header( "Content-Disposition: attachment; filename=" . basename( $file ) . '"' );
			header( "Content-Length: " . filesize( $file ) );
			header( "Content-Type: application/octet-stream;" );
			print readfile_chunked( $file );
			exit( 0 );
		case "delete":
			if ( count( $content ) > $backup_count ) {
				unlink( $content[ count( $content ) - 1 ] );
			}
			exit( 0 );
	}
	// print $file_name;
}

function readfile_chunked( $filename, $retbytes = true ) {
	$chunksize = 1 * ( 1024 * 1024 ); // how many bytes per chunk
	$cnt       = 0;
	// $handle = fopen($filename, 'rb');
	$handle = fopen( $filename, 'rb' );
	if ( $handle === false ) {
		return false;
	}
	while ( ! feof( $handle ) ) {
		$buffer = fread( $handle, $chunksize );
		echo $buffer;
		if ( $retbytes ) {
			$cnt += strlen( $buffer );
		}
	}
	$status = fclose( $handle );
	if ( $retbytes && $status ) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}

	return $status;

}