<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

require_once( "routes.php" );

$operation = get_param("operation", false, "show_routes");

if (($result = handle_routes_do($operation)) !== "not handled") { print $result; return; }

print header_text(true, true, true, array("routes.js", "/core/data/data.js", "/core/gui/client_tools.js"));

print handle_routes_show($operation);

?>