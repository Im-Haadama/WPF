<?php

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( $_SERVER["SCRIPT_FILENAME"])))). '/');
}

require_once(ABSPATH . 'wp-config.php');

//$flavor = Flavor::instance();

$p = new E_Fresh_Payment_Gateway();
$p->test();

print "TEST";