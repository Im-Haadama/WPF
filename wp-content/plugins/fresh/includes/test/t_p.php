<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );



//$args = array();
////print gui_select_client("client", null, $args);
////print gui_select_project("project", 1, $args);
//// print gui_select_task("task", 1, $args);
////debug_time_output("before");
////print gui_select_worker("aa", null, null);
////debug_time_output("after");
//
//
//print sql_table_id("wp_users");

$username = "xx";
$password = "yyy";
$url = "https://fruity.co.il/fresh/r-multisite.php";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
$output = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

print $output . "<br/>";

print "done";