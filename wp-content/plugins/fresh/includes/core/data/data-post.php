<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ) );
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

init();
require_once( "data.php" );

$operation = get_param("operation", false, null);

if ($operation){
	require_once(FRESH_INCLUDES . '/im-config.php');
	require_once( FRESH_INCLUDES . '/init.php' );

	handle_data_operation($operation);
	return;
}
