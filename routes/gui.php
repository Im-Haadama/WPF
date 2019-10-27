<?php

function gui_select_mission( $id, $selected = 0, $args = null ) {
	$events = GetArg($args, "events", null);

	$sql_where = " where date >= curdate() or date is null";
	return gui_select_table( $id, "im_missions", $selected, $events, array( 1 ),
		"ifnull(concat (name, ' ', DAYOFMONTH(date), '/', month(date)), name)", $sql_where, true, false, "date" );
}
