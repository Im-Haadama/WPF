<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once("boot.php");
require_once( "bank.php" );

// Specific files.
require_once( ROOT_DIR . "/niver/web.php" );

// Params
$debug = get_param( "debug", false, false );
$operation = get_param("operation", false, "focus_main");

if ($debug) print "op=$operation<br/>";

print load_scripts(array("/niver/data/data.js", "/niver/gui/client_tools.js"));

if ($operation) {
	handle_bank_operation($operation);
	return;
}
