<?php

require_once(ROOT_DIR . '/org/org.php');

/// Parameters are required - we need to show the allowed projects to the given user.
function gui_select_project($id, $value, $args)
{
	// print "v=$value<br/>"; 16/10/2019 - default is edit=true.
	$edit = GetArg($args, "edit", true);
	$new_row = GetArg($args, "new_row", false);

	if (! $edit)
	{
//		print "v= " . $value . "<br/>";
		return get_project_name($value);
	}
	// Filter by worker if supplied.
	$user_id = GetArg($args, "worker", get_user_id());
	$query = null;
	if ( !$user_id ) {
		throw new Exception( __FUNCTION__ .": No user " . $user_id);
	}

	// Check if this user is global company user.
	// $query = " where id in (" . comma_implode(worker_get_projects($user_id)) . ")";

	$companies = worker_get_companies($user_id);
	$query = " where id in (select project_id from im_working where company_id in (" . comma_implode($companies) . "))";
//	} else {
//		$query = " where id in (" . comma_implode(worker_get_projects($user_id) . ")");
//	}

	// print "w=" . $worker;
	// $user_id = sql_query("select user_id from im_working where id = " . $worker);
//		$query = " where id in (select project_id from im_working where user_id = " . $user_id . ")";

	$args["where"]    = $query;
	$args["name"]     = "project_name";
	$args["selected"] = $value;
	$gui              = GuiSelectTable( $id, "im_projects", $args );
	$form_table = GetArg($args, "form_table", null);
	if ($form_table and $new_row) { // die(__FUNCTION__ . ":" . " missing form_table");
		$gui .= gui_button( "add_new_project", "add_element('project', '" . $form_table . "', '" . get_url() . "')", "New Project" );
	}

	return $gui;
}

function gui_select_worker( $id = null, $selected = null, $args = null )
{
	// $events = GetArg($args, "events", null);
	$edit = GetArg($args, "edit", true);
	$companies = worker_get_companies(get_user_id());

	$debug = false; // (get_user_id() == 1);
	$args["debug"] = $debug;
	$args["name"] = "client_displayname(user_id)";
	$args["where"] = "where is_active=1 and company_id in (" . comma_implode($companies) . ")";
	$args["id_key"] = "user_id";
	$args["selected"] = $selected;

	if ($edit) {
		$gui = GuiSelectTable($id, "im_working", $args);
		return $gui;
	} else
		return ($selected > 0) ? sql_query_single_scalar("select client_displayname(user_id) from im_working where user_id = " . $selected) :
			"";
}

function gui_select_team($id, $selected = null, $args = null)
{
	$edit = GetArg($args, "edit", true);
	$companies = worker_get_companies(get_user_id());
	$debug = false; // (get_user_id() == 1);
	$args["debug"] = $debug;
	$args["name"] = "team_name";
	$args["selected"] = $selected;
	$form_table = GetArg($args, "form_table", null);

	if ($edit) {
		$gui = GuiSelectTable($id, "im_working_teams", $args);
		$gui .= gui_button("add_new_team", "add_element('team', '" . $form_table . "', '" .get_url() . "')", "New Team");
		return $gui;
	}
	else
		return ($selected > 0) ? sql_query_single_scalar("select team_name from im_working_teams where id = " . $selected) : "";

}

function get_project_name($project_id)
{
	if ($project_id)
		return sql_query_single_scalar("SELECT project_name FROM im_projects WHERE id = " . $project_id);
	return "No project selected";
}
