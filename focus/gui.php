<?php


// $selector_name( $input_name, $orig_data, $args)
function gui_select_worker( $id = null, $selected = null, $args = null ) {

	// $events = GetArg($args, "events", null);
	$edit = GetArg($args, "edit", true);
	$companies = GetArg($args, "companies", "must send company");
	$debug = false; // (get_user_id() == 1);
	$args["debug"] = $debug;
	$args["name"] = "client_displayname(user_id)";
	$args["where"] = "where is_active=1 and company_id in (" . comma_implode($companies) . ")";
	$args["id_key"] = "user_id";
	$args["selected"] = $selected;

	if ($edit)
		return GuiSelectTable($id, "im_working", $args);
//		return gui_select_table( $id, "im_working", $selected, $events, "",
//			"client_displayname(user_id)",
//			"where is_active=1 and company_id = " . $company, true, false, null, "id" );
	else
		return sql_query_single_scalar("select client_displayname(user_id) from im_working where user_id = " . $selected);
}


function gui_select_task( $id, $value, $args ) {
	$debug = 0; // (1 == get_user_id());
	$events = GetArg($args, "events", null);
	$query = GetArg($args, "where", " where status = 0 ");
	$length = GetArg($args, "length", 30);

//	if ( $worker ) {
//		// print "w=" . $worker;
//		// $user_id = sql_query("select user_id from im_working where id = " . $worker);
//		$query = " where id in (select project_id from im_working where worker_id = " . $worker . ")";
//	}

//	return gui_select_table( $id, "im_tasklist", $value, $events, "", "task_description",
//		"where " . $query, true, false );

	$args = array("value" => $value,
	              "events"=>$events,
	              "name"=>"task_description", // "SUBSTR(task_description, 1, " . $length . ")",
	              "where"=> $query,
	              "include_id" => 1,
	              "datalist" =>1,
	              "debug" => $debug);

	if ($debug) {
		print "where: " . $query . "<br/>";
	}
	return GUiSelectTable($id, "im_tasklist", $args);
}
