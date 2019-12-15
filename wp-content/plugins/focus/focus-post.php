<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

init();
require_once( "focus_class.php" );

$operation = get_param("operation", false, null);
$args = [];

if ($operation){
	print handle_focus_show($operation, $args);
	return;
}

