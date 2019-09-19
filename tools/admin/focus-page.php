<?php
require_once( "load.php" );
require_once ("admin.php");

$debug = get_param("debug", false, false);

print header_text(true, true, is_rtl(), array("data.js"));
// focus_init();

$operation = get_param("operation", false, null);
if ($operation){
	switch ($operation)
	{
		case "new_task":
			print greeting();
			break;
	}

	handle_admin_operation($operation);
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
	im_init($admin_scripts);
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
	im_init($admin_scripts);
	show_team($team_id, get_param("active_only", false, true));
	return;
}

$row_id = get_param( "row_id", false );
if ($row_id)
{
	global $admin_scripts;
	im_init($admin_scripts);
	show_task($row_id);
	return;
}

$time_filter = get_param("time", false, true);

$args["url"] = basename(__FILE__);
// print "url=". $args["url"];

im_init($admin_scripts);
print greeting();
print gui_hyperlink("Repeating tasks", $url . "?templates");

print " ";

print gui_hyperlink("add tasks", $url . "?operation=new_task");

print " ";

print managed_workers(get_user_id(), $_SERVER['REQUEST_URI']);

print " ";

print gui_hyperlink("projects", $url . "?operation=projects");

//	$sum     = null;

print gui_header(1, "Tasks assigned to me");
$args["query"] = " owner = " . get_user_id();
$args["limit"] = get_param("limit", false, 10);
$args["active_only"] = get_param("active_only", false, true);
print active_tasks($args);
if (get_user_id() != 1) return;

print gui_header(1, "My teams' tasks");
// foreach (show_team())

$members = comma_implode(team_all_members(get_user_id()));
if (strlen($members)) {
	print $members;
    $args["query"] = " owner in (" . $members . ")";
	print active_tasks($args, $debug, $time_filter);
}
