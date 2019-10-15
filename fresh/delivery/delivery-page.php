<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) )) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

require_once( "delivery.php" );

$debug = get_param("debug", false, false);

print header_text(true, true, is_rtl(), array("/niver/data/data.js", "/niver/gui/client_tools.js"));

$operation = get_param("operation", false, "show_this_week");

if ($operation) {
	handle_delivery_operation($operation);
	return;
}
