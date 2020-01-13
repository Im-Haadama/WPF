<?php

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ) );
}
require_once(FRESH_INCLUDES . '/im-config.php');
require_once(FRESH_INCLUDES . "/init.php" );
require_once(FRESH_INCLUDES . "/fresh/orders/orders.php");

$operation = GetParam("operation", false, "show_order_form");

if ($operation) {
	$args = [];
	if (GetParam("page", false, null)) $args ["page"] = GetParam("page");
	if (get_user_id()) {
		handle_order_operation($operation, $args);
		return;
	}
	 handle_anonymous_operation($operation, $args);
	return;
}

