<?php

if (! function_exists('data_inactive')) {
// $ignore_list = array("search", "operation", "table_name", "id", "dummy");

function data_inactive($table_name)
{
	// TODO: adding meta key when needed(?)
	global $meta_table_info;

	$row_id = intval(get_param("id", true));

	return sql_query("update $table_name set is_active = 0 where id = $row_id");
}

function data_delete($table_name, $row_may_ids)
{
	// TODO: adding meta key when needed(?)
	if (is_array($row_may_ids)) {
		foreach ( $row_may_ids as $id )
			if ( ! data_delete( $table_name, $id ) ) return false;
		return true;
	}
	$sql = "delete from $table_name where id = $row_may_ids";
//	print $sql;
	if (! sql_query($sql)) return false;
	return true;
}

function data_save_new($table_name)
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

	$id = sql_insert_id();
	return $id;
}

// For now use escape_string and not bind. Uncaught Error: Call to undefined method mysqli_stmt::get_result
function data_search($table_name, $args = null)
{
	$result = null;
	$ignore_list = GetArg($args, "ignore_list", array("search", "operation", "table_name", "id", "dummy"));
	$values = data_parse_get($table_name, $ignore_list);

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
	// TODO: register allowed tables by config or something
	$allowed_tables = array("im_company", "im_tasklist", "im_task_templates", "im_working_teams", "im_projects", "im_bank_transaction_types", "im_business_info");

	$debug = 0;
	if ($debug)	print "operation: " . $operation . "<br/>";
	switch ($operation){
		case "cancel":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			if (data_inactive($table_name))
				print "done";
			break;
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
	$datalist = GetArg($args, "datalist", null);

	$args["sql"] = "select $id_field, $field from $table_name where $field like '%" . $prefix . "%'";
	$query = GetArg($args, "query", null); 	if ($query) $args["sql"] .= " and " . $query;
	// print $args["sql"] . "<br/>";
	$args["field"] = $field;
	$args["include_id"] = $include_id;

	$data .= TableDatalist($datalist, $table_name, $args);

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
}