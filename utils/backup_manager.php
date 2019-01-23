<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/02/18
 * Time: 19:16
 */

require_once( gethostname() . '.php' );

if ( ! isset( $_GET["tabula"] ) ) {
	die ( 1 );
}

if ( ! isset( $_GET["tabula"] ) ) {
	die ( 1 );
}

$tabula = $_GET["tabula"];
if ( $tabula != "145db255-79ea-4e9c-a51d-318a86c999bf" ) {
	die( 2 );
}

if ( ! isset( $_GET["op"] ) ) {
	die ( 2 );
}

$op = $_GET["op"];

// print $folder;

$content = scandir( $backup_dir );

if ( count( $content ) > ( $backup_count + 3 ) ) // Files . .. tmp
{
	$file_name = $content[3];
	$file      = $backup_dir . '/' . $file_name;
	switch ( $op ) {
		case "name":
			print $file_name;
			exit( 0 );
		case "file":
			header( "Content-Disposition: attachment; filename=" . basename( $file ) . '"' );
			header( "Content-Length: " . filesize( $file ) );
			header( "Content-Type: application/octet-stream;" );
			print readfile_chunked( $file );
			exit( 0 );
		case "delete":
			unlink( $file );
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