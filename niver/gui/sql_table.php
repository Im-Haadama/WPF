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
	// We need only the header. Remove query and replace with false.
//	if (strstr($sql, "where"))
//		$sql = substr($sql, 0, strpos($sql, "where")) . " where 1 = 0";

	$result = sql_query( $sql );

	if (! $result)
		return null;

	$headers = array();
	$debug = false;

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
		if ( $add_checkbox ) {
			array_push( $headers, "" );
		} // future option: gui_checkbox("chk_all", ""));
		foreach ( $fields as $val ) {
			if (! $skip_id or strtolower($val->name) !== "id") {
				$headers [$val->name] = im_translate($val->name);
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

static $mn = array();
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

function mnemonic3($key)
{
	global $mn;
//	 var_dump($mn); print "<br/>";
	$chars = "abcdefghijklmnopqrstuvwxyz123456789";
	if (isset ($nm[$key])) return $mn[$key];

	$short_key = $key;
//	print "sk=$short_key<br/>";

	// For meta fields.
	if (($s = strpos($key, '/'))) {
		$short_key = substr ($key, $s + 1);
		// print "sk=$short_key<br/>";
	}

	// Try all 3 letters.
	$poss = substr($short_key, 0, 3);
//	print "poss=$poss<br>";
	if (! mn_used($poss) and (strlen($poss) == 3)) {
		$mn[$short_key] = $poss;
		return $poss;
	}

	// If already used, take 2 letters and the first that is available.
	for ($i = 0; $i < strlen($chars); $i ++){
		$poss = substr($short_key, 0, 2) . substr($chars, $i, 1);
//		print "poss=$poss<br/>";
		if (! mn_used($poss)) {
			$mn[$short_key] = $poss;
			return $poss;
		}
	}
//	print "not found";
	return "not";
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
			print $key . " " . $data . "<br/>";
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
			if ( $links and array_key_exists( $key, $links ) ) {
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
				$value = $selector_name( $input_name, $orig_data, $args ); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
				if ($drill) $value = gui_hyperlink($value, get_url() . "&$key=$value");
				break;
			}
			// print "pp e=" .$edit . " e_c=" . (is_array($edit_cols) ? comma_implode($edit_cols) : $edit_cols) . " ec[k]=" . isset($edit_cols[$key]) . "<br/>";

			/// 5/9/2019 Change!! edit_cols by default is to edit. if it set, don't edit.
			/// 23/9/2019  isset($edit_cols[$key]) - set $args["edit_cols"][$key] for fields that need to be edit.
			if ($edit and  (! $edit_cols or (isset($edit_cols[$key]) and $edit_cols[$key]))){
				if (! $key)
					continue;
				if ($field_events) $args["events"] = $field_events;
				if ( $table_name ) {
					if (isset($args["field_types"]))
						$type = $args["field_types"][$key];
					else
						$type = sql_type( $table_name, $key );
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

/**
 * Header will be build from query or sent by header_fields. In the later case, this function will transform it from seq array to assoc array.
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

	// print __FUNCTION__ . "<br/>";
	$result = sql_query( $sql );
	if ( ! $result ) {
		print "ERROR";
		return null;
	}

	$rows_data = array();

	$header = GetArg($args, "header", true);
	$field_list = FieldList($sql, $args);
	$mandatory_fields = GetArg($args, "mandatory_fields", null);
	$header_fields = GetArg($args, "header_fields", null);
	$id_field = GetArg($args, "id_field", "id");
	$skip_id = GetArg($args, "skip_id", false);
	$meta_fields = GetArg($args, "meta_fields", null);
	$meta_table = GetArg($args, "meta_table", null);
	$meta_key_field = GetArg($args, "meta_key", "id");
	$values = GetArg($args, "values", null);
	$v_checkbox = GetArg($args, "v_checkbox", null);
	$checkbox_class = GetArg($args, "checkbox_class", "checkbox");

	$table_names = array();
	if (preg_match_all("/from ([^ ]*)/" , $sql, $table_names))
	{
		$args["table_name"] = $table_names[1][0];
	}

	if ($header){
		if ($header_fields)
			$h_line = array(); // Build it from $header_fields using table fields.
		else
			$h_line = TableHeader($sql, false, $skip_id, $meta_fields);
	} else {
		$h_line = null; // No header.
	}
	if ($debug) {
		print "h_line: "; var_dump($h_line); print "<br/>";
	}
	$m_line = $mandatory_fields ? array() : null;

	$row_count = 0;

	$v_line = $v_checkbox ? array() : null;

	$i = 0;

	if ($debug) {
		print $sql ."<br/>";
		var_dump($mandatory_fields); print "<br/>";
		print "m_line: "; var_dump($m_line); print "<br/>";
	}


	if (strstr($sql, "describe") || strstr($sql, "show col")) // New Row
	{

		// var_dump($field_list);

		$new_row = array();
		if ($debug) print "new<br/>";

		foreach ($field_list as $key)
		{
			if ($debug) print "handling $key<br/>";
			if ($values and isset($values[$key])) {
				$new_row[$key] = $values[$key];
			} else {
				$new_row[$key] = null;
			}
			if ($v_line !== null) {
				if (! $skip_id or ($key != $id_field))
					$v_line[$key] = gui_checkbox("chk_" . $key, $checkbox_class, $new_row[$key] != null);
			}
			if (is_array($h_line) and $header_fields){
				if (! $skip_id or $key !== "id")
					$h_line[$key] = im_translate($header_fields[$i++], $args);
			}
			if (is_array($m_line)){
				if ($debug) print "adding " . isset($mandatory_fields[$key]);
				if(isset($mandatory_fields[$key])) {
					if ($debug) print $key ."<br/>";
					$m_line[$key] = 1;
				}
				// array_push($m_line, isset($mandatory_fields[$key]));
			}
		}
		// var_dump($m_line); print "<br/>";
		if ($v_line){
			$rows_data["checkbox"] = $v_line;
			// array_push($rows_data, $v_line);
		}

		if ($debug) var_dump($m_line);

		if ($m_line) $rows_data["mandatory"] = $m_line;
		// $header_line = TableHeader($sql, false, $skip_id, $meta_fields);

		if ($h_line){
			$rows_data['header'] = $h_line;
			$h_line = null;
		}

		$fields = GetArg($args, "fields", null);

		if ($fields) // new row field order would be like in database. Reorder it here.
		{
			$new_row_ordered = array();
			foreach ($fields as $field){
				$new_row_ordered[$field] = $new_row[$field];
			}
			$new_row = $new_row_ordered;
		}
		$rows_data["new"] = $new_row;
		// array_push($rows_data, $new_row);

		return $rows_data;
	} else {
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$the_row = array();
			if ( ! isset($row[$id_field])) {
				// Error... We don't have a valid row ID.
				print "<br/>field id:" . $id_field . "<br/>";
				var_dump( $row );
				print "<br/>";
				die( __FUNCTION__ . ":" . __LINE__ . "no row id" );
			}
			$row_id  = $row[ $id_field ];

			foreach ( $row as $key => $cell ) {
				// Change: 9/9/2019. We put the id only in multirow display
				if ( ! $skip_id or strtolower( $key ) !== "id" ) {
					$the_row[$key] = $cell;
					// array_push( $the_row, $cell );
				}
				if ($v_checkbox){
					if (! $skip_id or ($key != $id_field))
						$v_line[$key] = gui_checkbox("chk_" . $key, $checkbox_class, false);
				}
				if (is_array($h_line) and $header_fields){
					if ((! $skip_id or $key !== "id") and isset($header_fields[$i]))
						$h_line[$key] = im_translate($header_fields[$i], $args);
					$i++;
				}
			}

			$row_count ++;

			if ($meta_fields and is_array($meta_fields))
			{
				foreach ($meta_fields as $meta_key) {
					$meta_value = sql_query_single_scalar( "select meta_value from " . $meta_table . " where " . $meta_key_field . " = $row_id " .
					                                       " and meta_key = " . quote_text( $meta_key ) );

					$key             = $meta_table . '/' . $meta_key;
					$the_row[ $key ] = $meta_value;

					if ( $v_checkbox ) {
						$v_line[ $key ] = gui_checkbox( "chk_" . $key, $checkbox_class, false );
					}
					if ($h_line){
						$h_line[$key] = im_translate($header_fields[$i++], $args);
					}
				}
			}

			if ($v_line){
				$rows_data["checkbox"] = $v_line;
				// array_push($rows_data, $v_line);
				$v_checkbox = false;
				$v_line = null;
			}

			if ($h_line){
				$rows_data['header'] = $h_line;
				$h_line = null;
			}

			$rows_data[$row_id] = $the_row;
		}

		return $rows_data;
	}
}

function FieldList($sql, &$args)
{
//	print "field list $sql";
	$fields = GetArg($args, "fields", null);
	// print "fields: "; var_dump($fields) . "<br/>";
	if ($fields) return $fields;

	$fields = array();

	$result = sql_query($sql);
	if (strstr($sql, "describe") or strstr($sql, "show cols")){
		while ( $row = mysqli_fetch_assoc( $result ) ) {
//			var_dump($row);
			$fields[$row['Field']] = 1;
		}
	} else {
		$row = sql_fetch_assoc($result);
		if ($row) foreach ($row as $key => $cell) $fields[$key] = 1;
	}
//	var_dump($fields);
	$args["fields"] = $fields;

	return $fields;
}


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
function NewRow($table_name, $args)
{
	$args["edit"] = true;
	$args["table_name"] = $table_name;
	$args['events'] = 'onchange="changed_field(\'%s\')"';
	$args["add_field_suffix"] = false;
	return GuiRowContent($table_name, null, $args);
}

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

	if (! isset($args["skip_id"])) $args["skip_id"] = true;
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
	return GuiTableContent($table_name, $sql, $args);
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
	if (! $sql)
		$sql = "select * from $table_id";

	// Fetch the data from DB.
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

	$row_count = count( $rows_data);

	// Convert to table if data returned.
	if ( $row_count >= 1 ) {
		$html = gui_table_args( $rows_data, $table_id, $args );
		return $html;
	}

	return null;
}

function prepare_text($string)
{
	// Todo: convert text to url, only if not already hyperlink.
//	$url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
//	$string = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $string);
	return $string;
	// return nl2br($string);
}