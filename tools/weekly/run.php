<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/08/18
 * Time: 04:37
 */

require_once( "../im_tools.php" );

$key = "weekly_run";

// TODO: Check permission

ob_start();
$create_time = date( "Y-m-d H:m" );
print $create_time;

$log = ob_end_clean();

print "run started " . $create_time . "<br/>";


print "run ended " . date( "H:m" );
$file_name = "../logs/" . $create_time;
print "results saved to " . $file_name . "<br/>";
$file = fopen( $file_name, "w" );
fwrite( $file, $log );

info_update( $key, $create_time );

