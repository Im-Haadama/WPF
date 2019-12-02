<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ) );
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

init();
require_once("data.php");

$operation = get_param("operation", false, null);

if ($operation){
	require_once(ROOT_DIR . '/im-config.php');
	require_once(ROOT_DIR . '/init.php');

	handle_data_operation($operation);
	return;
}
