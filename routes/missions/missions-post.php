<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname(dirname( dirname( __FILE__)  ) ));
}

require_once("Mission.php");
require_once(ROOT_DIR . "/niver/fund.php");

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );


$operation = get_param("operation", false, null);
if ($operation){

	handle_mission_operation($operation);
	return;
}



