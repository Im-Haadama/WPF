<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:19
 */

require_once( "im_tools.php" );

function sql_query( $sql ) {
	global $conn;

	if ( $result = mysqli_query( $conn, $sql ) ) {
		return $result;
	} else {
		sql_error( $sql );

		return null;
	}
}

function sql_query_array( $sql ) {
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

		return "Error";
	}
	$rows = array();
	while ( $row = mysqli_fetch_row( $result ) ) {
		array_push( $rows, $row );
	}

	return $rows;
}

// Good for sql that return just one value
function sql_query_single_scalar( $sql ) {
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

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
