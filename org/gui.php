<?php

require_once(ROOT_DIR . '/org/org.php');

function gui_select_project( $id, $value, $args)
{
	// print "v=$value<br/>";
	$edit = GetArg($args, "edit", false);
	if (! $edit)
	{
//		print "v= " . $value . "<br/>";
		return get_project_name($value);
	}
	// Filter by worker if supplied.
	$user_id = GetArg($args, "worker", null);
	$query = null;
	if ( !$user_id ) {
		throw new Exception( __FUNCTION__ .": No worker given" );
	}

	// Check if this user is global company user.
	if ($companies = worker_get_companies($user_id)){
		$query = " where id in (select project_id from im_working where company_id in (" . comma_implode($companies) . "))";
	} else {
		$query = " where id in (" . comma_implode(worker_get_projects($user_id) . ")");
	}

	// print "w=" . $worker;
	// $user_id = sql_query("select user_id from im_working where id = " . $worker);
//		$query = " where id in (select project_id from im_working where user_id = " . $user_id . ")";

	$args["where"] = $query;
	$args["name"] = "project_name";
	$args["selected"] = $value;
	return GuiSelectTable($id, "im_projects", $args);

}

