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

function sql_insert_id() {
	global $conn;

	return mysqli_insert_id( $conn );
}

function sql_type( $table, $field ) {
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

	return $type_cache[ $table ][ $field ];

}
function sql_query( $sql, $report_error = true ) {
	global $conn;

	if ( ! $conn ) {
		die ( "not connected!<br/>" );
	}

	if ( $result = mysqli_query( $conn, $sql ) ) {
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
	$result = sql_query_single_scalar( 'SELECT 1 FROM ' . $table . ' LIMIT 1', false);

	return $result == 1;
}

function escape_string( $string ) {
	global $conn;

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

function sql_error( $sql ) {
	$debug = debug_backtrace();
	for ( $i = 2; $i < 6 && $i < count( $debug ); $i ++ ) {
		print "called from " . $debug[ $i ]["function"] . ":" . $debug[ $i ]["line"] . "<br/>";
	}
	global $conn;
	if ( is_string( $sql ) ) {
		$message = "Error: sql = `" . $sql . "`. Sql error : " . mysqli_error( $conn ) . "<br/>";
	} else {
		$message = $sql->error;
		// $message = "sql not string";
	}
	my_log( $message );
	print "<div style=\"direction: ltr;\">" . $message . "</div>";
}

function sql_fetch_row( $result ) {
	return mysqli_fetch_row( $result );
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