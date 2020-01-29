<?php



require_once( "boot.php" );
require_once( "bank.php" );

// Specific files.
require_once( FRESH_INCLUDES . "/core/web.php" );

// Params
$debug = GetParam( "debug", false, false );
$operation = GetParam("operation", false, "show_transactions");

if ($debug) print "op=$operation<br/>";

print load_scripts(array("/core/data/data.js", "/core/gui/client_tools.js"));

if ($operation) {
	handle_bank_operation($operation);
	return;
}
