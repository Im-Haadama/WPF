<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/06/16
 * Time: 06:02
 */

define( '__ROOT__', dirname( dirname( __FILE__ ) ) );

require( '../config.php' );

function uptime_log( $msg, $title = '' ) {
	$error_file = STORE_DIR . '/logs/uptime.log';
	$date       = date( 'd.m.Y h:i:s' );
	$msg        = print_r( $msg, true );
	$log        = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
}

$link = mysql_connect( $servername, $username, $password );

if ( ! $link ) {
	print "connection failed\n";
	uptime_log( "connect faild" . mysql_error() );
}

mysql_set_charset( 'utf8', $link );
mysql_select_db( $dbname );

$sql = 'SELECT count(*) FROM wp_posts';

$export = mysql_query( $sql );

if ( ! $export ) {
	uptime_log( "export failed" . mysql_error() );
}

$row = mysqli_fetch_row( $result );

uptime_log( $row[0] );

?>
