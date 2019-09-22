<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/04/18
 * Time: 10:42
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

// require_once("im_tools_light.php");
require_once("r-multisite.php");

// print "document root: " .  $_SERVER['DOCUMENT_ROOT'] . "<br/>";

function convert( $size ) {
	$unit = array( 'b', 'kb', 'mb', 'gb', 'tb', 'pb' );

	return @round( $size / pow( 1024, ( $i = floor( log( $size, 1024 ) ) ) ), 2 ) . ' ' . $unit[ $i ];
}

print $power_version;

//echo '$_SERVER[\'SERVER_ADDR\'] = ' . $_SERVER['SERVER_ADDR'] . "<br/>";
//
//// print "memory usage: " . memory_get_usage() . "<br/>";21092141,18972121
//
//print "OK!<br/>";