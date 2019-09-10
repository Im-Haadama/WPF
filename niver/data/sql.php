<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:19
 */

if ( ! function_exists( "my_log" ) ) {
	require_once( ROOT_DIR . "/niver/fund.php" );
}

// TODO: move meta table information to somewhere else. Maybe read from database.
$meta_table_info = array();
$meta_table_info["wp_usermeta"] = array("id" => "user_id", "key" => "meta_key", "value" => "meta_value");

function sql_insert_id() {
	$conn = get_sql_conn();

	return mysqli_insert_id( $conn );
}

//function sql_express_type($sql, $expression)
//{
//	if (! $sql)
//		throw new Exception("No sql give");
//
//	if (! $expression)
//		throw new Exception("No express");
//
//	static $type_cache = array();
//
//	if (isset($type_cache[$expression]))
//		return $type_cache[$expression];
//
//	// Parse the sql and save the types.
//	// if (! strpos(strtolower($sql), "limit")) $sql .= " limit 1"; // We want just the types.
//
//	$result = sql_query($sql);
//	$c = mysqli_num_fields($result);
//	for ($i = 0; $i < $c; $i++){
//		$t = mysqli_fetch_field_direct($result, $i);
//		try {
//			$type_cache[ $t->name ] = sql_type( $t->orgtable, $t->orgname );
//		} catch ( Exception $e ) {
//		}
//	}
//
//	if (isset($type_cache[$expression]))
//		return $type_cache[$expression];
//
//	return "none";
//
//}
function sql_type( $table, $field ) {
	global $meta_table_info;

	if (! $table)
		throw new Exception("No table given");

	if (! $field)
		throw new Exception(__CLASS__ . ":". __METHOD__ . "No field given.<br/> " . sql_trace());
	//	print "checking $table $field<br/>";
	// For meta fields:
	if ($sl = strpos($field, '/')){
		$table = substr($field, 0, $sl);
		$field = $meta_table_info[$table]['value'];
	}
	static $type_cache = array();

	if ( ! isset( $type_cache[ $table ][ $field ] ) ) {
		$result               = sql_query( "describe $table" );
		$type_cache[ $table ] = array();
		while ( $row = sql_fetch_row( $result ) ) {
			$f = $row[0];
			$t = $row[1];
//			print $f . " " . $t . "<br/>";
			$type_cache[ $table ][ $f ] = $t;
		}
	}

	if (! isset($type_cache[ $table ][ $field ])){
		throw new Exception("unknown field $field in table $table");
	}
	return $type_cache[ $table ][ $field ];
}

function sql_prepare($sql)
{
	if (! $sql)
		return false;
	$conn = get_sql_conn();

	$stmt = $conn->prepare($sql);
	if ($stmt) return $stmt;
	sql_error("prepare $sql failed");
}

function sql_bind($table_name, &$stmt, $_values)
{
//	print "table: $table_name<br/>";
	$debug = 0;
	$types = "";
	$values = array();
	foreach ($_values as $key => $value){
		if ($debug) print "binding $value to $key <br/>";
		$type = sql_type($table_name, $key);
		switch(substr($type, 0, 3))
		{
			case 'bit':
			case "int":
			case "big":
				$types .= "i";
				$value = $value ? $value : null;
				break;
			case "var":
			case 'lon':
				$types .= "s";
				$value = escape_string($value);
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

	switch (count($values))
	{
		case 1:
			return $stmt->bind_param($types, $values[0]);
			break;
		case 2:
			return $stmt->bind_param($types, $values[0], $values[1]);
			break;
		case 3:
			return $stmt->bind_param($types, $values[0], $values[1], $values[2]);
			break;
		default:
			throw new Exception("number of arguments not implemented");
	}
	return true;
}

function sql_query( $sql, $report_error = true ) {
	try {
		$conn = get_sql_conn();
	} catch ( Exception $e ) {
		print "Error (2): " . $e->getMessage() . "<br/>";
		die(1);
	}

	if ( ! $conn ) {
		sql_error("Error (3): not connected");
	}

	$prev_time         = microtime(true);
	if ( $result = mysqli_query( $conn, $sql ) ) {
		$now         = microtime(true);
		$micro_delta = $now - $prev_time;
		if ( $micro_delta > 0.1 ) {
			$report = sql_trace();
			$report .= "long executing: " . $sql . " " . $micro_delta . "<br>";
			my_log($report, "sql performace");
		}
		return $result;
	} else {
		if ( $report_error ) {
			sql_error( $sql );
		}

		return null;
	}
}

function sql_query_array( $sql, $header = false ) {
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

		return "Error";
	}
	$rows = array();
	if ( $header ) {
		$headers = mysqli_fetch_fields( $result );
		$h       = array();
		foreach ( $headers as $val ) {
			array_push( $h, $val->name );
		}
		array_push( $rows, $h );
	}
	while ( $row = mysqli_fetch_row( $result ) ) {
		array_push( $rows, $row );
	}

	return $rows;
}

// Good for sql that return just one value
function sql_query_single_scalar( $sql, $report_error = true ) {
	$result = sql_query( $sql, $report_error );
	if ( ! $result ) {
		if ( $report_error ) {
			sql_error( $sql );
		}

		return "Error";
	}
	// print gettype($result) . "<br/>";
	if ( gettype( $result ) != "object" ) {
		var_dump( $result );
		print "<br/>";
		print "bad result. sql= $sql<br/>";
		print "result = $result<br/>";
		die( 2 );
	}
	$arr = mysqli_fetch_row( $result );

	return $arr[0];
}

function table_exists( $table ) {
	$sql = 'SELECT 1 FROM ' . $table . ' LIMIT 1';
//	print $sql;
	return sql_query( $sql, false) != null;
//	print "r= $result<br/>";

//	return $result == 1;
}

function escape_string( $string ) {
	$conn = get_sql_conn();

	return mysqli_real_escape_string( $conn, $string );
}

// Good for sql that returns array of one value
function sql_query_array_scalar( $sql ) {
	$arr    = array();
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

		return "Error";
	}
	while ( $row = mysqli_fetch_row( $result ) ) {
		array_push( $arr, $row[0] );
	}

	return $arr;
}

// Good fom sql that returns one record (an array is returned)
function sql_query_single( $sql ) {
	$result = sql_query( $sql );
	if ( $result ) {
		return mysqli_fetch_row( $result );
	}
	sql_error( $sql );

	return null;
}

function sql_query_single_assoc( $sql ) {
	$result = sql_query( $sql );
	if ( $result ) {
		return mysqli_fetch_assoc( $result );
	}
	sql_error( $sql );

	return null;
}

function sql_trace()
{
	$result = "";
	$debug = debug_backtrace();
	for ( $i = 2; $i < 6 && $i < count( $debug ); $i ++ ) {
		$result .= ("called from " . $debug[$i]['file'] . " " . $debug[ $i ]["function"] . ":" . $debug[ $i ]["line"] . "<br/>");
	}
	return $result;
}

function sql_error( $sql ) {
	try {
		$conn = get_sql_conn();
	} catch ( Exception $e ) {
		print "Error (1): " . $e->getMessage() . "<br/>";
	}
	if ( is_string( $sql ) ) {
		$message = "Error: sql = `" . $sql;
		if ($conn) $message .= "`. Sql error : " . mysqli_error( $conn ) . "<br/>";
		else $message .= "not connected";
		print sql_trace();
	} else {
		$message = $sql->error;
		// $message = "sql not string";
	}
	my_log( $message );
	print "<div style=\"direction: ltr;\">" . $message . "</div>";
}

function sql_fetch_row( $result ) {
	if ($result)
		return mysqli_fetch_row( $result );
	else
		return null;
}

function sql_fetch_assoc( $result ) {
	return mysqli_fetch_assoc( $result );
}


function add_query( &$query, $p ) {
	if ( ! $query ) {
		$query = "Where ";
	} else {
		$query .= " and ";
	}
	$query .= $p;
}

function sql_set_time_offset()
{
	$now = new DateTime();
	$mins = $now->getOffset() / 60;
	$sgn = ($mins < 0 ? -1 : 1);
	$mins = abs($mins);
	$hrs = floor($mins / 60);
	$mins -= $hrs * 60;
	$offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);

	// print "offset= " . $offset . "<br/>";

	sql_query("SET time_zone='$offset';");
}

function sql_table_id($table_name)
{
	return sql_query_single_scalar("SELECT COLUMN_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = '$table_name' 
  AND CONSTRAINT_NAME = 'PRIMARY'");
}