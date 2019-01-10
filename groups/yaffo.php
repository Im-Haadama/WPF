<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/03/18
 * Time: 14:25
 */

require_once( "../tools/im_tools.php" );
require_once( "../tools/gui/inputs.php" );

print header_text( true, true );

$group = "Yaffo";
$min   = 0;
$user  = 381;

print gui_header( 1, "פרטי המזמין" );
print gui_table( array(
	array( "שם", gui_input( "name", "", "" ) ),
	array( "טלפון", gui_input( "phone", "", "" ) )
) );
require_once( "../tools/orders/order-form.php" );
