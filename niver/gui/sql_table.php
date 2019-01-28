<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/01/17
 * Time: 18:42
 */

require_once( "inputs.php" );

require_once( ROOT_DIR . "/niver/data/sql.php" );

function table_content( $sql, $header = true, $footer = true, $links = null, &$sum_fields = null ) {
	global $conn;

	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		return "error: " . $sql . sql_error( $sql );
	}

	$rows_data = array();

	if ( $header ) {
		$i      = 0;
		$fields = mysqli_fetch_fields( $result );
		// var_dump($fields);
		// var_dump($header);
		$headers = array();
		foreach ( $fields as $val ) {
			// print $val->name . "<br/>";
			array_push( $headers, $val->name );
			$i ++;
		}
		array_push( $rows_data, $headers );
	}
	$row_count = 0;
	while ( $row = mysqli_fetch_row( $result ) ) {
		$i        = 0;
		$row_data = array();
		foreach ( $row as $key => $data ) {
			// print $key . "<br/>";
			if ( $links and array_key_exists( $key, $links ) ) {
				$value = gui_hyperlink( $data, sprintf( $links[ $i ], $data ) );
			} else {
				$value = $data;
			}

			// print $key;
			array_push( $row_data, $value );
			$row_count ++;
			$i ++;
		}

		array_push( $rows_data, $row_data );
	}

	if ( $row_count >= 1 ) {
		return gui_table( $rows_data, null, $header, $footer, $sum_fields );
	}

	return null;

}