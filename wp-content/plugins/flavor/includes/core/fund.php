<?php

// Core functions.
// Todo: Split file to functions that are not required in WordPress enviromnent -  e.g. load_scripts.

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/05/18
 * Time: 07:30
 *
 * @param $msg
 * @param string $title
 * @param null $file - default is php_error.log
 */

	function MyLog( $msg, $title = '', $file = null ) {
		$error_file = ABSPATH . 'logs/' . ( $file ? $file : 'php_error.log' );
//    print $error_file;
		$date = date( 'd.m.Y h:i:s' );
		$msg  = print_r( $msg, true );
		$log  = $date . ": " . $title . "  |  " . $msg . "\n";
		error_log( $log, 3, $error_file );
	}

function CommaArrayExplode( $string_array ) {
	if ( ! $string_array ) {
		return null;
	}
	$string_array = str_replace( "::", ":", $string_array );
	$teams        = array();
	$t            = [];

	while ( strlen( $string_array ) > 1 ) {
		$p    = strpos( $string_array, ":", 1 );
		$team = substr( $string_array, 1, $p - 1 );
		// print "Team: $team<br/>";

		$t[] = $team;
//		print "p=$p<br/>";
		if ( $team > 0 ) {
			array_push( $teams, $team );
		}
		$string_array = substr( $string_array, $p );
	}

	return $t;
}

function DebugVar($var)
{
	if (get_user_id() != 1) return;
	print debug_trace();
	var_dump($var);
}

function debug_trace($deep = 2)
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
