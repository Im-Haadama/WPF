<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
var_dump(php_ini_loaded_file(), php_ini_scanned_files());

print  xdebug_info();
