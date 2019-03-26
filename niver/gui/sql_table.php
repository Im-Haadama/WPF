<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/01/17
 * Time: 18:42
 */

require_once( "inputs.php" );

require_once( ROOT_DIR . "/niver/data/sql.php" );

function table_content( $id, $sql, $header = true, $footer = true, $links = null, &$sum_fields = null, $add_checkbox = false, $checkbox_class = null, $chkbox_events = null
) {
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
		if ( $add_checkbox ) {
			array_push( $headers, "" );
		} // future option: gui_checkbox("chk_all", ""));
		foreach ( $fields as $val ) {
			// print $val->name . "<br/>";
			array_push( $headers, $val->name );
			$i ++;
		}
		array_push( $rows_data, $headers );
	}
	$row_count = 0;
	$row_id    = null;
	while ( $row = mysqli_fetch_row( $result ) ) {
		$i        = 0;
		$row_data = array();
		foreach ( $row as $key => $data ) {
			if ( $key == "id" ) {
				$row_id = $data;
			}
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
		if ( $add_checkbox )
			array_unshift( $row_data, gui_checkbox( "chk" . $row_id, $checkbox_class, false, $chkbox_events ) );

		array_push( $rows_data, $row_data );
	}

	if ( $row_count >= 1 ) {
		return gui_table( $rows_data, $table_id, $header, $footer, $sum_fields );
	}

	return null;

}