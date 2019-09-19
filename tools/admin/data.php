<?php

$ignore_list = array("operation", "table_name", "id");

function update_data($table_name)
{
	// TODO: adding meta key when needed(?)
	global $ignore_list;
	global $meta_table_info;

	// Prepare sql statements: primary and meta tables;
	$values = array();
	$row_id = intval(get_param("id", true));
	$is_meta = array();
	foreach ( $_GET as $key => $value ) {
		if (in_array($key, $ignore_list))
			continue;
		$tbl = $table_name;
		$field = $key;
		$meta = false;
		if ($st = strpos($key, "/")){
			$tbl = substr($key, 0, $st);
			$field = substr($key, $st + 1);
			$meta = true;
		}
		if (! isset($values[$tbl])){
			$values[$tbl] = array();
			$is_meta[$tbl] = $meta;
		}

		$values[$tbl][$field] = $value;
	}

	foreach ($values as $tbl => $changed_values)
	{
		foreach ($changed_values as $changed_field => $changed_value){
			if (sql_type($table_name, $changed_field) == 'date' and substr($changed_value, "0001")) {
				if ($row_id) sql_query("update $table_name set $changed_field = null where id = " . $row_id);
				continue;
			}
			if ($is_meta[$tbl]){
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
			if ($is_meta[$tbl]){
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
	die(1);
	$sql .= " where id=$row_id";

	print $sql;
	die(1);
	$stmt = $conn->prepare($sql);
	if (! $stmt) {
		die ($conn->error);
	}

	// Bind
	$types = "";
	$values = array();
	foreach ( $_GET as $key => $value ) {
		if (! $key)
			continue;
		if (in_array($key, $ignore_list))
			continue;
		try {
			$type = sql_type( $table_name, $key );
		} catch ( Exception $e ) {
			return new \Exception(__CLASS__ . ":" . __METHOD__ . "can't find type of " . $key);
		}
		switch(substr($type, 0, 3))
		{
			case 'bit':
			case "int":
				$types .= "i";
				$value = strlen($value) ? $value : null;
				break;
			case "dat":
			case "var":
				$types .= "s";
				break;
			case "dou":
				$types .= "d";
				break;
			default:
				print $type . " not handled";
				die(1);
		}
		array_push($values, $value);
	}

	if (! $stmt->bind_param($types, ...$values))
	{
		die("bind error" . sql_error($sql));
	};

	if (!$stmt->execute()) {
		print "Update failed: (" . $stmt->errno . ") " . $stmt->error . " " . $sql;
		die(2);
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