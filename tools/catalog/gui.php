<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/09/18
 * Time: 12:14
 */

require_once( ROOT_DIR . "/agla/gui/inputs.php" );

function gui_select_product( $id, $events ) // 'onchange="select_product(' . $line_id . ')"'
{
	return gui_input_select_from_datalist( $id, "products", $events );
}
