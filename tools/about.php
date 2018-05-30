<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/04/18
 * Time: 10:42
 */

// require_once("im_tools_light.php");

// print "document root: " .  $_SERVER['DOCUMENT_ROOT'] . "<br/>";

function convert( $size ) {
	$unit = array( 'b', 'kb', 'mb', 'gb', 'tb', 'pb' );

	return @round( $size / pow( 1024, ( $i = floor( log( $size, 1024 ) ) ) ), 2 ) . ' ' . $unit[ $i ];
}

echo convert( memory_get_usage( true ) ); // 123 kb

// print "memory usage: " . memory_get_usage() . "<br/>";

phpinfo();