<script>
    function success_message(xmlhttp)
    {
        if (xmlhttp.response.substr(0, 3) === "done")
            alert("<?php print im_translate("Success"); ?>");
        else
            alert (xmlhttp.response);
    }
</script>
<?php

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

	$args = array("selected" => $value,
	              "events"=>$events,
	              "name"=>"task_description", // "SUBSTR(task_description, 1, " . $length . ")",
	              "where"=> $query,
	              "include_id" => 1,
	              "datalist" =>1,
	              "multiple_inline" => 1,
	              "debug" => $debug);

	if ($debug) {
		print "where: " . $query . "<br/>";
	}
	return GuiAutoList($id, "tasks", $args);
}
