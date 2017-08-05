<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/06/17
 * Time: 08:02
 */
require_once( "../../config.php" );
$target = $_GET["target"];
$sql    = "INSERT INTO im_activity (time, ip, ref, target) VALUES (" .
          " now(), " .
          tag_st( $_SERVER['REMOTE_ADDR'] ) . ", " .
          tag_st( $_SERVER['HTTP_REFERER'] ) . ", " .
          tag_st( $target ) . ")";
$conn   = new mysqli( $servername, $username, $password, $dbname );
mysqli_query( $conn, $sql );
header( 'Location: ' . $target );
function tag_st( $str ) {
	return "'" . $str . "'";
}
