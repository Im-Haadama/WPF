<?php

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( $_SERVER["SCRIPT_FILENAME"])))). '/');
}

require_once("finance.php");
print "b";
$f = Finance::instance();
var_ddump($f);
// 16631
//phpinfo();
//var_dump(get_loaded_extensions());
//if(! in_array('curl', get_loaded_extensions())) {
////	print phpinfo();
//	die ("curl not loaded");
//}

curl_init();

