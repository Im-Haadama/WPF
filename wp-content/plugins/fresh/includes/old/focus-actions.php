<?php

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );
$operation = get_param("operation", true);

switch ($operation)
{
	case "handle_minus_quantity":
		Catalog::HandleMinusQuantity();
		print "0 ok";

}