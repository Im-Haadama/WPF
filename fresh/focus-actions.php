<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );
$operation = get_param("operation", true);

switch ($operation)
{
	case "handle_minus_quantity":
		Catalog::HandleMinusQuantity();
		print "0 ok";

}