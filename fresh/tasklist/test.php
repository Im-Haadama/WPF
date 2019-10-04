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

//$r = sql_query_single_scalar( "SELECT MIN(DATEDIFF(CURDATE(), DATE(date)))  FROM im_tasklist
//										WHERE task_template = 10 " );
//
//if ( is_null( $r ) ) {
//	print "n";
//}
// var_dump($r);

require_once( "Tasklist.php" );

create_tasks_per_mission();
print "done";
exit;

//print gui_select_task_related( "aa", null, "", 234 );
//print gui_select_project( "x", null, null );