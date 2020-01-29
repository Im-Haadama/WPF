<?php



if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ) );
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

init();
require_once( "data.php" );

$operation = GetParam("operation", false, null);

if ($operation){
	require_once(FRESH_INCLUDES . '/im-config.php');
	require_once( FRESH_INCLUDES . '/init.php' );

	handle_data_operation($operation);
	return;
}
