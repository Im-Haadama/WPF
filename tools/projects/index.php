<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/08/15
 * Time: 08:19
 */

require_once( '../r-shop_manager.php' );
require_once("../tasklist/tasklist.php");
require_once("../../niver/web.php");

$user = new WP_User( $user_ID );

$project_id = get_param("project_id");

$task_template_id = get_param("task_template_id");

if ($task_template_id)
{
	$args["selectors"] = array("repeat_freq" => "gui_select_repeat_time",
	                           "project_id" => "gui_select_project");
	$args["edit"] = true; // get_param("edit", false, false);

	Print RowContent("im_task_templates", $task_template_id, $args);

	return;
}

if ($project_id){
	show_tasks($project_id, $user_ID);
	return;
}

$non_zero = get_param("non_zero");

$url = get_url();

print header_text(false, true, true, "/vendor/sorttable.js");

print gui_header (1, "פרויקטים");

print gui_hyperlink("רק פרויקטים עם משימות פתוחות", $url . "?non_zero=1");
show_projects($user_ID, $non_zero);

print gui_header (1, "משימות חוזרות");
show_templates();

function show_projects($owner, $non_zero)
{
	$links = array();

	$links["id"] = "index.php?project_id=%s";
	$sql = "select id, project_name, project_priority, project_count(id, " . $owner . ") as open_count " .
		" from im_projects ";
	if ($non_zero) $sql .= " where project_count(id, " . $owner . ") > 0 ";
	$sql .=	" order by 3 desc";

	$args = array();
	$args["class"] = "sortable";
	$args["links"] = $links;
	$args["header"] = true;

	$sum = array();

	print TableContent("projects", $sql,	$args, $sum);
}

function show_templates($template_id = 0)
{
	$links = array();

	$links["id"] = "index.php?task_template_id=%s";
	$sql = "select * " .
	       " from im_task_templates ";
	$sql .=	" order by 3 desc";

	$args = array();
	$args["class"] = "sortable";
	$args["links"] = $links;
	$args["header"] = true;
	$args["selectors"] = array("repeat_freq" => "gui_select_repeat_time",
		"project_id" => "gui_select_project");
	$sum = array();

	print TableContent("projects", $sql,	$args, $sum);
}

function show_tasks($project_id, $owner)
{
	$actions = array(gui_hyperlink("בוצע", "../tasklist/tasklist-post.php?operation=end&id=%s"));
	$sum = array();

	$sql = "select * from im_tasklist " .
	       " where project_id = " . $project_id .
	       " and status = " . eTasklist::waiting .
	       " and owner = " . $owner .
	       " order by 12 desc ";

	$args = array();
	$args["actions"]  = $actions;
	$args["class"] = "sortable";

	print TableContent("tasks",	$sql,$args, $sum);

}

?>

