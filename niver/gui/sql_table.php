<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/01/17
 * Time: 18:42
 */

require_once( "inputs.php" );

require_once( ROOT_DIR . "/niver/data/sql.php" );

function table_content_data_args($sql, $args) {
	global $conn;

	$header = true;
	$links = null;
	$add_checkbox = false;
	$checkbox_class = null;
	$chkbox_events = null;
	$selectors = null;
	$selectors_events = null;

	if (isset($args["selectors"]))
		$selectors = $args["selectors"];

	if (isset($args["selectors_events"])){
		$selectors_events = $args["selectors_events"];
	}

	// var_dump($selectors);

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
	while ( $row = mysqli_fetch_assoc( $result ) ) {
		$i        = 0;
		$row_data = array();
		foreach ( $row as $key => $data ) {
			if ( $key == "id" ) {
				$row_id = $data;
			}
//			 print "key= " . $key . "<br/>";
			if ( $links and array_key_exists( $key, $links ) ) {
				// print "lk=" . $links[$key];
				$value = gui_hyperlink( $data, sprintf( $links[ $key ], $data ) );
				// print $value . "<br/>";
			} else {
//				print $key . " " . $selectors[$key] . "<br/>";
				if ( $selectors and array_key_exists( $key, $selectors ) ) {
					$events = "";
					if ($selectors_events and isset($selectors_events[$key])) {
						$events = sprintf($selectors_events[ $key ], $row_id);
					}
					$selector_name = $selectors[ $key ];
					if ( strlen( $selector_name ) < 2 ) {
						die( "selector " . $key . "is empty" );
					}
//					print "sn=" . $selector_name . "<br/>";
					$value = $selector_name( $key . "_" . $row_id, $data, $events); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
					//$value = "xx";

				} else {
					$value = $data;
				}
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

	return $rows_data;
}

function TableHeader($sql, $add_checkbox = false)
{
	$result = sql_query( $sql );

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

	return $headers;
}

function table_content_data(
	$sql, $header = true, $footer = true, $links = null,
	$add_checkbox = false, $checkbox_class = null, $chkbox_events = null, $selectors = null
) {
	global $conn;

	// var_dump($selectors);

	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		return "error: " . $sql . sql_error( $sql );
	}

	$rows_data = array();

	if ( $header ) {
		array_push( $rows_data, TableHeader($sql) );
	}
	$row_count = 0;
	while ( $row = mysqli_fetch_assoc( $result ) ) {
			// print $key;
			$row_count ++;

		array_push( $rows_data, RowData($row, $links, $selectors, $add_checkbox, $checkbox_class, $chkbox_events) );
	}

	return $rows_data;
}

function RowData($row, $links = null, $selectors = null, $add_checkbox = false, $checkbox_class=false, $chkbox_events=null, $edit = false) {
	$row_data = array();
	$row_id   = null;

	foreach ( $row as $key => $data ) {
		if ( $key == "id" ) {
			$row_id = $data;
		}
//			 print "key= " . $key . "<br/>";
		if ( $links and array_key_exists( $key, $links ) ) {
			// print "lk=" . $links[$key];
			$value = gui_hyperlink( $data, sprintf( $links[ $key ], $data ) );
			// print $value . "<br/>";
		} else {
//				print $key . " " . $selectors[$key] . "<br/>";
			if ( $selectors and array_key_exists( $key, $selectors ) ) {
				$selector_name = $selectors[ $key ];
				if ( strlen( $selector_name ) < 2 ) {
					die( "selector " . $key . "is empty" );
				}
//					print "sn=" . $selector_name . "<br/>";
				$value = $selector_name( "id", $data ); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
				//$value = "xx";

			} else {
				$value = $data;
			}
		}
		array_push( $row_data, $value );
	}
	if ( $add_checkbox ) {
		array_unshift( $row_data, gui_checkbox( "chk" . $row_id, $checkbox_class, false, $chkbox_events ) );
	}

	return $row_data;
}

function GetArg($args, $key, $default)
{
	if (! isset($args[$key])) return $default;
	return $args[$key];
}

function TableContent($table_id, $sql, $args, $sum_links)
{
	$header = GetArg($args, "header", true);
	$footer = GetArg($args, "footer", true);
	$links = GetArg($args, "links", null);
	$class = GetArg($args, "class", "");
	// print "c=" . $class . "<br/>";
	$add_checkbox = GetArg($args, "add_checkbox", false);
	$checkbox_class = GetArg($args, "checbkox_class", "");
	$checkbox_events = GetArg($args, "checkbox_events", "");
	$selectors = GetArg($args, "selectors", null);
	$actions = GetArg($args, "actions", null);

	return table_content ($table_id, $sql, $header, $footer, $links, $sum_links,
		$add_checkbox, $checkbox_class, $checkbox_events, $selectors, $actions, $class);
}

function table_content(
	$table_id, $sql, $header = true, $footer = true, $links = null, &$sum_fields = null,
	$add_checkbox = false, $checkbox_class = null, $chkbox_events = null, $selectors = null, $actions = null,
	$class = null
) {
	$rows_data = table_content_data( $sql, $header, $footer, $links,
		$add_checkbox, $checkbox_class, $chkbox_events, $selectors );

	$row_count = count( $rows_data);

	if ( $row_count >= 1 ) {
		return gui_table( $rows_data, $table_id, $header, $footer, $sum_fields, null, $class, null,
			null, null, false, $actions );

	}

	return null;

}

function table_content_args($table_id, $sql, $args)
{
	$rows_data = table_content_data_args( $sql, $args );

	if ( count( $rows_data) >= 1 ) {
		return gui_table_args( $rows_data, $table_id, $args );
	}

	return null;

}

function RowContent($table, $row_id, $args = null, $transpose = false)
{
	// $sql = "select id, task_description, task_url, repeat_freq_numbers, project_id, repeat_freq, condition_query, priority, working_hours from $table where id = $row_id";
	$sql = "select * from $table where id = $row_id";

	$header = TableHeader($sql);
	// var_dump($header);
	$result = sql_query($sql);
	if ($result){
		$row = sql_fetch_assoc($result);
		$links = GetArg($args, "links", null);
		$selectors = GetArg($args, "selectors", null);
		$add_checkbox = GetArg($args, "add_checkbox", false);
		$checkbox_class = GetArg($args, "checkbox_clasS", false);
		$chkbox_events = GetArg($args, "checkbox_events", null);
		$edit = GetArg($args,"edit", false);

		$data = RowData($row, $links, $selectors, $add_checkbox, $checkbox_class, $chkbox_events, $edit);

		//		 var_dump($data);
		$table = array($header, $data);

		if ($transpose)
			$table = array_map(null, ...$table);

		return gui_table($table);
	}
	return "no data";
}
