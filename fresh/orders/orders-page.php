<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(ROOT_DIR . '/im-config.php');
require_once(ROOT_DIR . "/init.php" );
require_once("Order.php");
//require_once(ROOT_DIR . "/focus/gui.php");
//require_once(ROOT_DIR . "/org/gui.php");
//require_once(ROOT_DIR . "/routes/gui.php");

if (! get_user_id()) {
	force_login();
	return;
}

require_once("new-order.php");

$operation = get_param("operation", false, "show_orders");
handle_order_operation($operation);

