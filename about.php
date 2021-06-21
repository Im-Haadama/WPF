<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "wp-load.php";
require_once "wp-includes/ms-functions.php";
require_once "wp-admin/includes/ms.php";

print "allowed space: " . get_space_allowed() . "<br/>";
print "used: " . display_space_usage() . "<br/>";
print phpinfo();
//print xdebug_info();