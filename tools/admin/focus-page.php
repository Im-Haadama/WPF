<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once("load.php");
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
	show_active_tasks( $args );
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
	show_team($team_id, get_param("active_only", false, true), get_url(1));
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
print gui_hyperlink("repeating tasks", $url . "?templates");

print " ";

print gui_hyperlink("add tasks", $url . "?operation=new_task");

print " ";

print managed_workers(get_user_id(), $_SERVER['REQUEST_URI']);

print " ";

print gui_hyperlink("projects", $url . "?operation=projects");

//	$sum     = null;

print active_tasks($args, $debug, $time_filter);

