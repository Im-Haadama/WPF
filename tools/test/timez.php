<?php


error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "../r-shop_manager.php" );


var_dump (sql_query_single("SELECT @@global.time_zone, @@session.time_zone")); print  "<br/>";

print "time=" . sql_query_single_scalar("select curtime()") . "<br/>";
print sql_query_single_scalar("select task_active_time(2424)");
