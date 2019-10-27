<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname(dirname( dirname( __FILE__)  ) ));
}

require_once(ROOT_DIR . "/niver/gui/inputs.php");

$ignore_list = array("search", "operation", "table_name", "id", "dummy");

function data_parse_get($table_name) {
	global $ignore_list;
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

function update_data($table_name)
{
	// TODO: adding meta key when needed(?)
	global $meta_table_info;

	$row_id = intval(get_param("id", true));

	// Prepare sql statements: primary and meta tables;
	$values = data_parse_get($table_name);

	foreach ($values as $tbl => $changed_values)
	{
		foreach ($changed_values as $changed_field => $changed_pair){
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

			// print $sql;
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

function cancel_data($table_name)
{
	// TODO: adding meta key when needed(?)
	global $meta_table_info;

	$row_id = intval(get_param("id", true));

	return sql_query("update $table_name set is_active = 0 where id = $row_id");
}

function data_save_new($table_name)
{
	global $ignore_list;
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

	$id = sql_insert_id();
	return $id;
}

// For now use escape_string and not bind. Uncaught Error: Call to undefined method mysqli_stmt::get_result
function data_search($table_name, $args = null)
{
	$result = null;
	$values = data_parse_get($table_name);

	$id_field = GetArg($args, "id_field", "id");
	$sql = "select $id_field from $table_name where 1 ";
	$count = 0;

	$params = array();

	foreach ($values as $tbl => $changed_values)
	{
		foreach ($changed_values as $field => $pair){
			$is_meta = $pair[1]; if ($is_meta) die("not implemeted yet");

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

		$stmt = sql_prepare($sql);
		sql_bind($tbl, $stmt, $params);
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

function handle_data_operation($operation)
{
	$allowed_tables = array("im_company", "im_tasklist", "im_task_templates", "im_working_teams", "im_projects");

	$debug = 0;
	if ($debug)	print "operation: " . $operation . "<br/>";
	switch ($operation){
		case "save_new":
			init();
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			$result = data_save_new($table_name);
			if ($result > 0) print "done";
			break;

		case "update":
			$table_name = get_param("table_name", true);
			if (update_data($table_name))
				print "done";
			break;

		case "auto_list":
			$prefix = get_param("prefix", true);

			$lists = array("products" => array("table" => "im_products", "field_name" =>'post_title', "include_id" => 1, "id_field" => "ID"),
				"tasks" => array("table"=>"im_tasklist", "field_name" => "task_description", "include_id" => 1, "id_field" => "id", "query" => " status = 0"));
			$list = get_param("list", true);
			if (! isset($lists[$list])) die ("Error: unknown list " . $list);
			$table_name = $lists[$list]["table"];
			$field = $lists[$list]["field_name"];

			$args = $lists[$list];

			print auto_list($table_name, $field, $prefix, $args);
			break;

		default:
			print __FUNCTION__ . ": " . $operation . " not handled <br/>";

			die(1);
	}
	return;
}

//($table_name, $field, $prefix, $args);
function auto_list($table_name, $field, $prefix, $args = null)
{
	$data = "";

	// print "field=$field<br/>";
	if (!$args) $args = [];
	$id_field = GetArg($args, "id_field", "id");
	$include_id = GetArg($args, "include_id", false);

	$args["sql"] = "select $id_field, $field from $table_name where $field like '%" . $prefix . "%'";
	$query = GetArg($args, "query", null); 	if ($query) $args["sql"] .= " and " . $query;
	print $args["sql"] . "<br/>";
	$args["field"] = $field;
	$args["include_id"] = $include_id;
	$data .= GuiDatalist($table_name . "_$prefix", $table_name, $args );

	return $data;
}

function set_args_value(&$args, $ignore_list = null)
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