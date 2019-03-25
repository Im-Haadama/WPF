<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/09/18
 * Time: 12:14
 */

require_once( ROOT_DIR . "/niver/gui/inputs.php" );

function gui_select_product( $id, $events, $datalist = "products" ) // 'onchange="select_product(' . $line_id . ')"'
{
	return gui_input_select_from_datalist( $id, $datalist, $events );
}
