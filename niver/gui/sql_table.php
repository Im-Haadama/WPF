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
	$headers = array();
	$debug = false;

	if (strstr($sql, "describe"))
	{
		while ($row = sql_fetch_row($result))
		{
			if (! $skip_id or strtolower($row[0]) !== "id") {
				array_push($headers, $row[0]);
			} else {
				if ($debug) print "skip header";
			}
		}
	} else { // Select
		$i      = 0;
		$fields = mysqli_fetch_fields( $result );
		// var_dump($fields);
		// var_dump($header);
		if ( $add_checkbox ) {
			array_push( $headers, "" );
		} // future option: gui_checkbox("chk_all", ""));
		foreach ( $fields as $val ) {
			// print $val->name . "<br/>";
			if (! $skip_id or strtolower($val->name) !== "id")
				array_push( $headers, $val->name );
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
 * @param $row
 * @param $args
 *
 * @return array
 */
function PrepareRow($row, $args, $row_id)
{
	$checkbox_class = null;

	$skip_id = GetArg($args, "skip_id", false);
	$links = GetArg($args, "links", null);
	$edit = GetArg($args, "edit", false);
	$selectors = GetArg($args, "selectors", null);
	$actions = GetArg($args, "actions",null);
	$add_checkbox = GetArg($args, "add_checkbox", false);
//	var_dump($selectors);

	$events = GetArg($args, "events", $add_checkbox ? 'onchange="changed(this)"' : null);
	$table_name = GetArg($args, "table_name", null);

	$row_data = array();

	$debug = false;
	if ($debug and ! $table_name) print "no table name<br/>";

	if ($debug){
		print "start " . __FUNCTION__ . "<br/>";
	}

//	$first_row = false;
	foreach ( $row as $key => $data ) {
		if ($debug) print  "<br/>handling $key ";
		if (strtolower($key) == "id" ) {
//			$row_id = $data;
			if ($skip_id) {
				if ($debug) print "skip";
				continue;
			}
		}
		do {
			if ( $links and array_key_exists( $key, $links ) ) {
				if ($debug) print "links ";
				$value = gui_hyperlink( $data, sprintf( $links[ $key ], $data ) );
				break;
			}
			if ( $selectors and array_key_exists( $key, $selectors ) ) {
				if ($debug) print "has selectors ";
				$selector_name = $selectors[ $key ];
				if ( strlen( $selector_name ) < 2 ) {
					die( "selector " . $key . "is empty" );
				}
				//////////////////////////////////
				// Selector ($id, $value, $args //
				//////////////////////////////////

				$value = $selector_name( $key, $data, $args); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
				break;
			}
			if ($edit) {
				$field_events = sprintf( $events, $key );

				if ($table_name) {
					$type = sql_type($table_name, $key);
//					print $type . "<br/>";
					switch ( substr( $type, 0, 3 ) ) {
						case 'dat':
							$value = gui_input_date($key, null, $data, $events);
							break;
						case 'var':
							$length = 10;
							$r = array();
							if (preg_match_all('/\(([0-9]*)\)/', $type, $r))
							{
								$length = $r[1][0];
							}
							if ($length > 100) {
								$value = gui_textarea($key, $data, $field_events);
							} else {
								$value        = gui_input( $key, $data, $field_events );
							}
							break;
						default:
							$field_events = sprintf( $events, $key );
							$value        = gui_input( $key, $data, $field_events );
							break;
					}
				} else {
					if ($debug) var_dump($data);
					$value        = gui_input( $key, $data, $field_events );
				}
				break;
			}
			$value = $data;
		} while (0);

		array_push( $row_data, $value );
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
function TableData($sql, $args = null)
{
	$result = sql_query( $sql );
	if ( ! $result ) {
		return "error: " . $sql . sql_error( $sql );
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

	$table_names = array();
	if (preg_match_all("/from ([^ ]*)/" , $sql, $table_names))
	{
		$args["table_name"] = $table_names[1][0];
	}

	if ( $header ) {
		$header_line = TableHeader($sql, false, $skip_id, $meta_fields);
	} else {
		 $header_line = null;
	}
	$row_count = 0;

	$v_line = $v_checkbox ? array() : null;

	if (strstr($sql, "describe")) // New Row
	{
		$new_row = array();
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$key = $row["Field"];
			if ($key == $id_field)
				continue;
			if ($values and isset($values[$key])) {
				$new_row[$key] = $values[$key];
			} else {
				$new_row[$key] = null;
			}
			if ($v_line !== null) {
				$v_line[$key] = gui_checkbox("chk_" . $key, "checkbox", $new_row[$key] != null);
			}
		}
		$new_row = PrepareRow($new_row, $args, null);

		if ($v_line) array_push($rows_data, $v_line);
		if ($header_line) array_push ($rows_data, $header_line);
		array_push($rows_data, $new_row);
		return $rows_data;
	} else {
		if ($header_line)
			array_push($rows_data, $header_line);
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$row_id = $row[$id_field];
			if (! $row_id)
			{
				die("no row id");
			}
			// print $key;
			$row_count ++;

			if ($meta_fields and is_array($meta_fields))
			{
				foreach ($meta_fields as $meta_key){
					$meta_value = sql_query_single_scalar("select meta_value from " . $meta_table . " where " . $meta_key_field . " = $row_id " .
					" and meta_key = " . quote_text($meta_key));

					$key = $meta_table . '/'. $meta_key;
					$row[$key] = $meta_value;
				}
			}
			$row = PrepareRow($row, $args, $row_id);
			array_push($rows_data, $row);
		}
		return $rows_data;
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
	$args["v_checkbox"] = true;
	$args['events'] = 'onchange="changed(this)"';
//	$args["show_cols"] = array(0 => false);
	return GuiRowContent($table_name, null, $args);

//	$skip_id = GetArg($args, "skip_id", true);
//	$header = TableHeader($sql, false, $skip_id);
//	$empty_row = array();
//	$result = sql_query($sql);
//	while ($row = sql_fetch_row($result)){
//		$key = $row[0];
//		if ($key === "id") continue;
//		$value = '';
//		if (isset($args["fields"]) and isset($args["fields"][$key])) {
//			$value = $args["fields"][$key];
//		}
//		array_push($empty_row, gui_input($key, $value));
//	}
//	return gui_table_args(array($header, $empty_row), $table_name, $args);
}

//function NewRow($table_name, $args = null, $transpose = false)
//{
//	$sql = "describe $table_name";
//
//	$skip_id = true;
//
//	$header = TableHeader($sql, false, $skip_id);
//	// var_dump($header);
//	$result = sql_query($sql);
//	if ($result){
//		$add_checkbox = GetArg($args, "add_checkbox", false);
//		$checkbox_class = GetArg($args, "checkbox_class", false);
//		$data = array();
//		while ($row = sql_fetch_row($result)){
//			$key = $row[0];
//			if ($key === "id") continue;
//			$value = '';
//			if (isset($args["fields"]) and isset($args["fields"][$key])) {
//				$value = $args["fields"][$key];
//			}
//			array_push($data, gui_input($key, $value));
//		}
//		$table = array($header, $data);
//		if ($transpose)
//			$table = array_map(null, ...$table);
//
//		if ($add_checkbox and $transpose){
//			for ($i = 0; $i < count($table); $i++)
//			{
//				array_unshift($table[$i], gui_checkbox("chk_" . $table[$i][0], $checkbox_class, false));
//			}
//		}
//		return gui_table($table, $table_name);
//	}
//}


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
	$id_key = GetArg($args, "id_key", "id");
	if (! isset($args["skip_id"])){
		$args["skip_id"] = true;
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

function GuiTableContent($table_id, $sql, $args, &$sum_links = null)
{
	// Fetch the data from DB.
	$rows_data = TableData( $sql, $args);

	$row_count = count( $rows_data);

	// Convert to table if data returned.
	if ( $row_count >= 1 ) {
		return gui_table_args( $rows_data, $table_id, $args );
	}

	return null;
}
