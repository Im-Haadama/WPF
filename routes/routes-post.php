<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );


require_once( "routes.php" );

$operation = get_param("operation", false, null);
$debug = get_param("debug", false, false);

if (($result = handle_routes_do($operation)) !== "not handled") { print $result; return; }

die ("not handled");