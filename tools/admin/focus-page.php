<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once("load.php");
require_once ("admin.php");

$url = get_url();
$base_url = get_url(1);
$project_id = get_param( "project_id" );
if ( $project_id ) {
	$args = [];
	$args["project"] = $project_id;
	show_active_tasks( $args );
	return;
}

$task_template_id = get_param("task_template_id");
if ($task_template_id) { show_templates($url, $task_template_id); return; }

$team_id = get_param("team");

if ($team_id) { show_team($team_id, get_param("active_only", false, true), $base_url); return; }

$row_id = get_param( "row_id", false );
if ($row_id) { show_task($row_id); return; }

$debug = get_param("debug", false, false);
$time_filter = get_param("time", false, true);

$args["url"] = basename(__FILE__);
// print "url=". $args["url"];

show_active_tasks($args, $debug, $time_filter);

