<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );


require_once( "routes.php" );

$operation = GetParam("operation", false, null);
$debug = GetParam("debug", false, false);

if (($result = handle_routes_do($operation)) !== "not handled") { print $result; return; }

die ("not handled");