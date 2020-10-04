<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/10/17
 * Time: 15:56
 */

class Core_Options {

	static function info_remove( $key ) {
		SqlQuery( "delete from im_info where info_key ='$key'" );
	}

	static function info_get( $key, $create = false, $default = null ) {
		$sql = "SELECT info_data FROM im_info WHERE info_key = '" . $key . "'";

		$result = SqlQuerySingleScalar( $sql );

		if ( is_null( $result ) ) {
			if ( $create ) {
				info_update( $key, $default );

				return $default;
			}
		}

		return $result;
	}
}