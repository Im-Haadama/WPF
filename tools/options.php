<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/10/17
 * Time: 15:56
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . '/agla/sql.php' );

function info_get( $key ) {
	$sql = "SELECT info_data FROM im_info WHERE info_key = '" . $key . "'";

	return sql_query_single_scalar( $sql );
}

function info_update( $key, $data ) {
	if ( ! info_get( $key ) ) {
		sql_query( "insert into im_info (info_key, info_data) VALUE ('$key', '$data')" );

		return;
	}
	$sql = "UPDATE im_info SET info_data = '" . $data . "' WHERE info_key = '" . $key . "'";
//		print $sql;
	sql_query( $sql );
}
