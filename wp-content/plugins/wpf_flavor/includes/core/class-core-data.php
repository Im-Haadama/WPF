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
	private function __construct() {
	}

	static function init_hooks($loader)
	{
		$loader->AddAction("data_auto_list", __CLASS__, "data_auto_list");
		$loader->AddAction("data_update", __CLASS__, "data_update");
		$loader->AddAction("data_set_active", __CLASS__, "data_set_active");
	}

	static function data_set_active($args)
	{
		$id = GetArg($args, "id", 0);
		$table = GetArg($args, "table", null);
		$value = GetArg($args, "value", true);
		if (! $id or ! $table) {
			print "Failed: id or table are missing";
			return false;
		};
		$db_prefix = GetTablePrefix($table);
		$sql = "update ${db_prefix}$table set is_active = $value where id = $id";
		SqlQuery($sql);
	}

	static function data_save_new()
	{
		$table_name = GetParam("table_name", true);
		print self::SaveNew($table_name);
	}

	static function Inactive($table_name, $rows)
	{
		// TODO: adding meta key when needed(?)
		$db_prefix = GetTablePrefix($table_name);
		if (! is_array($rows)) $rows = array($rows);

		foreach($rows as $row_id)
			if (! SqlQuery("update ${db_prefix}$table_name set is_active = 0 where id = $row_id")) return false;
		return true;
	}

	static function Delete($table_name, $rows)
	{
		$db_prefix = GetTablePrefix($table_name);
		// TODO: adding meta key when needed(?)
		if (! in_array($table_name, array("missions", "supplier_price_list", "mission_types", "working_rates"))) die ("not allowed $table_name");
		$sql = "delete from ${db_prefix}$table_name where id in (" . CommaImplode($rows) . ")";
		MyLog(__FUNCTION__ . "$sql by " . get_current_user(), CommaImplode($rows));
		SqlQuery($sql );

		return true;
	}

	static function Active($table_name, $row_id, $active)
	{
		$row_id = intval($row_id);
		$db_prefix = GetTablePrefix($table_name);
		if (! in_array("is_active", SqlTableFields($table_name))) return true;

		return SqlQuery("update ${db_prefix}$table_name set is_active = $active where id = $row_id");
	}

	static function SaveNew($table_name)
	{
		$ignore_list = ["dummy", "operation", "table_name"];
		$row = apply_filters("data_save_new_$table_name", $_GET);
        if (!$row) return false;
		$row_id = SqlInsert($table_name, $row, $ignore_list);
		self::Active($table_name,$row_id,true);
		do_action("data_save_new_after_$table_name", $row, $row_id);
        return $row_id;
	}

	static function data_update_wrap()
	{
		$table_name = GetParam("table_name", true, null, true);
		self::data_update($table_name);
	}

	static function data_update($table_name)
	{
		$db_prefix = GetTablePrefix($table_name);
		// TODO: adding meta key when needed(?)
		global $meta_table_info;

		$row_id = intval(GetParam("id", true));
		$id_field = SqlTableId($table_name);

		$conn = GetSqlConn();

		SqlSetEncoding($conn, "${db_prefix}$table_name");

		// Prepare sql statements: primary and meta tables;
		$values = self::data_parse_get($table_name, array("search", "operation", "table_name", "id", "dummy"));
//		print "data_update_prepare_$table_name";
		$values = apply_filters("data_update_prepare_$table_name", $values, $row_id);

		foreach ($values as $tbl => $changed_values)
		{
			foreach ($changed_values as $changed_field => $changed_pair){
				$changed_value = $changed_pair[0];
				$is_meta = $changed_pair[1];
				if ( SqlType($table_name, $changed_field) == 'date' and strstr($changed_value, "0001")) {
					$sql = "update ${db_prefix}$table_name set $changed_field = null where $id_field = " . $row_id;

					if ($row_id) SqlQuery($sql);
					continue;
				}
				if ($is_meta){
					if (! isset($meta_table_info)) return false;
					$sql = "update $tbl set " . $meta_table_info[$tbl]['value'] . "=?" .
					       " where " . $meta_table_info[$tbl]['key'] . "=? " .
					       " and " . $meta_table_info[$tbl]['id'] . "=?";
				}
				else
					$sql = "update ${db_prefix}$table_name set $changed_field =? where $id_field =?";

//				 if (get_user_id() == 1) print $sql;
				$stmt = SqlPrepare($sql);
				if (! $stmt) return false;
				if ($is_meta){
					if (! SqlBind($tbl, $stmt,
						array($meta_table_info[$tbl]['value'] => $changed_value,
						      $meta_table_info[$tbl]['key'] => $changed_field,
						      $meta_table_info[$tbl]['id'] => $row_id))) return false;
				} else {
					if ( ! SqlBind($table_name, $stmt,
						array(
							$changed_field          => $changed_value,
							SqlTableId($table_name) => $row_id
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
		$args["field"] = $field;
		$args["include_id"] = $include_id;

		$data .= Core_Html::TableDatalist($datalist, $table_name, $args);

		return $data;
	}

	static function TableHeader($sql, $args) // $add_checkbox = false, $skip_id = false, $meta_fields = null)
	{
		$header_fields = GetArg($args, "header_fields", null);

		$skip_id = GetArg($args, "skip_id", false);

		// Option a - build from given header_fields.
		if ($header_fields) {
			if ($fields = GetArg($args, "fields", null)){
				$result = [];
				$fields = Core_Fund::array_assoc($fields);
				foreach ($fields as $field => $v) {
					if ($as_pos = strpos($field, " as ")) {
						$field = substr($field, $as_pos + 4);
					}
					if ( (! $skip_id or strtolower( $field ) !== "id") and !isset($args["hide_cols"][$field]))
						$result[ $field ] = ETranslate( ( isset( $header_fields[ $field ] ) ? $header_fields[ $field ] : $field ) );
				}
			} else {
				$result = $header_fields;
			}
			if (GetArg($args, "add_checkbox", false)) array_unshift($result, "");
			return $result;
		}

		$result = SqlQuery( $sql );

		if (! $result)
			return null;

		$headers = array();
		if (GetArg($args, "add_checkbox", false)) $headers["select"] = ETranslate("select");

		// If not sent, create from database fields.
		if (strstr($sql, "describe") or strstr($sql, "show"))
		{
			while ($row = SqlFetchRow($result))
			{
				if (! $skip_id or strtolower($row[0]) !== "id" and !isset($args["hide_cols"])) {
					$headers[$row[0]] = ETranslate($row[0]);
					// array_push($headers, im_translate($row[0]));
				}
			}
		} else { // Select
			$i      = 0;
			$fields = mysqli_fetch_fields( $result );
			foreach ( $fields as $val ) {
				if ((! $skip_id or strtolower($val->name) !== "id") and !isset($args["hide_cols"][$val->name])) {
					$headers [$val->name] = ETranslate($val->name);
				}
				$i ++;
			}
		}

		if ($extra_header = GetArg($args, "extra_header", null)){
			foreach ($extra_header as $e )
				array_push($headers, $e);
		}
//	if ($meta_fields and is_array($meta_fields))
//	{
//		foreach ($meta_fields as $f)
//			array_push($headers, $f);
//	}
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

	static function PrepareRow($row, &$args, $row_id)
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
		$accumulation_row = GetArg($args, "accumulation_row", null);
		$events = GetArg($args, "events", null);

		$table_name = GetArg($args, "table_name", null);

		$prepare_plug = GetArg($args, "prepare_plug", null);
		if (is_callable($prepare_plug)) $row = call_user_func($prepare_plug, $row, $args);

		$row = apply_filters("prepare_$table_name", $row);

		$row_data = array();

		if (! is_array($row))
		{
			return $row;
		}

		$field_args = $args;

		foreach ($row as $key => $data)
		{
			if (isset($row[$key])) $data = $row[$key];

			// General preparation... decide the field name and save the orig data and default data.
			$nm = $key; // mnemonic3($key);
			if ($add_field_suffix and $row_id)	$input_name = $nm . '_' . $row_id;
			else $input_name = $nm;

			$orig_data = $data;
			$value = self::prepare_text($data); // Default;
			if (strtolower($key) == "id" and $skip_id) continue;

//          Option for events specific to col. Not tested.
//			if (is_array($maybe_array_events)) { // If it's array check if the right col has events.
//				if ( isset( $maybe_array_events[ $key ] ) ) {
//					$events = $maybe_array_events[ $key ];
//				} else {
//					$events = null;
//				}
//			}

			if ($events) {
				if ($transpose)	$field_events = sprintf($events, "'" . $key . "'", $row_id);
				else			$field_events = sprintf( $events, $row_id, $key );

                $field_args["events"] = $field_events;
			}

			// Let's start
			do {
				if ($links and ! is_array($links))	die ("links should be array");
				if ( $links and  array_key_exists( $key, $links )) {
					if ( $selectors and array_key_exists( $key, $selectors ) ) {
						$selector_name = $selectors[ $key ];
						$selected = $selector_name( $input_name, $orig_data, $field_args ); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
					} else $selected = $value;

					$value = Core_Html::GuiHyperlink($selected, sprintf( urldecode($links[ $key ]), $data ), $args );
					break;
				}
				if ($selectors and array_key_exists( $key, $selectors ) ) {
					$selector_name = $selectors[ $key ];
					if ( strlen( $selector_name ) < 2 ) die( "selector " . $key . "is empty" );
					// print $selector_name;
//					if ($key == "team") dd ($args);
					$value = $selector_name( $input_name, $orig_data, $field_args );
					// $value = (function_exists($selector_name) ? $selector_name( $input_name, $orig_data, $args ) : $orig_data); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
//					if (! function_exists($selector_name)) {
//						print( "function $selector_name does not exists" );
//						print debug_trace();
//						die(1);
//					}
					if ($drill) {
						$value = Core_Html::GuiHyperlink($value, AddToUrl(array( $key => $orig_data)), $args);
					}
					break;
				}

				/// 5/9/2019 Change!! edit_cols by default is to edit. if it set, don't edit.
				/// 23/9/2019  isset($edit_cols[$key]) - set $args["edit_cols"][$key] for fields that need to be edit.
				if ($edit){
					// Copy the global args to field args. And than handle specific col args.
					// $field_args = $args; Set previously
					if (isset($args["size"]) and is_array($args["size"]))
						$field_args["size"] = (isset($args["size"][$key]) ? $args["size"][$key] : null);
//					if (isset($args["events"])) $field_args["events"] = $args["events"];

					if (! $key or $key == "id")	continue;
					// Not tested:
					//							if (isset($args['styles']) and is_array($args['styles']))
					//								$args['style'] = (isset($args['styles'][$key]) ? $args['styles'][$key] : null);
//							print "edit $key " . $field_args["edit"] . "<br/>";
					if (! $edit_cols or (isset($edit_cols[$key]) and $edit_cols[$key])) {
						if ( $table_name or isset($args["types"][$key])) { // Create by type
							if ( isset( $args["sql_fields"] ) ) {
								$type = SqlField( $args["sql_fields"], $key );
							} else {
								$type = $args["types"][ $key ];
							}
//							$field_args["checkbox_class"] = $args["checkbox_class"];
							$value = Core_Html::gui_input_by_type( $input_name, $type, $field_args, $value );
						} else {
							$value = Core_Html::GuiInput( $input_name, $data, $field_args ); //gui_input( $key, $data, $field_events, $row_id);
						}
						// Last change context: https://fruity.co.il/wp-admin/admin.php?page=deliveries&order_id=18584

					}
//					else {
//						// ??? 30/3/2020
//						// $value = Core_Html::GuiInput( $input_name, $data, $args ); //gui_input( $key, $data, $field_events, $row_id);
//					}
					break;
				}
				// Looks like the block above.
//				if ( $selectors and array_key_exists( $key, $selectors )) {
//					$selector_name = $selectors[ $key ];
//					if ( strlen( $selector_name ) < 2 ) die( "selector " . $key . "is empty" );
//					//////////////////////////////////
//					// Selector ($id, $value, $args //
//					//////////////////////////////////
//					if (function_exists($selector_name))
//						$value = $selector_name( $key, $orig_data, $args); //, 'onchange="update_' . $key . '(' . $row_id . ')"' );
//					else
//						$value = "selector $selector_name not found";
//					break;
//				}
				// Format values by type.
				if (isset($args["sql_fields"]) and ! isset($args["no_html"])){
					$type = SqlField($args["sql_fields"], $key);
//				print $key . " " . substr($type, 0, 3) . "<br/>";
					switch (substr($type, 0, 3)) {
						case 'tim':
							$value = substr($value, 0, 5);
							break;
						case 'tin':
//							print "value=$value<Br/>";
							$value  = Core_Html::GuiCheckbox("", $value, array("edit"=>false));
							break;

					}
				}
			} while (0);

			$row_data[$key] = $value;

			if ($accumulation_row and isset($accumulation_row[$key][1]) and is_callable($accumulation_row[$key][1])) {
				$accumulation_row[ $key ][1]( $accumulation_row[ $key ][0], $value );
			}
		}

		if ($actions){
			// Action types:
			// server;client: Run something in the server and update the display. Execute url is used to run the server action and later the javascript action.
			// url: Show as hyperlink
			foreach ($actions as $action_name => $action) {
				if (is_array($action))
				{
					$text = $action[0];
					if ($s = strpos($action[1], ';')) { // We have javascript command.
						$server_action = substr($action[1], 0, $s);
						$action_url = sprintf($server_action, $row_id);
						$client_action = substr($action[1], $s + 1);
						if (! $client_action) $client_action = "''";
//						print "CA=$client_action<br/>";
						$btn_id = "btn_$text" . "_" . $row_id . "_$action_name";
						$action_args = array("action" => "execute_url('" . $action_url . "', $client_action, $btn_id )");
						if (isset($action[2])) $action_args["class"] = $action[2];
						if (isset($action[3])) $action_args["tooltip"] = $action[3];
						$row_data[$action_name] = Core_Html::GuiButton($btn_id, $text, $action_args);
					} else {
//						$action_url = $row_id . $action[1];
						if (! $row_id) $action_url = ($action[1] . "row id missing");
						else $action_url = sprintf($action[1], $row_id);
						$row_data[$action_name] = Core_Html::GuiHyperlink($text, $action_url, $args);
					}
				} else {
					$h = sprintf($action, $row_id);
					$row_data[$action_name] = $h;
				}
			}
		}

		if ($accumulation_row) $args["accumulation_row"] = $accumulation_row;
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

	static function RowsData($sql, $id_field, $skip_id, bool $v_checkbox, $checkbox_class, $h_line, $v_line, $m_line, $header_fields, $meta_fields, $meta_table, &$args)
	{
		$result = SqlQuery( $sql );

		if (! $result) return null;
		if ($args) $args["sql_fields"] = mysqli_fetch_fields($result);
		$row_count = 0;
		$rows_data = [];
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$the_row = array();
			if (isset($row[strtoupper($id_field)])) $id_field = strtoupper($id_field);
			if ( ! isset( $row[ $id_field ] ) ) {
				// Error... We don't have a valid row ID.
				print "<br/>id_field:" . $id_field . "<br/>";
				var_dump( $row );
				print "<br/>";
				print $sql . "<br/>";
				print debug_trace(1);
				die( __FUNCTION__ . ":" . __LINE__ . "no id_field" );
			}
			$row_id = $row[ $id_field ];
//			print "row_id=$row_id<br/>";

			foreach ( $row as $key => $cell ) {
				// Change: 9/9/2019. We put the id only in multirow display
				if ( ! $skip_id or strtolower( $key ) !== "id" ) {
					$the_row[ $key ] = $cell;
					// print "adding $key $cell<br/>";
					// array_push( $the_row, $cell );
				}
				if ( $v_checkbox ) {
					if ( ! $skip_id or ( $key != $id_field ) ) {
						$checked = false;
						if (GetArg($args, "duplicate_of", false)) $checked = ($cell != '');
						$v_line[ $key ] = Core_Html::GuiCheckbox( "chk_" . $key, $checked, array("checkbox_class"=>$checkbox_class));
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
//			print $row_count . "<br/>";

			if ( $meta_fields and is_array( $meta_fields ) ) {
				foreach ( $meta_fields as $meta_key ) {
					$meta_value = SqlQuerySingleScalar( "select meta_value from " . $meta_table . " where " . $meta_key_field . " = $row_id " .
					                                    " and meta_key = " . QuoteText( $meta_key ) );

					$key             = $meta_table . '/' . $meta_key;
					$the_row[ $key ] = $meta_value;

					if ( $v_checkbox ) {
						$v_line[ $key ] = Core_Html::GuiCheckbox( "chk_" . $key, false , array("checkbox_class" => $checkbox_class) );
					}
					if ( $h_line ) {
						// print "adding $key<br/>";
//					print "trans " . $header_fields[$key] . "<br/>";
						$h_line[ $key ] = ETranslate( $header_fields[ $key ++ ], $args );
					}
				}
			}
			if ($v_line) $rows_data["checkbox"] = $v_line;
			if ($h_line) $rows_data['header'] = $h_line;
			if ($m_line) { $rows_data["mandatory"] = $m_line; $args["hide_rows"]["mandatory"] = 1; }

			$rows_data[$row_id] = $the_row;
		}
		if ( ! $result ) { print "Error #N1"; return null;	}

		if ($args and strstr($sql, "limit"))
		{
			$from_pos = strpos(strtolower($sql), " from ");
			$limit_or_order_pos = min(strpos(strtolower($sql), "limit"), strpos(strtolower($sql), "order"));
			$args["row_count"] = SqlQuerySingleScalar("select count(*) " . substr($sql, $from_pos, $limit_or_order_pos - $from_pos));
		}
		return $rows_data;
	}

	static function NewRowData($field_list, $values, &$v_line, &$h_line, &$m_line, $skip_id, $checkbox_class, $header_fields, $fields, &$args )
	{
		$new_row = array();

		$mandatory_fields = GetArg($args, "mandatory_fields", null);
		if ($mandatory_fields) $mandatory_fields = Core_Fund::array_assoc($mandatory_fields);

		// assert(0); // will fire
		// assert (! isset($field_list[0]), "field list in seq array" );
		foreach ( $field_list as $key => $field ) {
			if (strstr($key, " as ")) continue;
//			if (get_user_id() == 1) print "key=$key<br/>";
//
			if (  $skip_id and strtolower($key) === "id" ) continue;
			assert( isset( $field_list[ $key ] ) );

			if ( $values and isset( $values[ $key ] ) ) $new_row[ $key ] = $values[ $key ];
			else $new_row[ $key ] = null;

			if ( $v_line !== null ) $v_line[ $key ] = Core_Html::GuiCheckbox( "chk_" . $key, $new_row[ $key ] != null, array("checkbox_class" => $checkbox_class) );
			if ( is_array( $h_line ) and ! isset($h_line[$key])) $h_line[$key] = $key;   ////* and $header_fields */) $h_line[ $key ] = isset( $header_fields[ $key ] ) ? im_translate( $header_fields[ $key ], $args ) : $key;
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
		if (strstr($sql, "select") and !strstr ($sql, "limit")){
			$rows_per_page = GetArg($args, "rows_per_page", null);
			if ($rows_per_page) {
				$page = GetArg($args, "page_number", 1);
				$offset = ($page - 1) * $rows_per_page;

				$limit = (($page > -1) ? " limit $rows_per_page offset $offset" : "");
				$sql .= $limit;
			}
		}

		$header = GetArg($args, "header", true);
		$field_list = self::FieldList($sql, $args);
		$mandatory_fields = GetArg($args, "mandatory_fields", null);  $mandatory_fields = Core_Fund::array_assoc($mandatory_fields);
		$fields = GetArg($args, "fields", null);  $fields = Core_Fund::array_assoc($fields);
		$skip_id = GetArg($args, "skip_id", false);
		$meta_fields = GetArg($args, "meta_fields", null);
		$meta_table = GetArg($args, "meta_table", null);
		$meta_key_field = GetArg($args, "meta_key", "id");
		$values = GetArg($args, "values", null);
		$v_checkbox = GetArg($args, "v_checkbox", false);
		$checkbox_class = GetArg($args, "checkbox_class", "checkbox_class");
		$header_fields = GetArg($args, "header_fields", null);	 $header_fields = Core_Fund::array_assoc($header_fields);

		$table_names = array();
		if (preg_match_all("/from ([^ ]*)/" , $sql, $table_names))
		{
			$table_name = $table_names[1][0];
			$args["table_name"] = substr($table_name, 3); // Assume prefix is two letters with _
			$id_field = GetArg($args, "id_field", "id" /* long executing: sql_table_id($table_name) */);
		} else {
			$id_field = GetArg($args, "id_field", "id");
		}

		$h_line = ($header ? self::TableHeader( $sql, $args ) : null);

		$m_line = $mandatory_fields ? array() : null;

		$v_line = $v_checkbox ? array() : null;

		if (strstr($sql, "describe") || strstr($sql, "show col")) // New Row
		{
			$rows_data = self::NewRowData( $field_list, $values, $v_line, $h_line, $m_line, $skip_id, $checkbox_class, $header_fields, $fields, $args );
		} else {
			// print "before: "; var_dump($h_line); print "<br/>";
			$rows_data = self::RowsData($sql, $id_field, $skip_id, $v_checkbox, $checkbox_class, $h_line, $v_line, $m_line, $header_fields, $meta_fields, $meta_table, $args);

			// print "after: "; var_dump($h_line); print "<br/>";
		}

		if (! $rows_data or ! count($rows_data)) return null;

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
		$fields = GetArg($args, "fields", null);
		if (isset($fields[0])) $fields = Core_Fund::array_assoc($fields);
		if ($fields) return $fields;

		$fields = array();

		$skip_id = GetArg($args, "skip_id", false);
		$id_field = GetArg($args, "id_field", "id");

		$result = SqlQuery($sql);
		if (strstr($sql, "describe") or strstr($sql, "show cols")){
			while ( $row = mysqli_fetch_assoc( $result ) ) {
				$key = $row['Field'];
				if (! $skip_id or ($key != $id_field)) $fields[$key] = 1;
			}
		} else {
			$row = SqlFetchAssoc($result);
//			print "$id_field $skip_id<br/>";
			if ($row) foreach ($row as $key => $cell) if (! $skip_id or ($key != $id_field)) $fields[$key] = 1;
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
		foreach ($row as $key => $cell)
		{
			if (isset($acc_fields[$key]) and is_array($acc_fields[$key]) and function_exists($acc_fields[$key]['func'])) {
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
	static function data_search($table_name, $args = null)
	{
		$table_prefix = GetTablePrefix();

		$result = null;
		$query_array = GetArg($args, "query_array", null); //Core_Data::data_parse_get($table_name, $ignore_list);

		$id_field = GetArg($args, "id_field", "id");
		$sql = "select $id_field from ${table_prefix}$table_name where 1 ";
		$count = 0;

		$params = array();

		foreach ($query_array as $tbl => $changed_values)
		{
			foreach ($changed_values as $field => $pair){
				$is_meta = $pair[1]; if ($is_meta) die("$field: not implemeted yet: is_meta=" . $pair[1]);

				$sql .= " and $field =? "; // " . quote_text($changed_value);
				$count ++;
				$params[$field] = $pair[0];
//			if ($is_meta){
//				if (! isset($meta_table_info)) return false;
//				$sql = "update $tbl set " . $meta_table_info[$tbl]['value'] . "=?" .
//				       " where " . $meta_table_info[$tbl]['key'] . "=? " .
//				       " and " . $meta_table_info[$tbl]['id'] . "=?";
//			}
//			else
//				$sql = "update $table_name set $changed_field =? where id =?";

				// print $sql;
			}
//		print $sql; print "<br/>";

			$stmt = SqlPrepare($sql);
			SqlBind( $tbl, $stmt, $params);
			if (! $stmt->execute())
			{
				return "no results";
			}
			$id = 0;
			$stmt->bind_result($id);

			$result = array();
			while ($stmt->fetch()){
				$result[] = $id;
			}
		}
		return $result;
	}

	static function UpdateTableFieldEvent($post_file, $table_name, $id, $field_name)
	{
		return "XXXX";
		// 'update_table_field(\'' . $post_file . '\', \'' . $table_name . "', $id, '$field_name', check_result)";
	}

	static function data_auto_list() {
		$prefix = GetParam( "prefix", true );

		// Todo: add in classes
		$lists = array(
			"products"          => array(
				"table"      => "im_products",
				"field_name" => 'post_title',
				"include_id" => 0,
				"id_field"   => "ID"
			),
			"products_w_drafts" => array( "table"      => "im_products_w_drafts",
			                              "field_name" => 'post_title',
			                              "include_id" => 0,
			                              "id_field"   => "ID"
			),
			"tasks"             => array( "table"      => "im_tasklist",
			                              "field_name" => "task_title",
			                              "include_id" => 1,
			                              "id_field"   => "id",
			                              "query"      => " status = 0"
			),
			"users"             => array( "table" => "wp_users", "field_name" => "display_name", "id_field" => "ID" ),
			"categories"        => array( "table"      => "im_categories",
			                              "include_id" => 0,
			                              "id_field"   => "term_id",
			                              "field_name" => "name"
			)
		);

		$list = GetParam( "list", true );
		if ( ! isset( $lists[ $list ] ) ) {
			die ( "Error: unknown list " . $list );
		}
		$table_name = $lists[ $list ]["table"];
		$field      = $lists[ $list ]["field_name"];

		$args             = $lists[ $list ];
		$args["datalist"] = $list . "_list";

		print Core_Data::auto_list( $table_name, $field, $prefix, $args );

		return true;
	}
}