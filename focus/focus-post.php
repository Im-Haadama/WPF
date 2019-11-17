<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

init();
require_once("focus.php");

$operation = get_param("operation", false, null);
$args = [];

if ($operation){
	handle_focus_operation($operation, $args);
	return;
}

