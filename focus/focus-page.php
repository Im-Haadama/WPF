<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "focus.php" );

$debug = get_param("debug", false, false);

print header_text(true, true, is_rtl(), array("data.js"));
// focus_init();

$operation = get_param("operation", false, null);

if ($operation) {
	handle_focus_operation($operation);
	return;
}
$project_id = get_param( "project_id" );
if ( $project_id ) {
	$args = [];
	$args["project"] = $project_id;
	print active_tasks( $args );
	return;
}

$task_template_id = get_param("task_template_id");
if ($task_template_id)
{
	global $admin_scripts;
	focus_init($admin_scripts);
	show_templates($url, $task_template_id);
	return;
}

// print "X" . get_param("templates") != null . "X";

if (get_param("templates", false,"none") !== "none") {
	print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

	print gui_hyperlink("הוסף תבנית", get_url(true) . "?operation=new_template");

	show_templates(get_url(1));
	return;
}
	$team_id = get_param("team");
if ($team_id)
{
	global $admin_scripts;
	focus_init($admin_scripts);
	show_team($team_id, get_param("active_only", false, true));
	return;
}

$row_id = get_param( "row_id", false );
if ($row_id)
{
	global $admin_scripts;
	focus_init($admin_scripts);
	show_task($row_id);
	return;
}

$time_filter = get_param("time", false, true);

$args["url"] = basename(__FILE__);
// print "url=". $args["url"];

$url = get_url(1);
print greeting();
print gui_hyperlink("Repeating tasks", $url . "?templates");

print " ";

print gui_hyperlink("add tasks", $url . "?operation=new_task");

print " ";

print managed_workers(get_user_id(), $_SERVER['REQUEST_URI']) . " ";

if (im_user_can("edit_task_types"))
	print gui_hyperlink("projects", $url . "?operation=projects") . " ";

if (im_user_can("edit_projects"))
	print gui_hyperlink("task types", $url . "?operation=task_types") . " ";

//	$sum     = null;

// Tasks I need to handle
print gui_header(1, "Tasks assigned to me");
$args["query"] = " owner = " . get_user_id();
$args["limit"] = get_param("limit", false, 10);
$args["active_only"] = get_param("active_only", false, true);
print active_tasks($args);
// if (get_user_id() != 1) return;

// Tasks my teams need to handle.
$members = comma_implode(team_all_members(get_user_id()));
if (strlen($members)) {
	print gui_header(1, "My teams' tasks");
    $args["query"] = " owner in (" . $members . ")";
	print active_tasks($args, $debug, $time_filter);
}

// Tasks that I created
if (strlen ($members) > 1)
	$args["query"] = " creator = " . get_user_id() . " and owner not in (" . $members . ", " . geT_user_id() . ")";
else
	$args["query"] = " creator = " . get_user_id() . " and owner != " . geT_user_id();

$args["limit"] = get_param("limit", false, 10);
$args["active_only"] = get_param("active_only", false, true);
$result =  active_tasks($args);
// print "len=" . strlen($result);
if (strlen($result) > 150){
	print gui_header(1, "Tasks I've created");
	print $result;
}
