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
require_once( ROOT_DIR . "/niver/data/translate.php");
require_once( ROOT_DIR . "/niver/gui/gem.php");

/**
 * Table header gets a sql query and returns array to be used as header, usually in html table.
 *
 * @param $sql
 * @param $args
 *
 * @return array
 */

function TableHeader($sql, $args) // $add_checkbox = false, $skip_id = false, $meta_fields = null)
{
	$debug = 0;
	$header_fields = GetArg($args, "header_fields", null);

	if ($debug) { var_dump($header_fields); print "<br/>"; }

	$skip_id = GetArg($args, "skip_id", false);

	// Option a - build from given header_fields.
	if ($header_fields) {
		if ($fields = GetArg($args, "fields", null)){
			$result = [];
			$fields = array_assoc($fields);
			foreach ($fields as $field => $v)
				if (! $skip_id or strtolower($field) !== "id"){
					$result[$field] = (isset($header_fields[$field]) ? $header_fields[$field] : $field);
				}
			return $result;
		}
		return array_assoc($header_fields);
	}
//	$add_checkbox = GetArg($args, "add_checkbox", false);

	// We need only the header. Remove query and replace with false.
//	if (strstr($sql, "where"))
//		$sql = substr($sql, 0, strpos($sql, "where")) . " where 1 = 0";

	$result = sql_query( $sql );

	if (! $result)
		return null;

	$headers = array();
	$debug = false;

	// If not sent, create from database fields.
	if (strstr($sql, "describe") or strstr($sql, "show"))
	{
		while ($row = sql_fetch_row($result))
		{
			if (! $skip_id or strtolower($row[0]) !== "id") {
				$headers[$row[0]] = im_translate($row[0]);
				// array_push($headers, im_translate($row[0]));
			} else {
				if ($debug) print "skip header";
			}
		}
	} else { // Select
		$i      = 0;
		$fields = mysqli_fetch_fields( $result );
//		if ( $add_checkbox ) {
//			array_push( $headers, "" );
//		} // future option: gui_checkbox("chk_all", ""));
		foreach ( $fields as $val ) {
			if (! $skip_id or strtolower($val->name) !== "id") {
//				print "$skip_id adding " . $val->name . "<br/>";
				$headers [$val->name] = im_translate($val->name);
			}
			$i ++;
		}
	}
//	if ($meta_fields and is_array($meta_fields))
//	{
//		foreach ($meta_fields as $f)
//			array_push($headers, $f);
//	}

//	  print __FUNCTION__ . ":"; var_dump($headers); print "<br/>";
	return $headers;
}

static $mn = array();
/**
 * @param $p
 *
 * @return bool
 */
function mn_used($p)
{
	global $mn;

	foreach ($mn as $m){
		if ($p == $m){
//			print $p . " is used <br/>";
			return true;
		}
	}
	return false;
}


/**
 * PrepareRow - adds links, selectors and edit inputs.
 * $args:
 * Cell preparation priority (the top most that relevant applies).
 * 1) links - add hyperlink to other content. e.g "admin-post.php?id=%s".
 *            Todo: selector + link -> drill into table
 *
 * Table can be used in two ways.
 * edit = true. The values of the records can be changed. Drill to specific key is not possible. Should be used by admin users for many rows or allowed users for single row
 * edit = false. Pressing a field will drill into it. (e,g selecting a supplier will filter only this supplier). TODO
 *
 * 2) selectors - select the value from a list. if this col is not editable, the id will converted to the value.
 *  or
 * 3) edit - should the row be edited. If so, edit_cols can affect specific fields like id.
 *
 *
 * @param $row
 * @param $args
 *
 * @param $row_id
 *
 * @return array
 * @throws Exception
 */

function PrepareRow($row, $args, $row_id)
{
	if (is_null($row)){
		return null; // Todo: find why PivotTable creates null rows as in invoice_table.php
	}

//	if (get_user_id() == 1) return $row;

	// On single row, the id is displayed in the header, and not showing in the table.
	$skip_id = GetArg($args, "skip_id", false);
	$links = GetArg($args, "links", null);
	$edit = GetArg($args, "edit", false);
	$drill = GetArg($args, "drill", false);
	$selectors = GetArg($args, "selectors", null);
	$actions = GetArg($args, "actions",null);
	$edit_cols = GetArg($args, "edit_cols", null);
	$transpose = GetArg($args, "transpose", null);
	$add_field_suffix = GetArg($args, "add_field_suffix", true);
	$debug = GetArg($args, "debug", false);

	$events = GetArg($args, "events", null); // $edit ? "onchange='changed_field(" . $row_id . ")'" : null); // Valid for grid. In transposed single row it will be replaced.
	$field_events = null;
	$table_name = GetArg($args, "table_name", null);

	$row_data = array();

	if ($debug and ! $table_name) print "no table name<br/>";

	if ($debug){
		print "start " . __FUNCTION__ . "<br/>";
		print "edit=$edit "; var_dump($edit_cols); print "<br/>";
		if (! $links) print "NO links<br/>";
		else { print "Links: "; var_dump ($links); print "<br/>"; }
		if ($selectors) {
			print "has selectors: "; var_dump($selectors); print "<br/>";
		}
		else print "No selectors<br/>";
		print "edit_cols: " . var_dump($edit_cols); print "<br/>";
	}

	if (! is_array($row))
	{
		my_log( __FUNCTION__ . "invalid row ");
		return $row;
	}
	// var_dump($row);
	foreach ( $row as $key => $data )
	{
		if (0 and get_user_id() == 1)
		{
			print $key  . "<br/>";
		}
		// General preparation... decide the field name and save the orig data and default data.
		$nm = $key; // mnemonic3($key);
//		print "key=$nm<br/>";
		if ($add_field_suffix)
			$input_name = $nm . '_' . $row_id;
		else
			$input_name = $nm;

//		print "in=$input_name<br/>";
		$orig_data = $data;
		$value = prepare_text($data); // Default;
		if ($debug) print  "<br/>handling $key ";
		if (strtolower($key) == "id" ) {
			if ($skip_id) {
				if ($debug) print "skip";
				continue;
			}
		}

		if ($events) {
			if ($transpose)	$field_events = sprintf($events, "'" . $key . "'", $row_id);
			else			$field_events = sprintf( $events, $row_id, $key );

			$args["events"] = $field_events;
		}

		// Let's start
		do {
//			var_dump($links);
			//print "key=$key " . $links[$key] . "<br/>";
			if ($links and ! is_array($links))
				die ("links should be array");
			if ( $links and  array_key_exists( $key, $links )) {
				if ($debug) print "Has links for $key";
				if ( $selectors and array_key_exists( $key, $selectors ) ) {
					if ($debug) print " and also selector";
					$selector_name = $selectors[ $key ];
					// print $selector_name;
					$selected = $selector_name( $input_name, $orig_data, $args ); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
				} else $selected = $value;

				$value = gui_hyperlink( $selected, sprintf( $links[ $key ], $data ) );
				break;
			} else {
				if ($debug){
					if (! $links or ! array_key_exists($key, $links)) print "no links[" .$key . "]";
				}
			}
			if ($debug and ! $selectors)
				print "no selectors<br/>";
			if ( $selectors and array_key_exists( $key, $selectors ) ) {
				if ( $debug ) {
					print "has selector ";
				}
				$selector_name = $selectors[ $key ];
				if ( strlen( $selector_name ) < 2 ) {
					die( "selector " . $key . "is empty" );
				}
				// print $selector_name;
				$value = (function_exists($selector_name) ? $selector_name( $input_name, $orig_data, $args ) : $orig_data); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
				if ($drill) {
					$operation = GetArg($args, "drill_operation", "show_archive");
					// debug_var($operation . " " . $key);
					$value = gui_hyperlink($value, add_to_url(array($key => $orig_data, "operation" => $operation)));
				}
				break;
			}
			// print "pp e=" .$edit . " e_c=" . (is_array($edit_cols) ? comma_implode($edit_cols) : $edit_cols) . " ec[k]=" . isset($edit_cols[$key]) . "<br/>";

			/// 5/9/2019 Change!! edit_cols by default is to edit. if it set, don't edit.
			/// 23/9/2019  isset($edit_cols[$key]) - set $args["edit_cols"][$key] for fields that need to be edit.

			if ($edit  and (! $edit_cols or (isset($edit_cols[$key]) and $edit_cols[$key]))){
				if (! $key)
					continue;
				if ($field_events) $args["events"] = $field_events;
				if ( $table_name ) {
					if (isset($args["field_types"]))
						$type = $args["field_types"][$key];
					else
						try {
							$type = sql_type( $table_name, $key );
						} catch (Exception $e) {
							print __FUNCTION__ . ": can't find type for $key<br/>";
							var_dump($row);
							return null;
						}
					// input_by_type($input_name, $type, $args, $data = null)
					$value = gui_input_by_type($input_name, $type, $args, $value);
				} else {
					if ( $debug ) {
						var_dump( $data );
					}
					$value = GuiInput($input_name, $data, $args); //gui_input( $key, $data, $field_events, $row_id);
//						print "v=$value<br/>";
				}
			}
			if ($debug) print "after $key";
			if ( $selectors and array_key_exists( $key, $selectors )) {
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

		} while (0);
		if ($debug) print " setting ";
		$row_data[$key] = $value;
	}

	if ($actions){
		foreach ($actions as $action) {
			if (is_array($action))
			{
				$text = $action[0];
				if ($s = strpos($action[1], ';')) { // We have javascript command.
					$server_action = substr($action[1], 0, $s);
					$action_url = sprintf($server_action, $row_id);
					$client_action = substr($action[1], $s + 1);
					$btn = "btn_$text" . "_" . $row_id;
					array_push($row_data, gui_button($btn, "execute_url('" . $action_url . "', $client_action, $btn )", $text));
				} else {
					$action_url = sprintf($action[1], $row_id);
					array_push($row_data, gui_hyperlink($text, $action_url));
				}
			} else {
				$h = sprintf($action, $row_id);
				array_push($row_data, $h);
			}
		}
	}

	return $row_data;
}

//function PrepareCell($key, $orig_data, $edit_cols, $selectors, $links)
//{
//	$edit = true;
//	$selector = true;
//	$links = true;
//
//	$data = $orig_data;
//	if ($links) $data = sprintf($links[$key], $value)
//}

function RowsData($sql, $id_field, $skip_id, $v_checkbox, $checkbox_class, $h_line, $v_line, $m_line, $header_fields, $meta_fields, $meta_table, $args)
{
	$result = sql_query( $sql );
	$row_count = 0;
	$rows_data = [];
	while ( $row = mysqli_fetch_assoc( $result ) ) {
		$the_row = array();
		if (isset($row[strtoupper($id_field)])) $id_field = strtoupper($id_field);
		if ( ! isset( $row[ $id_field ] ) ) {
			// Error... We don't have a valid row ID.
			print "<br/>field id:" . $id_field . "<br/>";
			var_dump( $row );
			print "<br/>";
			print $sql . "<br/>";
			die( __FUNCTION__ . ":" . __LINE__ . "no id_field" );
		}
		$row_id = $row[ $id_field ];

		foreach ( $row as $key => $cell ) {
			// Change: 9/9/2019. We put the id only in multirow display
			if ( ! $skip_id or strtolower( $key ) !== "id" ) {
				$the_row[ $key ] = $cell;
				// print "adding $key $cell<br/>";
				// array_push( $the_row, $cell );
			}
			if ( $v_checkbox ) {
				if ( ! $skip_id or ( $key != $id_field ) ) {
					$v_line[ $key ] = gui_checkbox( "chk_" . $key, $checkbox_class, false );
				}
			}
			// print "handling header<br/>";
			// print $skip_id . " " . $key . "<br/>";
//			if ( is_array( $h_line ) and $header_fields ) {
//				$down_key = strtolower( $key );
//				if ( ! $skip_id or $key !== "id" ) {
//					// print "adding $key<br/>";
//					$h_line[ $key ] = isset( $header_fields[ $down_key ] ) ? im_translate( $header_fields[ $down_key ], $args ) : $key;
//				}
//			}
		}

		$row_count ++;

		if ( $meta_fields and is_array( $meta_fields ) ) {
			foreach ( $meta_fields as $meta_key ) {
				$meta_value = sql_query_single_scalar( "select meta_value from " . $meta_table . " where " . $meta_key_field . " = $row_id " .
				                                       " and meta_key = " . quote_text( $meta_key ) );

				$key             = $meta_table . '/' . $meta_key;
				$the_row[ $key ] = $meta_value;

				if ( $v_checkbox ) {
					$v_line[ $key ] = gui_checkbox( "chk_" . $key, $checkbox_class, false );
				}
				if ( $h_line ) {
					// print "adding $key<br/>";
					$h_line[ $key ] = im_translate( $header_fields[ $key ++ ], $args );
				}
			}
		}
		if ($v_line) $rows_data["checkbox"] = $v_line;
		if ($h_line) $rows_data['header'] = $h_line;
		if ($m_line) { $rows_data["mandatory"] = $m_line; $args["hide_rows"]["mandatory"] = 1; }

		$rows_data[$row_id] = $the_row;
	}
	return $rows_data;
}

function NewRowData($field_list, $values, &$v_line, &$h_line, &$m_line, $skip_id, $checkbox_class, $header_fields, $fields, &$args )
{
	$new_row = array();
	$debug   = false;
	if ( $debug ) {
		print "new<br/>";
	}

	$mandatory_fields = GetArg($args, "mandatory_fields", null);
	if ($mandatory_fields) $mandatory_fields = array_assoc($mandatory_fields);

	// assert(0); // will fire
	// assert (! isset($field_list[0]), "field list in seq array" );
	foreach ( $field_list as $key => $field ) {
		if (  $skip_id and strtolower($key) === "id" ) continue;
		assert( isset( $field_list[ $key ] ) );
		if ( $debug ) print "handling $key<br/>";
		if ( $values and isset( $values[ $key ] ) ) {
			$new_row[ $key ] = $values[ $key ];
		} else {
			$new_row[ $key ] = null;
		}
		if ( $v_line !== null ) $v_line[ $key ] = gui_checkbox( "chk_" . $key, $checkbox_class, $new_row[ $key ] != null );

		if ( is_array( $h_line ) and $header_fields ) $h_line[ $key ] = isset( $header_fields[ $key ] ) ? im_translate( $header_fields[ $key ], $args ) : $key;
		if ( is_array( $m_line ) ) $m_line[ $key ] = isset( $mandatory_fields[ $key ] );
	}

	if ($v_line) $rows_data["checkbox"] = $v_line;
	if ($h_line) $rows_data['header'] = $h_line;
	if ($m_line) { $rows_data["mandatory"] = $m_line; $args["hide_rows"]["mandatory"] = 1; }

	if ($fields)
	{
		$new_row_ordered = array();
		foreach ($fields as $key => $field){
			$new_row_ordered[$key] = isset($new_row[$key]) ? $new_row[$key] : null;
		}
		$new_row = $new_row_ordered;
	}
	// debug_var($new_row);
	$rows_data["new"] = $new_row;

	// debug_var($new_row);
	return $rows_data;
}

/**
 * Header will be build from query or sent by header_fields. In the later case, this function will transform it from seq array to assoc array.
 * Option page and rows_per_page will make "pages". Default - not paged.
 *
 * @param $sql
 * @param null $args
 *
 * @return array|string
 * @throws Exception
 */
function TableData($sql, &$args = null)
{
	$debug = 0; // (get_user_id() == 1);

	if (strstr($sql, "select") and !strstr ($sql, "limit")){
		$page = GetArg($args, "page", null);
		if ($page) {
			$rows_per_page = GetArg($args, "rows_per_page", 10);
			$offset = ($page - 1) * $rows_per_page;

			$limit = (($page > -1) ? " limit $rows_per_page offset $offset" : "");
			$sql .= $limit;
		}
	}

	// print __FUNCTION__ . "<br/>";
	$result = sql_query( $sql );
	if ( ! $result ) { print "Error #N1"; return null;	}

	$header = GetArg($args, "header", true);
	$field_list = FieldList($sql, $args);
	// debug_var($field_list);
	// print __FUNCTION__ ; var_dump($field_list); print "<br/>";
	$mandatory_fields = GetArg($args, "mandatory_fields", null);  $mandatory_fields = array_assoc($mandatory_fields);
	$fields = GetArg($args, "fields", null);  $fields = array_assoc($fields);
	if ($debug) {print "fields: "; var_dump($fields); print "<br/>";}
	$skip_id = GetArg($args, "skip_id", false);
	$meta_fields = GetArg($args, "meta_fields", null);
	$meta_table = GetArg($args, "meta_table", null);
	$meta_key_field = GetArg($args, "meta_key", "id");
	$values = GetArg($args, "values", null);
	$v_checkbox = GetArg($args, "v_checkbox", null);
	$checkbox_class = GetArg($args, "checkbox_class", "checkbox");
	$header_fields = GetArg($args, "header_fields", null);	 $header_fields = array_assoc($header_fields);

	$table_names = array();
	if (preg_match_all("/from ([^ ]*)/" , $sql, $table_names))
	{
		$table_name = $table_names[1][0];
		$args["table_name"] = $table_name;
		$id_field = GetArg($args, "id_field", "id" /* long executing: sql_table_id($table_name) */);
	} else {
		$id_field = GetArg($args, "id_field", "id");
	}

	$h_line = ($header ? TableHeader( $sql, $args ) : null);

// 	debug_var($h_line);

	$m_line = $mandatory_fields ? array() : null;

	$v_line = $v_checkbox ? array() : null;

	if ($debug) {
		print $sql ."<br/>";
		var_dump($mandatory_fields); print "<br/>";
		print "m_line: "; var_dump($m_line); print "<br/>";
	}

	if (strstr($sql, "describe") || strstr($sql, "show col")) // New Row
	{
		if ($debug) print "creating new row<br/>";
		$rows_data = NewRowData( $field_list, $values, $v_line, $h_line, $m_line, $skip_id, $checkbox_class, $header_fields, $fields, $args );
		// debug_var($rows_data);
	} else {
		if ($debug) print "getting data<br/>";
		// print "before: "; var_dump($h_line); print "<br/>";
		$rows_data = RowsData($sql, $id_field, $skip_id, $v_checkbox, $checkbox_class, $h_line, $v_line, $m_line, $header_fields, $meta_fields, $meta_table, $args);
		// print "after: "; var_dump($h_line); print "<br/>";
	}

	if (! count($rows_data)) return null;

	return $rows_data;
}

/**
 * @param $sql
 * @param $args
 *
 * @return array|mixed|null
 */
function FieldList($sql, &$args)
{
//	print "field list $sql";
	$fields = GetArg($args, "fields", null);
	if (isset($fields[0])) $fields = array_assoc($fields);
//	print "1:"; var_dump($fields); print "<br/>";
//	 print "fields: "; var_dump($fields) . "<br/>";
	if ($fields) return $fields;

	$fields = array();

	$result = sql_query($sql);
	if (strstr($sql, "describe") or strstr($sql, "show cols")){
		while ( $row = mysqli_fetch_assoc( $result ) ) {
//			var_dump($row);
			$fields[$row['Field']] = 1;
		}
//		print "2:"; var_dump($fields); print "<br/>";
	} else {
		$row = sql_fetch_assoc($result);
		$skip_id = GetArg($args, "skip_id", false);
		$id_field = GetArg($args, "id_field", "id");
		if ($row) foreach ($row as $key => $cell) if (! $skip_id or ($key != $id_field)) $fields[$key] = 1;
//		print "3:"; var_dump($fields); print "<br/>";
	}

	$args["fields"] = $fields;

	return $fields;
}


/**
 * @param $total_line
 * @param $rows_data
 * @param $fields
 *
 * @return array
 */
function HandleTableAcc($total_line, $rows_data, $fields)
{
	foreach ($rows_data as $key => $row)
		if ($key != 'header')
			HandleAcc($total_line, $row);

	$t_line = array();
	foreach ($fields as $field => $a){
		$value = "";
		if (isset($total_line[$field]))	$value = is_array($total_line[$field]) ? $total_line[$field]['val'] : $total_line[$field];
		$t_line[$field] = $value;
	}

	return $t_line;
}

/**
 * @param $acc_fields
 * @param $row
 */
function HandleAcc(&$acc_fields, $row)
{
	// if (function_exists("sum_numbers")) print "AAAA";
//	var_dump($acc_fields); print "<br/>";
	foreach ($row as $key => $cell)
	{
		if (isset($acc_fields[$key]) and is_array($acc_fields[$key]) and function_exists($acc_fields[$key]['func'])) {
			 // if ($debug) print "summing " . $acc_fields[$key][0][2] . "<br/>";
			$acc_fields[$key]['func']($acc_fields[$key]['val'], $cell);
		} else {
//			print "not summing " . is_array($acc_fields[$key]) . " " . function_exists($acc_fields[$key][1]) . "<br/>";
		}
	}
}

/**
 * @param $table_name
 * @param $args
 *
 * @return string|null
 * @throws Exception
 */


/**
 * Get record from the database and display in html table.
 * This function defines the args for TableContent
 *
 * @param $table_name
 * @param $row_id
 * @param $args
 *
 * @return string|null
 * @throws Exception
 */
function GuiRowContent($table_name, $row_id, $args)
{
	$id_key = GetArg($args, "id_key", "id");
	$fields = GetArg($args, "fields", null);
	$table_id = GetArg($args, "table_id", $table_name);

	if (! isset($args["skip_id"]))	$args["skip_id"] = true;

	if (GetArg($args, "headers", null) and isset($args["headers"][0])) $args["headers"] = array_assoc($args["headers"]);

	$edit = GetArg($args, "edit", false);
	if ($edit) {
		$args["v_checkbox"] = 1;
		if (! isset($args["transpose"])) $args["transpose"] = 1;
		$args["events"] = "onchange=changed_field(%s)";
	}
	if ($row_id) { // Show specific record
		$sql = "select " . ($fields ? comma_implode($fields) : "*") . " from $table_name where " . $id_key . " = " . $row_id;
		$args["row_id"] = $row_id;
	} else { // Create new one.
		if ($fields) {
			$sql = "show columns from $table_name where field in ( " . comma_implode($fields, true) . ")";
		}
		else
			$sql = "describe $table_name";
	}
	return GuiTableContent($table_id, $sql, $args);
}

/**
 * Execute SQL. If data return, return html table with the data. Otherwise return null.
 *
 * @param $table_id
 * @param $sql
 * @param $args
 * @param null $sum_links
 *
 * @return string|null
 * @throws Exception
 */

function GuiTableContent($table_id, $sql, &$args = null)
{
	if (! $sql)	$sql = "select * from $table_id";

	// Fetch the data from DB or create the new row
	$rows_data = TableData( $sql, $args);

	$debug = GetArg($args, "debug", false);

	if ($debug)
	{
		print "sql: $sql<br/>";
		print "skip_id:" . GetArg($args, "skip_id", false) ."<br/>";
		print "<table border=\"1\">";
		foreach ($rows_data as $key => $row){
			// var_dump($row);
			print "<tr>";
			foreach ($row as $cell_key => $cell){
				print "<td>". $cell . "</td>";
			}
			print "</tr>";
		}
		print "</table>";
	}

	if (! $rows_data)
		return null;

	$id_field = GetArg($args, "id_field", "id");
	if (isset($args["edit_cols"]))
		$args["edit_cols"][$id_field] = 0;

	if (! isset($args["form_table"])) $args["form_table"] = $table_id;

	$row_count = count( $rows_data);
	if (isset($args["count"])) $args["count"] += $row_count;

	// Convert to table if data returned.
	if ( $row_count >= 1 ) {
		$html = gui_table_args( $rows_data, $table_id, $args );
		return $html;
	}

	return null;
}

/**
 * @param $string
 *
 * @return mixed
 */
function prepare_text($string)
{
	// Todo: convert text to url, only if not already hyperlink.
//	$url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
//	$string = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $string);
	return $string;
	// return nl2br($string);
}

// This function collects values from the table. If sql is not specified - all values are read and sent to doGuiDatalist.
function TableDatalist( $id, $table, $args = null)
{
	$field = GetArg($args, "field", "field");
	$include_id = GetArg($args, "include_id", false);
	$sql = GetArg($args, "sql", "select " . $field . ($include_id ? ", id" : "") .	 " from " . $table);
	if (!strstr($sql, "where")) $sql .= " where " . GetArg ($args, "query", "1");
	$id_field = GetArg($args, "id_field", "id");
	$values = [];

	// print "id_field: $id_field<br/>";

	$result = sql_query( $sql );
	// print $sql . "<br/>";
	while ( $row = sql_fetch_assoc($result ) ) {
//		var_dump($row); print "<br/>";
		// print "key = " . $row[$id_field];
		array_push($values, $row);
		// $values[$row[$id_field]] = $row;
		// $row["ID"]] = $row[$field];
	}

	return GuiDatalist($id, $values, $id_field,  $field, $include_id);
}
