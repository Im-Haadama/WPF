<?php

function gui_select_mission( $id, $selected = 0, $args = null ) {
	$events = GetArg($args, "events", null);

	$sql_where = " where date >= curdate() or date is null";
	return gui_select_table( $id, "im_missions", $selected, $events, array( 1 ),
		"ifnull(concat (name, ' ', DAYOFMONTH(date), '/', month(date)), name)", $sql_where, true, false, "date" );
}


function gui_select_zones($id, $selected, $args)
{
	$edit = GetArg($args, "edit", false);

	if (! $edit) {
		$f = strtok($selected, ":");
		$result = zone_get_name($f);
        while ($z = strtok( ":")) $result .= ", " . zone_get_name($z);
		return $result;
	}
	$wc_zones = WC_Shipping_Zones::get_zones();

	$args["values"] = $wc_zones;
	$events = GetArg($args, "events", null);
	$args["multiple"] = true;

	return gui_select( $id, "zone_name", $wc_zones, $events, $selected, "id", "class", true );
}