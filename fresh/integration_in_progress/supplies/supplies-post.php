<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 11:45
 */



require_once( '../r-multisite.php' );
require_once( 'Supply.php' );
require_once( '../orders/orders-common.php' );
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
require_once("../catalog/gui.php");

// my_log(__FILE__);
// print header_text(false, true, false);
if ( $operation = GetParam("operation", false, null) ) {
	handle_supplies_operation($operation);
}
