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

	/**
	 * @return string
	 */

	/**
	 * @param $style_file
	 *
	 * @return string
	 */

	/**
	 * @param $key
	 *
	 * @param bool $mandatory
	 * @param null $default
	 *
	 * @param string $delimiter
	 *
	 * @return array|null
	 */

// Return reference is useful for summing table, e.g.


	/**
	 * @param $num_or_text
	 *
	 * @param bool $sql_escape
	 *
	 * @return string
	 */


	/**
	 * @param $url
	 * @param $addition
	 *
	 * @return string
	 */


	/**
	 * @param mixed ...$elems
	 *
	 * @return array|string
	 */

