<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/01/17
 * Time: 18:42
 *
 * return rows of data from sql statement.
 * DATA
 *=-=-=
 * Table Header - collect column name using describe or fetch_field
 * GetArg - get option from array.
 * RowData - get $row from sql result and create array. Handle links and entry.
 * TableData - get all rows.
 *
 */

require_once( "inputs.php" );

require_once( ROOT_DIR . "/niver/data/sql.php" );
require_once(ROOT_DIR . "/niver/data/translate.php");

/**
 * Table header gets a sql query and returns array to be used as header, usually in html table.
 * @param $sql
 * @param bool $add_checkbox
 * @param bool $skip_id
 * @param null $meta_fields
 *
 * @return array
 */
function TableHeader($sql, $add_checkbox = false, $skip_id = false, $meta_fields = null)
{
	$result = sql_query( $sql );
	// We need only the header. Remove query and replace with false.
	if (strstr($sql, "where"))
		$sql = substr($sql, 0, strpos($sql, "where")) . " where 1 = 0";
	if (! $result)
		return null;

	$headers = array();
	$debug = false;

	if (strstr($sql, "describe"))
	{
		while ($row = sql_fetch_row($result))
		{
			if (! $skip_id or strtolower($row[0]) !== "id") {
				array_push($headers, im_translate($row[0]));
			} else {
				if ($debug) print "skip header";
			}
		}
	} else { // Select
		$i      = 0;
		$fields = mysqli_fetch_fields( $result );
		if ( $add_checkbox ) {
			array_push( $headers, "" );
		} // future option: gui_checkbox("chk_all", ""));
		foreach ( $fields as $val ) {
			if (! $skip_id or strtolower($val->name) !== "id") {
				array_push( $headers, im_translate($val->name) );
			}
			$i ++;
		}
	}
	if ($meta_fields and is_array($meta_fields))
	{
		foreach ($meta_fields as $f)
			array_push($headers, $f);
	}

	return $headers;
}

/**
 * PrepareRow - adds links, selectors and edit inputs.
 *
 * @param $row
 * @param $args
 *
 * @param $row_id
 *
 * @return array
 */
function PrepareRow($row, $args, $row_id)
{
	$skip_id = GetArg($args, "skip_id", false);
	$links = GetArg($args, "links", null);
	$edit = GetArg($args, "edit", false);
	$selectors = GetArg($args, "selectors", null);
	$actions = GetArg($args, "actions",null);
	$edit_cols = GetArg($args, "edit_cols", null);
	$transpose = GetArg($args, "transpose", null);
	$debug = 0;

	$events = GetArg($args, "events", null); // $edit ? "onchange='changed_field(" . $row_id . ")'" : null); // Valid for grid. In transposed single row it will be replaced.
	// print "e=" . $events;
	$table_name = GetArg($args, "table_name", null);

	$row_data = array();

	if ($debug and ! $table_name) print "no table name<br/>";

	if ($debug){
		print "start " . __FUNCTION__ . "<br/>";
	}

	if (! is_array($row))
	{
		print __FUNCTION__ . "invalid row ";
		var_dump($row);
		return $row;
	}
	foreach ( $row as $key => $data )
	{
		$input_name = substr($key, 0, 3) . '_' . $row_id;
		$orig_data = $data;
		$value = $data; // Default;
		if ($debug) print  "<br/>handling $key ";
		if (strtolower($key) == "id" ) {
			if ($skip_id) {
				if ($debug) print "skip";
				continue;
			}
		}
		if ($edit and (! $edit_cols or $edit_cols[$key])) {
			if ($transpose){
				$field_events = sprintf($events, "'" . $key . "'", $row_id);
//				 print "EE=" . $field_events . "<br/>";
			}
			else
				$field_events = sprintf( $events, $row_id, $key );
			$args["events"] = $field_events;
			if ( $selectors and array_key_exists( $key, $selectors ) ) {
				if ($debug) print "has selector ";
				$selector_name = $selectors[ $key ];
				if ( strlen( $selector_name ) < 2 ) {
					die( "selector " . $key . "is empty" );
				}
				$value = $selector_name( $input_name, $orig_data, $args); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
			} else {
//				if ($key == $id_col){
//					if ($debug) print "in id. $row_id";
//					$value = $row_id;
//				} else
				if ( $table_name ) {
					$type = sql_type( $table_name, $key );
					switch ( substr( $type, 0, 3 ) ) {
						case 'dat':
							$value = gui_input_date( $input_name, null, $data, $field_events );
							break;
						case 'var':
							$length = 10;
							$r      = array();
							if ( preg_match_all( '/\(([0-9]*)\)/', $type, $r ) ) {
								$length = $r[1][0];
							}
							if ( $length > 100 ) {
								$value = gui_textarea( $input_name, $data, $field_events );
							} else {
								$value = GuiInput($input_name, $data, $args); // gui_input( $input_name, $data, $field_events, $row_id );
							}
							break;
						default:
							// $field_events = sprintf( $events, $row_id, $key );
							$value        = GuiInput($input_name, $data, $args); //gui_input( $input_name, $data, $field_events, $row_id );
							break;
					}
				} else {
					if ( $debug ) {
						var_dump( $data );
					}
					$value = GuiInput($input_name, $data, $args); //gui_input( $key, $data, $field_events, $row_id);
				}
			}
		} else {
			if ( $selectors and array_key_exists( $key, $selectors ) ) {
				if ($debug) print "has selectors ";
				$selector_name = $selectors[ $key ];
				if ( strlen( $selector_name ) < 2 ) {
					die( "selector " . $key . "is empty" );
				}
				//////////////////////////////////
				// Selector ($id, $value, $args //
				//////////////////////////////////
				$value = $selector_name( $key, $orig_data, $args); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
			}
			if ( $links and array_key_exists( $key, $links ) ) {
				if ($debug) print "links ";
				$value = gui_hyperlink( $value, sprintf( $links[ $key ], $data ) );
			}
		}
		$row_data[$key] = $value;
		//array_push( $row_data, $value );
//		$first_row = false;
	}

	if ($actions){
		foreach ($actions as $action) {
			if (is_array($action))
			{
				$text = $action[0];
				$action_url = sprintf($action[1], $row_id);
				array_push($row_data, gui_hyperlink($text, $action_url));
			} else {
				$h = sprintf($action, $row_id);
				array_push($row, $h);
			}
		}
	}

	return $row_data;
}

/**
 *
 * @param $sql
 * @param null $args
 *
 * @return array|string
 */
function TableData($sql, &$args = null)
{
	$result = sql_query( $sql );
	if ( ! $result ) {
		return null;
	}

	$rows_data = array();

	$header = GetArg($args, "header", true);
	$id_field = GetArg($args, "id_field", "id");
	$skip_id = GetArg($args, "skip_id", false);
	$meta_fields = GetArg($args, "meta_fields", null);
	$meta_table = GetArg($args, "meta_table", null);
	$meta_key_field = GetArg($args, "meta_key", "id");
	$values = GetArg($args, "values", null);
	$v_checkbox = GetArg($args, "v_checkbox", null);
	$sum_fields = &GetArg($args, "sum_fields", null);
	$checkbox_class = GetArg($args, "checkbox_class", "checkbox");

	$table_names = array();
	if (preg_match_all("/from ([^ ]*)/" , $sql, $table_names))
	{
		$args["table_name"] = $table_names[1][0];
	}

	$header_line = $header ? TableHeader($sql, false, $skip_id, $meta_fields) : null;

	$row_count = 0;

	$v_line = $v_checkbox ? array() : null;

	if (strstr($sql, "describe")) // New Row
	{
		$new_row = array();
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$key = $row["Field"];
			if ($values and isset($values[$key])) {
				$new_row[$key] = $values[$key];
			} else {
				$new_row[$key] = null;
			}
			if ($v_line !== null) {
				if (! $skip_id or ($key != $id_field))
					$v_line[$key] = gui_checkbox("chk_" . $key, $checkbox_class, $new_row[$key] != null);
			}
		}

		if ($v_line){
			array_push($rows_data, $v_line);
		}
		if ($header_line) array_push ($rows_data, $header_line);
		array_push($rows_data, $new_row);
		return $rows_data;
	} else {
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$the_row = array();
			$row_id  = $row[ $id_field ];
			if ( ! $row_id ) {
				// Error... We don't have a valid row ID.
				print "<br/>field id:" . $id_field . "<br/>";
				var_dump( $row );
				print "<br/>";
				die( __FUNCTION__ . ":" . __LINE__ . "no row id" );
			}
			foreach ( $row as $key => $cell ) {
//				if ( ! $skip_id or strtolower( $key ) !== "id" ) {
					$the_row[$key] = $cell;
					// array_push( $the_row, $cell );
//				}
				if ($v_checkbox){
					if (! $skip_id or ($key != $id_field))
						$v_line[$key] = gui_checkbox("chk_" . $key, $checkbox_class, false);
				}
			}

			$row_count ++;

			if ($meta_fields and is_array($meta_fields))
			{
				foreach ($meta_fields as $meta_key){
					$meta_value = sql_query_single_scalar("select meta_value from " . $meta_table . " where " . $meta_key_field . " = $row_id " .
					" and meta_key = " . quote_text($meta_key));

					$key = $meta_table . '/'. $meta_key;
					$the_row[$key] = $meta_value;
				}
			}
			if ($sum_fields) {
				HandleSum($sum_fields, $row);
			}

			if ($v_line){
				array_push($rows_data, $v_line);
				$v_checkbox = false;
				$v_line = null;
			}

			if ($header_line) {
				array_push($rows_data, $header_line);
				$header_line = null;
			}

			array_push($rows_data, $the_row);
		}
		if ($sum_fields) {
			$total_line = array();
			foreach ($sum_fields as $cell)
				array_push($total_line, is_array($cell) ? $cell[0] : $cell);
			array_push($rows_data, $total_line);
		}
		return $rows_data;
	}
}

function HandleSum(&$sum_fields, $row)
{
	foreach ($row as $key => $cell)
	{
		if (is_array($sum_fields[$key]) and function_exists($sum_fields[$key][1])) {
//			 print "summing " . $sum_fields[$key][0][2] . "<br/>";
			$sum_fields[$key][1]($sum_fields[$key][0], $cell);
		} else {
//			print "not summing " . is_array($sum_fields[$key]) . " " . function_exists($sum_fields[$key][1]) . "<br/>";
		}
	}
}

/**
 * @deprecated
 */

function table_content_data(
	$sql, $header = true, $footer = true, $links = null,
	$add_checkbox = false, $checkbox_class = null, $chkbox_events = null, $selectors = null
) {

	$result = sql_query( $sql );
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

			$args = array("links" => $links, "selectors" => $selectors, "add_checkbokx" => $add_checkbox,
				"checkbox_class" => $checkbox_class, "checkbox_events" => $chkbox_events);

		array_push( $rows_data, PrepareRow($row, $args, $row["id"]) );
	}

	return $rows_data;
}



/**
 * @deprecated 1.8.0 Use Table instead.
 */

function table_content(
	$table_id, $sql, $header = true, $footer = true, $links = null, &$sum_fields = null,
	$add_checkbox = false, $checkbox_class = null, $chkbox_events = null, $selectors = null, $actions = null,
	$class = null
) {

	// Fetch the data from DB.
	$rows_data = table_content_data( $sql, $header, $footer, $links,
		$add_checkbox, $checkbox_class, $chkbox_events, $selectors );

	$row_count = count( $rows_data);

	$args = array();
	if ($links)	$args["links"] = $links;
	if ($actions) $args["actions"] = $actions;

	// Convert to table if data returned.
	if ( $row_count >= 1 ) {
		return gui_table_args( $rows_data, $table_id, $args );
	}

	return null;

}


/**
 * @param $table_name
 * @param $args
 */
function NewRow($table_name, $args)
{
	$args["edit"] = true;
	$args["table_name"] = $table_name;
	$args['events'] = 'onchange="changed_field(\'%s\')"';
	return GuiRowContent($table_name, null, $args);
}

/**
 * Get recorder from the database and display in html table.
 * @param $table_name
 * @param $row_id
 * @param $args
 *
 * @return string|null
 */
function GuiRowContent($table_name, $row_id, $args)
{
	$args['first_row_to_prepare'] = 2;

	$id_key = GetArg($args, "id_key", "id");
	if (! isset($args["skip_id"])){
		$args["skip_id"] = true;
	}
	$edit = GetArg($args, "edit", false);
	if ($edit) {
		$args["v_checkbox"] = 1;
		$args["transpose"] = 1;
		$args["events"] = 'onchange="changed_field(%s)"';
	}
	if ($row_id) {
		$sql = "select * from $table_name where " . $id_key . " = " . $row_id;
	} else {
		$sql = "describe $table_name";
	}

	return GuiTableContent($table_name, $sql, $args);
}

/**
 * Execute SQL. If data return, return html table with the data. Otherwise return null.
 * @param $table_id
 * @param $sql
 * @param $args
 * @param null $sum_links
 *
 * @return string|null
 */

function GuiTableContent($table_id, $sql, &$args)
{
	// Fetch the data from DB.
	$rows_data = TableData( $sql, $args);

	if (! $rows_data)
		return null;

	$row_count = count( $rows_data);

	// Convert to table if data returned.
	if ( $row_count >= 1 ) {
		$html = gui_table_args( $rows_data, $table_id, $args );
		return $html;
	}

	return null;
}
