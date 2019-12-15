<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/09/18
 * Time: 12:14
 */

require_once( FRESH_INCLUDES . "/niver/gui/inputs.php" );

// $selector_name( $key, $data, $args)
function gui_select_product( $id, $data = null, $args = null)
// $events, $datalist = "products" ) // 'onchange="select_product(' . $line_id . ')"'
{
//	print "data=$data<br/>";
	if (! $args)
		$args = array();

	$product_name = (($data > 0) ? get_product_name($data) : $data);
	if (isset($args["edit"]) and !$args["edit"]) return $product_name;
	$args["selected"] = $data;
	$args["name"] = "post_title";
	$args["value"] = $product_name;
	$args["datalist"] = true;
	$args["id_field"] = "ID";
	$args["include_id"] = true;

	// return GuiSelectTable( $id, "im_products", $args);
	return GuiAutoList($id, "products", $args);
}
