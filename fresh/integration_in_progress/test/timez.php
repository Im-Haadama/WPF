<?php





require_once( "../r-shop_manager.php" );


var_dump (SqlQuerySingle("SELECT @@global.time_zone, @@session.time_zone")); print  "<br/>";

print "time=" . SqlQuerySingleScalar("select curtime()") . "<br/>";
print SqlQuerySingleScalar("select task_active_time(2424)");
