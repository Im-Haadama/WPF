<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/10/17
 * Time: 15:56
 */

require_once( ROOT_DIR . '/agla/sql.php' );

function info_get( $key ) {
	$sql = "SELECT info_data FROM im_info WHERE info_key = '" . $key . "'";

//	print $sql . "<br/>";
	return sql_query_single_scalar( $sql );
}

function info_update( $key, $data ) {
	$sql = "UPDATE im_info SET info_data = '" . $data . "' WHERE info_key = '" . $key . "'";
//		print $sql;
	sql_query( $sql );
}
