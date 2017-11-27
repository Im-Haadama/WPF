<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/10/17
 * Time: 20:04
 */

//print date( "w" ) . "<br/>";
//print date( "N" ) . "<br/>";

require_once( "../im_tools.php" );

$r = sql_query_single_scalar( "SELECT MIN(DATEDIFF(CURDATE(), DATE(date)))  FROM im_tasklist
										WHERE task_template = 10 " );

if ( is_null( $r ) ) {
	print "n";
}
// var_dump($r);