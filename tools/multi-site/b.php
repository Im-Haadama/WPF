<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/01/17
 * Time: 20:32
 */

require_once( "../tools.php" );

//var_dump($conn);
$sql    = "SELECT count(*) FROM wp_posts";
$result = sql_query( $sql );
$row    = mysqli_fetch_row( $result );
print $row[0];

$servername1 = "aglamaz.com";
$username1   = "ihstore";
$password1   = "SuziSuzi40!";
$dbname1     = "ihstore";

$conn = new mysqli( $servername1, $username1, $password1, $dbname1 );
