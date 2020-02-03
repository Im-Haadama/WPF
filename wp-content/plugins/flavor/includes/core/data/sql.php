<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:19
 */

if (! function_exists('sql_insert_id')) {

// TODO: move meta table information to somewhere else. Maybe read from database.
$meta_table_info = array();
$meta_table_info["wp_usermeta"] = array("id" => "user_id", "key" => "meta_key", "value" => "meta_value");

/**
 * @return int|string
 */
function sql_insert_id() {
	$conn = get_sql_conn();

	return mysqli_insert_id( $conn );
}

/**
 * @param null $new_conn
 *
 * @return null
 */
function get_sql_conn($new_conn = null)
{
	static $conn;

	if ($new_conn)
		$conn = $new_conn;

	return $conn;
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
/**
 * @param $table
 * @param $field
 *
 * @return mixed|string
 * @throws Exception
 */
function sql_type( $table, $field) {
	global $meta_table_info;

	// Meta fields are all varchar
	if (substr($field, 0, 1) == "$")
		return 'varchar';

	if (! $table) throw new Exception("No table given");
	if (! $field) throw new Exception(__CLASS__ . ":". __METHOD__ . "No field given.<br/> " . sql_trace());

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
			$type_cache[ $table ][ $f ] = $t;
		}
	}

	if (! isset($type_cache[ $table ][ $field ])){
		throw new Exception("unknown field $field in table $table");
	}
	return $type_cache[ $table ][ $field ];
}

/**
 * @param $sql
 *
 * @return bool
 */
function sql_prepare($sql)
{
	if (! $sql)
		return false;
	$conn = get_sql_conn();

	if (!$conn)
		die("not connected to db");

	$stmt = $conn->prepare($sql);
	if ($stmt)
		return $stmt;
	sql_error("prepare $sql failed");
	die(1);
}

/**
 * @param $table_name
 * @param $stmt
 * @param $_values
 *
 * @return bool
 * @throws Exception
 */
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
				$value = ((strlen($value) > 0) ? $value : null);
				break;
			case "var":
			case 'lon':
			case 'tim':
				$types .= "s";
				$value = escape_string($value);
				break;
			case "dat":
				$types .= "s";
				$_values[$key] = date('Y/m/d', strtotime($_values[$key]));
				break;
			case "dou":
			case "flo":
				$types .= "d";
				break;
			default:
				print $type . " not handled";
				die(1);
		}
		array_push($values, $value);
	}
	// var_dump($values);

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
		case 4:
			return $stmt->bind_param($types, $values[0], $values[1], $values[2], $values[3]);
			break;
		case 5:
			return $stmt->bind_param($types, $values[0], $values[1], $values[2], $values[3], $values[4]);
			break;
		case 6:
			return $stmt->bind_param($types, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5]);
			break;
		case 7:
			return $stmt->bind_param($types, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6]);
			break;
		case 8:
			return $stmt->bind_param($types, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7]);
			break;

		default:
			var_dump($values);
			throw new Exception("number of arguments not implemented:" . count($values));
	}
	return true;
}

/**
 * @param $sql
 * @param bool $report_error
 *
 * @return bool|mysqli_result|null
 */
function sql_query( $sql, $report_error = true )
{
	try {
		$conn = get_sql_conn();
	} catch ( Exception $e ) {
		print "Error (2): " . $e->getMessage() . "<br/>";
		die(1);
	}
	if ( ! $conn ) {
		sql_error("Error (3): not connected");
		return null;
	}
	$prev_time         = microtime(true);
	if ( $result = mysqli_query( $conn, $sql ) ) {
		$now         = microtime(true);
		$micro_delta = $now - $prev_time;
		if ( $micro_delta > 0.1 ) {
			$report = sql_trace();
			$report .= "long executing: " . $sql . " " . $micro_delta . "<br>";
			MyLog($report, "sql performance", "sql_performance" . date('m-j'));
		}
		return $result;
	}
	if ( $report_error ) sql_error( $sql );
	return null;
}

/**
 * @param $sql
 * @param bool $header
 *
 * @return array|string
 */
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
/**
 * @param $sql
 * @param bool $report_error
 *
 * @return string
 */
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

/**
 * @param $table
 *
 * @return bool
 */
function table_exists( $table ) {
	$sql = 'SELECT 1 FROM ' . $table . ' LIMIT 1';
//	print $sql;
	return sql_query( $sql, false) != null;
//	print "r= $result<br/>";

//	return $result == 1;
}

/**
 * @param $string
 *
 * @return string
 */
function escape_string( $string ) {
	$conn = get_sql_conn();

	return mysqli_real_escape_string( $conn, $string );
}

/**
 * @return int
 */
function sql_affected_rows()
{
	$conn = get_sql_conn();

	return mysqli_affected_rows($conn);
}
// Good for sql that returns array of one value
/**
 * @param $sql
 *
 * @return array
 * @throws Exception
 */
function sql_query_array_scalar( $sql ) {
	$arr    = array();
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

		throw new Exception("cant fetch");
	}
	while ( $row = mysqli_fetch_row( $result ) ) {
		array_push( $arr, $row[0] );
	}

	return $arr;
}

// Good fom sql that returns one record (an array is returned)
/**
 * @param $sql
 *
 * @return array|null
 */
function sql_query_single( $sql ) {
	$result = sql_query( $sql );
	if ( $result ) {
		return mysqli_fetch_row( $result );
	}
	sql_error( $sql );

	return null;
}

/**
 * @param $sql
 *
 * @return string[]|null
 */
function sql_query_single_assoc( $sql ) {
	$result = sql_query( $sql );
	if ( $result ) {
		return mysqli_fetch_assoc( $result );
	}
	sql_error( $sql );

	return null;
}

/**
 * @return string
 */
function sql_trace($deep = 2)
{
	$result = "";
	$debug = debug_backtrace();
	for ( $i = 1; ($i < $deep) and ($i < count( $debug )); $i ++ ) {
		if (isset($debug[$i]['file'])) $caller = "called from " . $debug[$i]['file'] . " ";
		else $caller = "";
		if (isset($debug[ $i ]["line"])) $line = ":" . $debug[ $i ]["line"];
		else $line = "";
		$result .= '#' . $i . ' ' .( $caller . $debug[ $i ]["function"] . $line . "<br/>");
	}
	return $result;
}

/**
 * @param $sql
 */
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
	MyLog( $message );
	print "<div style=\"direction: ltr;\">" . $message . "</div>";
}

/**
 * @param $result
 *
 * @return array|null
 */
function sql_fetch_row( $result ) {
	if ($result)
		return mysqli_fetch_row( $result );
	else
		return null;
}

/**
 * @param $result
 *
 * @return string[]|null
 */
function sql_fetch_assoc( $result ) {
	if ($result) return mysqli_fetch_assoc( $result );
	return null;
}


/**
 * @param $query
 * @param $p
 */
function add_query( &$query, $p ) {
	if ( ! $query ) {
		$query = "Where ";
	} else {
		$query .= " and ";
	}
	$query .= $p;
}

/**
 * @throws Exception
 */
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

/**
 * @param $table_name
 *
 * @return string
 */
function sql_table_id($table_name)
{
	// Performance issues. For now hardcoded used tables.
	$cache = array ("im_tasklist" => "id", "im_working_teams" => "id", "im_task_templates" => "id", "im_working" => "id");
	if (isset($cache[$table_name])) return $cache[$table_name];

	return sql_query_single_scalar("SELECT COLUMN_NAME 
		FROM information_schema.KEY_COLUMN_USAGE 
		WHERE TABLE_NAME = '$table_name' 
		  AND CONSTRAINT_NAME = 'PRIMARY'");
}


function sql_field($fetch_fields, $field_name)
{
	foreach($fetch_fields as $field) {
//		print $field->name . " " . $field->type . "<br/>";
		if ($field->name == $field_name) {
//			debug_var($field->name . " " . $field->type);
			$length = $field->length;
//			print "len = $length <br/>";
			switch ($field->type){
				case 1:
					return "tinyint($length)";
				case 3:
					return "int($length)";
				case 4:
				case 5:
					return "float($length)";
				case 8:
					return "long($length)";
				case 10:
					return "date($length)";
				case 11:
					return "time($length)";
				case 12:
					return "datetime($length)";
				case 16:
					return "bit($length)";
				case 252:
					return "longtime($length)";
				case MYSQLI_TYPE_VAR_STRING:
					return "varchar($length)";
				default:
					die(__FUNCTION__ . ":" . $field->type . " not handled " . $field_name);
			}
		}
	}
}

}

function SqlQueryAssoc($sql)
{
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

		return "Error";
	}
	$rows = array();
	while ( $row = mysqli_fetch_assoc( $result ) ) {
		array_push( $rows, $row );
	}

	return $rows;

}