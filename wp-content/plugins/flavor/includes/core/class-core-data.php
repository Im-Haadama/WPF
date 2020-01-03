<?php

class Core_Data
{
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Core_Data constructor.
	 */
	public function __construct() {
	}

	static function handle_operation( $operation )
	{
		switch ($operation){
			case "data_auto_list":
				$prefix = get_param("prefix", true);

				$lists = array("products" => array("table" => "im_products", "field_name" =>'post_title', "include_id" => 0, "id_field" => "ID"),
				"tasks" => array("table"=>"im_tasklist", "field_name" => "task_description", "include_id" => 1, "id_field" => "id", "query" => " status = 0"),
				"users" => array("table" => "wp_users", "field_name" => "display_name", "id_field" => "ID"));
				$list = get_param("list", true);
				if (! isset($lists[$list])) die ("Error: unknown list " . $list);
				$table_name = $lists[$list]["table"];
				$field = $lists[$list]["field_name"];

				$args = $lists[$list];
				$args["datalist"] = $list . "_list";

				print Core_Data::auto_list($table_name, $field, $prefix, $args);
				return true;
			case "data_update":
				$table_name = get_param("table_name", true);
				return self::update_data($table_name);

			case "data_save_new":
				$table_name = get_param("table_name", true);
				return self::SaveNew($table_name);

			case "data_active":
				$active = get_param("active", true);
				$table_name = get_param("table_name", true);
				$row_id = get_param("id", true);
				return self::Active($table_name, $row_id, $active);

		}
	}

	static function Active($table_name, $row_id, $active)
	{
		$row_id = intval($row_id);

		return sql_query("update $table_name set is_active = $active where id = $row_id");
	}

	static function SaveNew($table_name)
	{
		$ignore_list = ["dummy", "operation", "table_name"];
		$sql    = "INSERT INTO $table_name (";
		$values = "values (";
		$first  = true;
		$sql_values = array();
		foreach ( $_GET as $key => $value ) {
			if (in_array($key, $ignore_list))
				continue;
			if ( ! $first ) {
				$sql    .= ", ";
				$values .= ", ";
			}
			$sql    .= $key;
			$values .= "?"; // "\"" . $value . "\"";
			$first  = false;

			$sql_values[$key] = $value;
		}
		$sql    .= ") ";
		$values .= ") ";
		$sql    .= $values;

		$stmt = sql_prepare($sql);
		sql_bind($table_name, $stmt, $sql_values);
		if (!$stmt -> execute())
			sql_error($sql);

		return sql_insert_id();
	}

	static function update_data($table_name)
	{
		// TODO: adding meta key when needed(?)
		global $meta_table_info;

		$row_id = intval(get_param("id", true));

		// Prepare sql statements: primary and meta tables;
		$values = self::data_parse_get($table_name, array("search", "operation", "table_name", "id", "dummy"));

		foreach ($values as $tbl => $changed_values)
		{
			foreach ($changed_values as $changed_field => $changed_pair){
//				print $changed_field . " " . $changed_pair[0] . "<br/>";
				$changed_value = $changed_pair[0];
				$is_meta = $changed_pair[1];
				if (sql_type($table_name, $changed_field) == 'date' and strstr($changed_value, "0001")) {
					$sql = "update $table_name set $changed_field = null where id = " . $row_id;
					// print $sql;
					if ($row_id) sql_query($sql);
					continue;
				}
				if ($is_meta){
					if (! isset($meta_table_info)) return false;
					$sql = "update $tbl set " . $meta_table_info[$tbl]['value'] . "=?" .
					       " where " . $meta_table_info[$tbl]['key'] . "=? " .
					       " and " . $meta_table_info[$tbl]['id'] . "=?";
				}
				else
					$sql = "update $table_name set $changed_field =? where id =?";

//				 print $sql;
				$stmt = sql_prepare($sql);
				if (! $stmt) return false;
				if ($is_meta){
					if (! sql_bind($tbl, $stmt,
						array($meta_table_info[$tbl]['value'] => $changed_value,
						      $meta_table_info[$tbl]['key'] => $changed_field,
						      $meta_table_info[$tbl]['id'] => $row_id))) return false;
				} else {
					if ( ! sql_bind($table_name, $stmt,
						array(
							$changed_field => $changed_value,
							sql_table_id($table_name)  => $row_id
						) ) ) {
						return false;
					}
				}
				if (!$stmt->execute()) {
					print "Update failed: (" . $stmt->errno . ") " . $stmt->error . " " . $sql;
					die(2);
				}
			}
		}
		return true;
	}

	static function data_parse_get($table_name, $ignore_list) {
		$debug = false; // (1== get_user_id());
		$values =array();
		foreach ( $_GET as $key => $value ) {
			$value = stripcslashes($value);
			if ( in_array( $key, $ignore_list ) ) {
				continue;
			}
			$tbl   = $table_name;
			$field = $key;
			$meta  = false;
			if ( $st = strpos( $key, "/" ) ) {
				$tbl   = substr( $key, 0, $st );
				$field = substr( $key, $st + 1 );
				$meta  = true;
			}
			if ( ! isset( $values[ $tbl ] ) ) {
				$values[ $tbl ]  = array();
			}

			if ($debug) print "parse: $key $value<br/>";
			$values[ $tbl ][ $field ] = array( $value, $meta );
		}
		return $values;
	}

	static function set_args_value(&$args, $ignore_list = null)
	{
		if (! $ignore_list) $ignore_list = array("operation", "table_name");
		foreach ($_GET as $key => $data)
		{
			if (! in_array($key, $ignore_list))
			{
				if (! isset($args["fields"]))
					$args["fields"] = array();
			}
			$args["values"][$key] = $data;
		}
	}

	static function auto_list($table_name, $field, $prefix, $args = null)
{
	$data = "";

	// print "field=$field<br/>";
	if (!$args) $args = [];
	$id_field = GetArg($args, "id_field", "id");
	$include_id = GetArg($args, "include_id", false);
	$datalist = GetArg($args, "datalist", null);

	$args["sql"] = "select $id_field, $field from $table_name where $field like '%" . $prefix . "%'";
	$query = GetArg($args, "query", null); 	if ($query) $args["sql"] .= " and " . $query;
	// print $args["sql"] . "<br/>";
	$args["field"] = $field;
	$args["include_id"] = $include_id;

	$data .= TableDatalist($datalist, $table_name, $args);

	return $data;
}
	static function TableHeader($sql, $args) // $add_checkbox = false, $skip_id = false, $meta_fields = null)
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
						$result[$field] = im_translate((isset($header_fields[$field]) ? $header_fields[$field] : $field));
					}
			} else {
				$result = $header_fields;
			}
			if (GetArg($args, "add_checkbox", false)) array_unshift($result, "");
			return $result;
		}

		$result = sql_query( $sql );

		if (! $result)
			return null;

		$headers = array();
		if (GetArg($args, "add_checkbox", false)) $headers["select"] = im_translate("select");
		$debug = false;

		// If not sent, create from database fields.
		if (strstr($sql, "describe") or strstr($sql, "show"))
		{
			while ($row = sql_fetch_row($result))
			{
				if (! $skip_id or strtolower($row[0]) !== "id" and !isset($args["hide_cols"])) {
					$headers[$row[0]] = im_translate($row[0]);
					// array_push($headers, im_translate($row[0]));
				} else {
					if ($debug) print "skip header";
				}
			}
		} else { // Select
			$i      = 0;
			$fields = mysqli_fetch_fields( $result );
			foreach ( $fields as $val ) {
				if ((! $skip_id or strtolower($val->name) !== "id") and !isset($args["hide_cols"][$val->name])) {
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

	static function PrepareRow($row, $args, $row_id)
	{
		if (is_null($row)){
			return null; // Todo: find why PivotTable creates null rows as in invoice_table.php
		}

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

		if (! is_array($row))
		{
			my_log( __FUNCTION__ . "invalid row ");
			return $row;
		}

		foreach ($row as $key => $data)
		{
			if (isset($row[$key])) $data = $row[$key];

			// General preparation... decide the field name and save the orig data and default data.
			$nm = $key; // mnemonic3($key);
			if ($add_field_suffix)	$input_name = $nm . '_' . $row_id;
			else $input_name = $nm;

			$orig_data = $data;
			$value = self::prepare_text($data); // Default;
			if ($debug) print  "<br/>handling $key ";
			if (strtolower($key) == "id" and $skip_id) continue;

			if ($events) {
				if ($transpose)	$field_events = sprintf($events, "'" . $key . "'", $row_id);
				else			$field_events = sprintf( $events, $row_id, $key );

				$args["events"] = $field_events;
			}

			// Let's start
			do {
				if ($links and ! is_array($links))	die ("links should be array");
				if ( $links and  array_key_exists( $key, $links )) {
					if ($debug) print "Has links for $key";
					if ( $selectors and array_key_exists( $key, $selectors ) ) {
						if ($debug) print " and also selector";
						$selector_name = $selectors[ $key ];
						$selected = $selector_name( $input_name, $orig_data, $args ); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
					} else $selected = $value;

					$value = Core_Html::GuiHyperlink($selected, sprintf( $links[ $key ], $data ), $args ); // TODO: if links contains encoded url - it will warn about missing parameter and create bad link. %d...
					break;
				}
				if ( $selectors and array_key_exists( $key, $selectors ) ) {
					$selector_name = $selectors[ $key ];
					if ( strlen( $selector_name ) < 2 ) die( "selector " . $key . "is empty" );
					// print $selector_name;
					$value = $selector_name( $input_name, $orig_data, $args );
					// $value = (function_exists($selector_name) ? $selector_name( $input_name, $orig_data, $args ) : $orig_data); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
//					if (! function_exists($selector_name)) {
//						print( "function $selector_name does not exists" );
//						print sql_trace();
//						die(1);
//					}
					if ($drill) {
						$operation = GetArg($args, "drill_operation", "show_archive");
						$value = Core_Html::GuiHyperlink($value, add_to_url(array($key => $orig_data, "operation" => $operation)), $args);
					}
					break;
				}

				/// 5/9/2019 Change!! edit_cols by default is to edit. if it set, don't edit.
				/// 23/9/2019  isset($edit_cols[$key]) - set $args["edit_cols"][$key] for fields that need to be edit.
				if ($edit  and (! $edit_cols or (isset($edit_cols[$key]) and $edit_cols[$key]))){
					if (! $key)	continue;
					if ($field_events) $args["events"] = $field_events;
					if ( $table_name ) {
//					if (isset($args["field_types"])) {
//						$type = $args["field_types"][$key];
//						$value = gui_input_by_type($input_name, $type, $args, $value);
						if (isset($args["sql_fields"])) {
							$type = sql_field($args["sql_fields"], $key);
							$value = Core_Html::gui_input_by_type($input_name, $type, $args, $value);
						}
					} else
						$value = GuiInput($input_name, $data, $args); //gui_input( $key, $data, $field_events, $row_id);
					break;
				}
				if ( $selectors and array_key_exists( $key, $selectors )) {
					$selector_name = $selectors[ $key ];
					if ( strlen( $selector_name ) < 2 ) die( "selector " . $key . "is empty" );
					//////////////////////////////////
					// Selector ($id, $value, $args //
					//////////////////////////////////
					if (function_exists($selector_name))
						$value = $selector_name( $key, $orig_data, $args); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
					else
						$value = "selector $selector_name not found";
					break;
				}
				// Format values by type.
				if (isset($args["sql_fields"])){
					$type = sql_field($args["sql_fields"], $key);
//				print $key . " " . $type . "<br/>";
					switch (strtok($type, "(")) {
						case 'time':
							$value = substr($value, 0, 5);
							break;
					}
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
						array_push($row_data, Core_Html::GuiButton($btn, $text, array("action" => "execute_url('" . $action_url . "', $client_action, $btn )", $text)));
					} else {
						$action_url = sprintf($action[1], $row_id);
						array_push($row_data, Core_Html::GuiHyperlink($text, $action_url, $args));
					}
				} else {
					$h = sprintf($action, $row_id);
					array_push($row_data, $h);
				}
			}
		}

		return $row_data;
	}

	static function prepare_text($string)
	{
		// Todo: convert text to url, only if not already hyperlink.
//	$url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
//	$string = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $string);
		return $string;
		// return nl2br($string);
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

	static function RowsData($sql, $id_field, $skip_id, $v_checkbox, $checkbox_class, $h_line, $v_line, $m_line, $header_fields, $meta_fields, $meta_table, &$args)
	{
		$result = sql_query( $sql );
		if ($args) $args["sql_fields"] = mysqli_fetch_fields($result);
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
						$v_line[ $key ] = Core_Html::gui_checkbox( "chk_" . $key, $checkbox_class, false );
					}
				}
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
						$v_line[ $key ] = Core_Html::gui_checkbox( "chk_" . $key, $checkbox_class, false );
					}
					if ( $h_line ) {
						// print "adding $key<br/>";
//					print "trans " . $header_fields[$key] . "<br/>";
						$h_line[ $key ] = im_translate( $header_fields[ $key ++ ], $args );
					}
				}
			}
			if ($v_line) $rows_data["checkbox"] = $v_line;
			if ($h_line) $rows_data['header'] = $h_line;
			if ($m_line) { $rows_data["mandatory"] = $m_line; $args["hide_rows"]["mandatory"] = 1; }

			$rows_data[$row_id] = $the_row;
		}
		if ( ! $result ) { print "Error #N1"; return null;	}

		return $rows_data;
	}

	static function NewRowData($field_list, $values, &$v_line, &$h_line, &$m_line, $skip_id, $checkbox_class, $header_fields, $fields, &$args )
	{
		$new_row = array();

		$mandatory_fields = GetArg($args, "mandatory_fields", null);
		if ($mandatory_fields) $mandatory_fields = array_assoc($mandatory_fields);

		// assert(0); // will fire
		// assert (! isset($field_list[0]), "field list in seq array" );
		foreach ( $field_list as $key => $field ) {
			if (  $skip_id and strtolower($key) === "id" ) continue;
			assert( isset( $field_list[ $key ] ) );

			if ( $values and isset( $values[ $key ] ) ) $new_row[ $key ] = $values[ $key ];
			else $new_row[ $key ] = null;

			if ( $v_line !== null ) $v_line[ $key ] = Core_Html::gui_checkbox( "chk_" . $key, $checkbox_class, $new_row[ $key ] != null );
			if ( is_array( $h_line ) /* and $header_fields */) $h_line[ $key ] = isset( $header_fields[ $key ] ) ? im_translate( $header_fields[ $key ], $args ) : $key;
			if ( is_array( $m_line ) ) $m_line[ $key ] = isset( $mandatory_fields[ $key ] );
		}

		if ($v_line) $rows_data["checkbox"] = $v_line;
		if ($h_line) $rows_data['header'] = $h_line;
		if ($m_line) { $rows_data["mandatory"] = $m_line; $args["hide_rows"]["mandatory"] = 1; }

		if ($fields)
		{
			$new_row_ordered = array();
			foreach ($fields as $key => $field) $new_row_ordered[$key] = isset($new_row[$key]) ? $new_row[$key] : null;
			$new_row = $new_row_ordered;
		}
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
	static function TableData($sql, &$args = null)
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

		$header = GetArg($args, "header", true);
		$field_list = self::FieldList($sql, $args);
//	 debug_var($field_list);
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

		$h_line = ($header ? self::TableHeader( $sql, $args ) : null);

		$m_line = $mandatory_fields ? array() : null;

		$v_line = $v_checkbox ? array() : null;

		if (strstr($sql, "describe") || strstr($sql, "show col")) // New Row
		{
			if ($debug) print "creating new row<br/>";
			$rows_data = self::NewRowData( $field_list, $values, $v_line, $h_line, $m_line, $skip_id, $checkbox_class, $header_fields, $fields, $args );
		} else {
			if ($debug) print "getting data<br/>";
			// print "before: "; var_dump($h_line); print "<br/>";
			$rows_data = self::RowsData($sql, $id_field, $skip_id, $v_checkbox, $checkbox_class, $h_line, $v_line, $m_line, $header_fields, $meta_fields, $meta_table, $args);
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
	static function FieldList($sql, &$args)
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
	static function HandleTableAcc($total_line, $rows_data, $fields)
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
	static function HandleAcc(&$acc_fields, $row)
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

}