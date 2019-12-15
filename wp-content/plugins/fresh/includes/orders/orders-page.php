<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once(FRESH_INCLUDES . "/init.php" );
require_once("Order.php");
//require_once(FRESH_INCLUDES . "/focus/gui.php");
//require_once(FRESH_INCLUDES . "/org/gui.php");
//require_once(FRESH_INCLUDES . "/routes/gui.php");

if (! get_user_id()) {
	force_login();
	return;
}

require_once("new-order.php");

$operation = get_param("operation", false, "show_orders");
handle_order_operation($operation);

