<?php


if (! function_exists("gui_select_project")){
/// Parameters are required - we need to show the allowed projects to the given user.
function gui_select_project($id, $value, $args)
{
	$edit = GetArg($args, "edit", true);
	$new_row = GetArg($args, "new_row", false);

	if (! $edit)
	{
		return Org_Project::GetName($value);
	}
	// Filter by worker if supplied.
	$user_id = GetArg($args, "worker_id", get_user_id());
	if ( !$user_id ) {
		throw new Exception( __FUNCTION__ .": No user " . $user_id);
	}

	$form_table = GetArg($args, "form_table", null);
	$events = GetArg($args,"events", null);

	$projects = Org_Project::GetProjects($user_id);
	$projects_list = [];
	foreach($projects as $project_id => $project_name) $projects_list[] = array("project_id" => $project_id, "project_name" => $project_name);
	$result = Core_Html::gui_select( $id, "project_name", $projects_list, $events, $value, "project_id" );
	if ($form_table and $new_row) { // die(__FUNCTION__ . ":" . " missing form_table");
		$result .= Core_Html::GuiButton( "add_new_project", "New Project", array("action" => "add_element('project', '" . $form_table . "', '" . get_url() . "')", "New Project" ));
	}

	return $result;
}

function gui_select_company($id, $value, $args)
{
	$edit = GetArg($args, "edit", true);
	$new_row = GetArg($args, "new_row", false);

	if (! $edit)
	{
		return Org_Company::GetName($value);
	}
	// Filter by worker if supplied.
	$user_id = GetArg($args, "worker_id", get_user_id());
	if ( !$user_id ) {
		throw new Exception( __FUNCTION__ .": No user " . $user_id);
	}

	$form_table = GetArg($args, "form_table", null);
	$events = GetArg($args,"events", null);

	$companies = Org_Company::GetCompanies($user_id);
	$companies_list = [];
	foreach($companies as $company_id => $company_name) $companies_list[] = array("company_id" => $company_id, "company_name" => $company_name);
	$result =  gui_select( $id, "company_name", $companies_list, $events, $value, "company_id" );
//	if ($form_table and $new_row) { // die(__FUNCTION__ . ":" . " missing form_table");
//		$result .= Core_Html::GuiButton( "add_new_project", "add_element('project', '" . $form_table . "', '" . get_url() . "')", "New Project" );
//	}

	return $result;
}

function gui_select_worker( $id = null, $selected = null, $args = null )
{
	// $events = GetArg($args, "events", null);
	$edit = GetArg($args, "edit", true);
	$companies = Org_Worker::GetCompanies(get_user_id());

	$debug = false; // (get_user_id() == 1);
	$args["debug"] = $debug;
	$args["name"] = "client_displayname(user_id)";
	$args["where"] = "where is_active=1 and company_id in (" . comma_implode($companies) . ")";
	$args["id_key"] = "user_id";
	$args["selected"] = $selected;
	$args["query"] = (isset($args["query_team"]) ? $args["query_worker"] : null);

	if ($edit) {
		$gui = Core_Html::GuiSelectTable($id, "im_working", $args);
		return $gui;
	} else
		return ($selected > 0) ? sql_query_single_scalar("select client_displayname(user_id) from im_working where user_id = " . $selected) :
			"";
}

function gui_select_user( $id = null, $selected = null, $args = null )
{
	// $events = GetArg($args, "events", null);
	$edit = GetArg($args, "edit", true);

	$args["name"] = "client_displayname(id)";
	$args["id_key"] = "id";
	$args["selected"] = $selected;

	if ($edit) {
		$gui = GuiAutoList($id, "users", $args);
		return $gui;
	} else
		return ($selected > 0) ? sql_query_single_scalar("select client_displayname(user_id) from wp_users where id = " . $selected) :
			"";
}

function gui_select_team($id, $selected = null, $args = null)
{
	$edit = GetArg($args, "edit", true);
	$companies = Org_Worker::GetCompanies(get_user_id());
	$debug = false; // (get_user_id() == 1);
	$args["debug"] = $debug;
	$args["name"] = "team_name";
	$args["selected"] = $selected;

	// collision between query of the container and the selector.
	$args["query"] = (isset($args["query_team"]) ? $args["query_team"] : null);

	$form_table = GetArg($args, "form_table", null);

	if ($edit) {
		$gui = Core_Html::GuiSelectTable($id, "im_working_teams", $args);
		$gui .= Core_Html::GuiButton("add_new_team", "New Team", array("action" => "add_element('team', '" . $form_table . "', '" .get_url() . "')", "New Team"));
		return $gui;
	}
	else
		return ($selected > 0) ? sql_query_single_scalar("select team_name from im_working_teams where id = " . $selected) : "";

}
}