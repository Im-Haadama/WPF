<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/10/17
 * Time: 15:56
 */

class Core_Options {

static function info_remove( $key ) {
	sql_query( "delete from im_info where info_key ='$key'" );
}

	static function info_get( $key, $create = false, $default = null ) {
	$sql = "SELECT info_data FROM im_info WHERE info_key = '" . $key . "'";

//	print $sql ."<br/>";

	$result = sql_query_single_scalar( $sql );

	if ( is_null( $result ) ) {
		if ( $create ) {
			info_update( $key, $default );

			return $default;
		}
	}

	return $result;
}

	static function info_update( $key, $data ) {
	$sql = "SELECT info_data FROM im_info WHERE info_key = '" . $key . "'";

	$result = sql_query_single_scalar( $sql );
	if ( ! $result ) {
		$sql = "insert into im_info (info_key, info_data) VALUE ('$key', '$data')";

		return sql_query( $sql );
	}
	$sql = "UPDATE im_info SET info_data = '" . $data . "' WHERE info_key = '" . $key . "'";

	return sql_query( $sql );
}

}