<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

require_once( "routes.php" );

$operation = get_param("operation", false, "show_routes");

if (($result = handle_routes_do($operation)) !== "not handled") { print $result; return; }

print header_text(true, true, true, array("routes.js", "/niver/data/data.js", "/niver/gui/client_tools.js"));

print handle_routes_show($operation);

?>