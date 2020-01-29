<?php



if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once(FRESH_INCLUDES . "/init.php" );
require_once( "Order.php" );
//require_once(FRESH_INCLUDES . "/focus/gui.php");
//require_once(FRESH_INCLUDES . "/org/gui.php");
//require_once(FRESH_INCLUDES . "/routes/gui.php");

if (! get_user_id()) {
	auth_redirect();
	return;
}

require_once("new-order.php");

$operation = GetParam("operation", false, "show_orders");
handle_order_operation($operation);

