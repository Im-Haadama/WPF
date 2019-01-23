<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/11/16
 * Time: 17:57
 */
require_once( '../r-shop_manager.php' );

$date = "2016-10-16";

$datetime = new DateTime( $date );
$interval = new DateInterval( "P" . $datetime->format( "w" ) . "D" );
$datetime->sub( $interval );
print $datetime->format( "Y-m-d" );


//$date -> sub(new DateInterval(""))
//
//print date('Y-m-d', mktime(0, 0, 0, $month, date("d")-date("w"), $year));
