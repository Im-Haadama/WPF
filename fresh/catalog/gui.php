<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/09/18
 * Time: 12:14
 */

require_once( ROOT_DIR . "/niver/gui/inputs.php" );

// $selector_name( $key, $data, $args)
function gui_select_product( $id, $data = null, $args = null)
// $events, $datalist = "products" ) // 'onchange="select_product(' . $line_id . ')"'
{
	if (! $args)
		$args = array();

	if (isset($args["edit"]) and !$args["edit"]) return ($data > 0 ? get_product_name($data) : $data);
	$args["selected"] = $data;
	$args["name"] = "post_title";
	$args["selected"] = $data;
	$args["datalist"] = true;
	$args["id_key"] = "ID";
	$args["include_id"] = true;
	return GuiSelectTable( $id, "im_products", $args);
}
