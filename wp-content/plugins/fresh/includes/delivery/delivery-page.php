<?php

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) )) ;
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

require_once( "delivery.php" );

$debug = get_param("debug", false, false);

print header_text(true, true, is_rtl(), array("/core/data/data.js", "/core/gui/client_tools.js"));

$operation = get_param("operation", false, "show_this_week");

if ($operation) {
	handle_delivery_operation($operation);
	return;
}
