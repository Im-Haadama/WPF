<?php

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ) );
}
require_once(ROOT_DIR . '/im-config.php');
require_once(ROOT_DIR . "/init.php" );
require_once(ROOT_DIR . "/fresh/orders/orders.php");

$operation = get_param("operation", false, "show_order_form");

if ($operation) {
	$args = [];
	if (get_param("page", false, null)) $args ["page"] = get_param("page");
	if (get_user_id()) {
		handle_order_operation($operation, $args);
		return;
	}
	handle_anonymous_operation($operation, $args);
	return;
}

