<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once(ROOT_DIR . "/tools/im_tools.php");
require_once( '../r-shop_manager.php' ); // for authentication

$operation = get_param("operation", true);

// http://fruity.co.il/tools/admin/data.php?table_name=im_business_info&operation=update&id=2560&ref=22
$table_name = get_param("table_name", true);
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

// TODO: Check permission to table

$ignore_list = array("operation", "table_name", "id");

if ( $operation )
	switch ( $operation ) {
		case "new":
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

			// print $sql;
			// TODO: use prepare https://stackoverflow.com/questions/60174/how-can-i-prevent-sql-injection-in-php

			if ( ! $conn->query( $sql ) ) {
				print "Sql: " . $sql . "<br>" . mysqli_error( $conn );
			}
			print "done";
			break;

		case "update":
			if (update_data($table_name, $ignore_list))
				print "done";
			break;

		default:
			print "no operation handler for $operation<br/>";
			die( 2 );
	}

function update_data($table_name, $ignore_list)
{
	// TODO: adding meta key when needed(?)
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
			if ($is_meta[$tbl]){
				if (! isset($meta_table_info)) return false;
				$sql = "update $tbl set " . $meta_table_info[$tbl]['value'] . "=?" .
				 " where " . $meta_table_info[$tbl]['key'] . "=? " .
				" and " . $meta_table_info[$tbl]['id'] . "=?";
			}
			else
				$sql = "update $table_name set $changed_field =? where id =?";

			$stmt = sql_prepare($sql);
			if (! $stmt) return false;
			if ($is_meta[$tbl]){
				if (! sql_bind($sql, $tbl, $stmt,
					array($meta_table_info[$tbl]['value'] => $changed_value,
						$meta_table_info[$tbl]['key'] => $changed_field,
						$meta_table_info[$tbl]['id'] => $row_id))) return false;
			} else {
				if ( ! sql_bind( $sql, $table_name, $stmt,
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
		if (in_array($key, $ignore_list))
			continue;
		$type = sql_type($table_name, $key);
		switch(substr($type, 0, 3))
		{
			case 'bit':
			case "int":
				$types .= "i";
				$value = strlen($value) ? $value : null;
				break;
			case "var":
				$types .= "s";
				break;
			case "dat":
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