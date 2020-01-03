<?php

if (! function_exists('gui_select_task')){
function gui_select_task( $id, $value, $args ) {
	$debug = 0; // (1 == get_user_id());
	$events = GetArg($args, "events", null);
	$query = GetArg($args, "query", " status = 0 ");
//	$length = GetArg($args, "length", 30);

//	if ( $worker ) {
//		// print "w=" . $worker;
//		// $user_id = sql_query("select user_id from im_working where id = " . $worker);
//		$query = " where id in (select project_id from im_working where worker_id = " . $worker . ")";
//	}
//	return gui_select_table( $id, "im_tasklist", $value, $events, "", "task_description",
//		"where " . $query, true, false );

	if ($value > 0) {
		$t = new Focus_Tasklist($value);
		$selected = $value . ")" . $t->getTaskDescription();
	} else
		$selected = $value;

	$args = array("selected" => $selected,
	              "events"=>$events,
	              "name"=>"task_description", // "SUBSTR(task_description, 1, " . $length . ")",
	              "query"=> $query,
	              "include_id" => 1,
	              "datalist" =>1,
	              "multiple_inline" => 1,
	              "debug" => $debug);

	if ($debug) {
		print "where: " . $query . "<br/>";
	}
	return Core_Html::GuiAutoList($id, "tasks", $args);
}

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
//	var_dump($wc_zones);

	$args["values"] = $wc_zones;
	$events = GetArg($args, "events", null);
	$args["multiple"] = true;

	return gui_select( $id, "zone_name", $wc_zones, $events, $selected, "id", "class", true );
}
}