<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/02/19
 * Time: 08:09
 */


function import_from_file( $table_name, $import_key, $file_name, $field_bind ) {
	global $conn;
	if ( ! $conn ) {
		die( "not conneted" );
	}

	$file = file( $file_name );

	// Locate the header.
	if ( ! parse_header( $file ) ) {
		die ( "header error" );
	}
}