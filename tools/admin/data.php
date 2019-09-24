<?php

$ignore_list = array("search", "operation", "table_name", "id");


function data_parse_get($table_name) {
	global $ignore_list;
	$debug = (1== get_user_id());
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
			// Todo: bind vars here.
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

function data_save_new($table_name)
{

	global $ignore_list;
	$sql    = "INSERT INTO $table_name (";
	$values = "values (";
	$first  = true;
	foreach ( $_GET as $key => $value ) {
		if (in_array($key, $ignore_list))
			continue;
		if ( ! $first ) {
			$sql    .= ", ";
			$values .= ", ";
		}
		$sql    .= $key;
		$values .= "\"" . $value . "\"";
		$first  = false;
	}
	$sql    .= ") ";
	$values .= ") ";
	$sql    .= $values;

//	print $sql;

	// print $sql;
	// TODO: use prepare https://stackoverflow.com/questions/60174/how-can-i-prevent-sql-injection-in-php

	try {
		if ( ! sql_query($sql ) ) {
			die(1);
		}
	} catch ( Exception $e ) {
		print "error: " . $e->getMessage();
	}

	return sql_insert_id();
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