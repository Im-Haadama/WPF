<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( '../r-shop_manager.php' );
require_once("../../tools/account/gui.php");

$args = array();
//print gui_select_client("client", null, $args);
//print gui_select_project("project", 1, $args);
// print gui_select_task("task", 1, $args);
debug_time_output("before");
print gui_select_worker("aa", null, null);
debug_time_output("after");

