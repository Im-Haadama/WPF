<?php

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( $_SERVER["SCRIPT_FILENAME"])))). '/');
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(ABSPATH . 'wp-config.php');

//$flavor = Flavor::instance();

$p = new E_Fresh_Payment_Gateway();
$p->test();

print "TEST";