<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:19
 */

//if (! function_exists('sql_insert_id')) {

// TODO: move meta table information to somewhere else. Maybe read from database.
$meta_table_info = array();
$meta_table_info["wp_usermeta"] = array("id" => "user_id", "key" => "meta_key", "value" => "meta_value");

/**
 * @return int|string
 */
function SqlInsertId() {
	$conn = GetSqlConn();

	return mysqli_insert_id( $conn );
}

/**
 * @param null $new_conn
 *
 * @return null
 */
function GetSqlConn($new_conn = null)
{
	static $conn;

	if ($new_conn)
		$conn = $new_conn;

	return $conn;
}

/**
 * @param $table
 * @param $field
 *
 * @return mixed|string
 * @throws Exception
 */
function SqlType( $table, $field) {
	$db_prefix = GetTablePrefix($table);

	global $meta_table_info;

	// Meta fields are all varchar
	if (substr($field, 0, 1) == "$")
		return 'varchar';

	if (! $table) throw new Exception("No table given");
	if (! $field) throw new Exception(__CLASS__ . ":". __METHOD__ . "No field given.<br/> " . debug_trace());

	// For meta fields:
	if ($sl = strpos($field, '/')){
		$table = substr($field, 0, $sl);
		$field = $meta_table_info[$table]['value'];
	}
	static $type_cache = array();

	if ( ! isset( $type_cache[ $table ][ $field ] ) ) {
		$result               = SqlQuery( "describe ${db_prefix}$table" );
		$type_cache[ $table ] = array();
		while ( $row = SqlFetchRow( $result ) ) {
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
function SqlPrepare($sql)
{
	if (! $sql)
		return false;
	$conn = GetSqlConn();

	if (!$conn)
		die("not connected to db");

	$stmt = $conn->prepare($sql);
	if ($stmt)
		return $stmt;
	SqlError("prepare $sql failed");
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
function SqlBind($table_name, &$stmt, $_values)
{
//	print "table: $table_name<br/>";
	$debug = 0;//  (get_user_id() == 1);
	$types = "";
	$values = array();
	foreach ($_values as $key => $value){
		if (! $key) { print "key is empty" . debug_trace(5); die (1);}
		if ($debug) print "binding $value to $key <br/>";
		$type = SqlType($table_name, $key);
		switch(substr($type, 0, 3))
		{
			case 'bit':
			case "int":
			case "big":
			case 'tin':
				$types .= "i";
				$value = ((strlen($value) > 0) ? $value : null);
				break;
			case "var":
			case 'lon':
			case 'tim':
			case "med":
				$types .= "s";
				// $value = escape_string($value);
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
//	if (get_user_id() == 1) var_dump($values);

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
		case 9:
			return $stmt->bind_param($types, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7], $values[8]);
			break;
		case 10:
			return $stmt->bind_param($types, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7], $values[8], $values[9]);
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
function SqlQuery( $sql, $report_error = true, $set_encoding = true )
{
	try {
		$conn = GetSqlConn();
	} catch ( Exception $e ) {
		print "Error (2): " . $e->getMessage() . "<br/>";
		die(1);
	}
	if ( ! $conn ) {
		SqlError("Error (3): not connected");
		print debug_trace(10);
		return null;
	}
	if ($set_encoding) {
//		print "setenq<br/>";
		$from_pos = strpos(strtolower($sql), " from ");
		if ($from_pos) {
			$table_name = strtok( substr( $sql, $from_pos + 6 ), " " );

			SqlSetEncoding( $conn, $table_name );
		}
	}
	$prev_time         = microtime(true);
	if ( $result = mysqli_query( $conn, $sql ) ) {
		$now         = microtime(true);
		$micro_delta = $now - $prev_time;
		if ( $micro_delta > 0.1 ) {
			$report = debug_trace();
			$report .= "long executing: " . $sql . " " . $micro_delta . "<br>";
			MyLog($report, "sql performance", "sql_performance" . date('m-j'));
		}
		return $result;
	}
	if ( $report_error ) SqlError( $sql );
	return null;
}

/**
 * @param $sql
 * @param bool $header
 *
 * @return array|string
 */
function SqlQueryArray( $sql, $header = false ) {
	$result = SqlQuery( $sql );
	if ( ! $result ) {
		SqlError( $sql );

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
function SqlQuerySingleScalar( $sql, $report_error = true, $set_enc = true ) {
	$result = SqlQuery( $sql, $report_error, $set_enc );
	if ( ! $result ) {
		if ( $report_error ) {
			SqlError( $sql );
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
	if (! $arr)
		return null;

	return $arr[0];
}

/**
 * @param $table
 *
 * @return bool
 */
function TableExists( $table ) {
	$db_prefix = GetTablePrefix($table);
	$sql = 'SELECT 1 FROM ' . $db_prefix .$table . ' LIMIT 1';
//	print $sql;
	return SqlQuery( $sql, false) != null;
}

/**
 * @param $string
 *
 * @return string
 */
function EscapeString( $string ) {
	$conn = GetSqlConn();

	return mysqli_real_escape_string( $conn, $string );
}

/**
 * @return int
 */
function SqlAffectedRows()
{
	$conn = GetSqlConn();

	return mysqli_affected_rows($conn);
}
// Good for sql that returns array of one value
/**
 * @param $sql
 *
 * @return array
 * @throws Exception
 */
function SqlQueryArrayScalar( $sql ) {
	$arr    = array();
	$result = SqlQuery( $sql );
	if ( ! $result ) {
		SqlError( $sql );

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
function SqlQuerySingle( $sql ) {
	$result = SqlQuery( $sql );
	if ( $result ) {
		return mysqli_fetch_row( $result );
	}
	SqlError( $sql );

	return null;
}

/**
 * @param $sql
 *
 * @return string[]|null
 */
function SqlQuerySingleAssoc( $sql ) {
	$result = SqlQuery( $sql );
	if ( $result ) {
		return mysqli_fetch_assoc( $result );
	}
	SqlError( $sql );

	return null;
}
/**
 * @param $sql
 */
function SqlError( $sql ) {
	try {
		$conn = GetSqlConn();
	} catch ( Exception $e ) {
		print "Error (1): " . $e->getMessage() . "<br/>";
		return;
	}
	if ( is_string( $sql ) ) {
		$message = "Error: sql = `" . $sql;
		if ($conn) $message .= "`. Sql error : " . mysqli_error( $conn ) . "<br/>";
		else $message .= "not connected";
		print debug_trace(6);
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
function SqlFetchRow( $result ) {
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
function SqlFetchAssoc( $result ) {
	if ($result) return mysqli_fetch_assoc( $result );
	return null;
}


/**
 * @param $query
 * @param $p
 */
function AddQuery( &$query, $p ) {
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
function SqlSetTimeOffset()
{
	$now = new DateTime();
	$mins = $now->getOffset() / 60;
	$sgn = ($mins < 0 ? -1 : 1);
	$mins = abs($mins);
	$hrs = floor($mins / 60);
	$mins -= $hrs * 60;
	$offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);

	// print "offset= " . $offset . "<br/>";

	SqlQuery("SET time_zone='$offset';");
}

/**
 * @param $table_name
 *
 * @return string
 */
function SqlTableId($table_name)
{
	$db_prefix = GetTablePrefix();
	// Performance issues. For now hardcoded used tables.
	$cache = array ("tasklist" => "id",
	                "working_teams" => "id",
	                "task_templates" => "id",
				 "conversion" => "id",
				 "woocommerce_shipping_zones" => "zone_id",
	                "wp_woocommerce_shipping_zone_methods" => "zone_id",
	                "supplier_price_list" => "ID",
	                "working" => "id");
	if (isset($cache[$table_name])) return $cache[$table_name];

	$sql = "SELECT COLUMN_NAME 
		FROM information_schema.KEY_COLUMN_USAGE 
		WHERE TABLE_NAME = '${db_prefix}$table_name' 
		  AND CONSTRAINT_NAME = 'PRIMARY'";
	$rc = SqlQuerySingleScalar($sql);

	if (! $rc) die("no primary key for table $table_name");
	return $rc;
}


function SqlField($fetch_fields, $field_name)
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

//}

function SqlQueryAssoc($sql)
{
	$result = SqlQuery( $sql );
	if ( ! $result ) {
		SqlError( $sql );

		return "Error";
	}
	$rows = array();
	while ( $row = mysqli_fetch_assoc( $result ) ) {
		array_push( $rows, $row );
	}

	return $rows;

}

function GetTablePrefix($table_name = null)
{
	global $table_prefix;
	if ($table_name){
		if (strstr($table_name, "woocommerce")) return $table_prefix;
		if (strstr($table_name, "options")) return $table_prefix;
		if (strstr($table_name, "users")) return $table_prefix;
	}
	global $im_table_prefix;
	return ($im_table_prefix ? $im_table_prefix : "im_");
}

function SqlTableFields($table_name) {
	$fields = [];
	$result = SqlQuery( "describe im_" . $table_name );
	while ( $row = SqlFetchRow( $result ) ) {
		array_push( $fields, $row[0] );
	}

	return $fields;
}

function SqlInsert($table_name, $array, $ignore_list)
{
	$debug = false;
	$db_prefix = GetTablePrefix();

	$sql    = "INSERT INTO ${db_prefix}$table_name (";
	$values = "values (";
	$first  = true;
	$sql_values = array();
	foreach ( $array as $key => $value ) {
		if ( in_array( $key, $ignore_list ) ) continue;

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

	$conn = GetSqlConn();
	SqlSetEncoding($conn, "${db_prefix}$table_name", $debug);

	$stmt = SqlPrepare($sql);
	SqlBind($table_name, $stmt, $sql_values);
	if (!$stmt -> execute())
		SqlError($sql);

	return SqlInsertId();
}

function GetTableEncoding($table) {
	static $cache = null;
	if ( isset( $cache[ $table ] ) ) return $cache[ $table ];

	if ( ! $cache ) $cache = [];
	$cache[$table] = SqlQuerySingleScalar("SELECT CCSA.character_set_name FROM information_schema.`TABLES` T,
       information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
	WHERE CCSA.collation_name = T.table_collation
          AND T.table_name = '$table'", false, false);
//	MyLog("$table $cache[$table]");
	return $cache[$table];
	//      AND T.table_schema = \"schemaname\"
}

function SqlSetEncoding($conn, $table_name, $debug = false)
{
//	return;
	$enq = GetTableEncoding($table_name);
	if ($debug) print "setting enq $enq table $table_name<br/>";
//	MyLog($table_name . " $enq");

	mysqli_set_charset($conn, $enq);
}
