<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/08/15
 * Time: 08:19
 */

require_once( '../r-shop_manager.php' );
require_once( "tasklist.php" );
require_once( "../../niver/web.php" );


$this_url           = "admin.php";
$entity_name        = "משימה";
$entity_name_plural = "משימות";
$table_name         = "im_tasklist";

print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js" ) );

$operation = get_param( "operation", false );
//if ( $operation ) {
//	switch ( $operation ) {
////		case "add":
////			$args = array();
////			foreach ( $_GET as $key => $data ) {
////				if ( ! in_array( $key, array( "operation", "table_name" ) ) ) {
////					if ( ! isset( $args["fields"] ) ) {
////						$args["fields"] = array();
////					}
////				}
////				$args["fields"][ $key ] = $data;
////			}
////			$args["edit"] = true;
////			print NewRow( "im_business_info", $args, true );
////			print gui_button( "btn_add", "save_new('im_business_info')", "הוסף" );
////			break;
//
//		case "templates":
//			show_templates();
//			break;
//		default:
//			die( "$operation not handled" );
//	}
//
//	return;
//}

// Selection:
$project_id = get_param( "project_id" );
if ( $project_id ) { show_tasks( $project_id, $user_ID ); return; }

$task_template_id = get_param("task_template_id");
if ($task_template_id) { show_templates($task_template_id); return; }

if ($operation == "templates") { show_templates(); return; }

$row_id = get_param( "row_id", false );
if ($row_id) { show_task($row_id); return; }

$debug = get_param("debug", false, false);
$time_filter = get_param("time", false, true);

show_active_tasks($debug, $time_filter);


$non_zero = get_param( "non_zero" );

$url = get_url();

print header_text( false, true, true, "/vendor/sorttable.js" );

//print gui_header( 1, "פרויקטים" );
//
//print gui_hyperlink( "רק פרויקטים עם משימות פתוחות", $url . "?non_zero=1" );
//show_projects( $user_ID, $non_zero );
//
//print gui_header( 1, "משימות חוזרות" );
//show_templates();

function show_projects( $owner, $non_zero ) {
	$links = array();

	$links["id"] = "index.php?project_id=%s";
	$sql         = "select id, project_name, project_priority, project_count(id, " . $owner . ") as open_count " .
	               " from im_projects ";
	if ( $non_zero ) {
		$sql .= " where project_count(id, " . $owner . ") > 0 ";
	}
	$sql .= " order by 3 desc";

	$args           = array();
	$args["class"]  = "sortable";
	$args["links"]  = $links;
	$args["header"] = true;

	$sum = array();

	print GuiTableContent( "projects", $sql, $args, $sum );
}

function show_templates( $template_id = 0 ) {
	$args              = array();

	$args["selectors"] = array(
		"repeat_freq" => "gui_select_repeat_time",
		"project_id"  => "gui_select_project",
		"owner" => "gui_select_client",
		"creator" => "gui_select_client"
	);
	$sql         = "select * " .
	               " from im_task_templates ";
	if ($template_id){
		$args["transpose"] = true;
		$args["edit"] = true;
		$args["add_checkbox"] = true;
		$args["events"] = "onchange=\"changed(this)\"";

		print GuiRowContent("im_task_templates", $template_id, $args);
		print gui_button( "btn_save", "save_entity('im_task_templates', " . $template_id . ')', "שמור" );

		return;
	}

	$sql .= " order by 3 desc";

	$args["class"]     = "sortable";
	$args["links"]     = array ("id" => "admin.php?task_template_id=%s" );
	$args["header"]    = true;
	$sum               = array();

	print GuiTableContent( "projects", $sql, $args, $sum );

}

function show_tasks( $project_id, $owner ) {
	$actions     = array( gui_hyperlink( "בוצע", "../tasklist/tasklist-post.php?operation=end&id=%s" ) );
	$links       = array();
	$links["id"] = "../tasklist/admin.php?id=%s";
	$sum         = array();

	$sql = "select * from im_tasklist " .
	       " where project_id = " . $project_id .
	       " and status = " . eTasklist::waiting .
	       " and owner = " . $owner .
	       " order by 12 desc ";

	$args            = array();
	$args["actions"] = $actions;
	$args["class"]   = "sortable";
	$args["links"]   = $links;

	print GuiTableContent( "tasks", $sql, $args, $sum );

}



if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/tools/im_tools_light.php' );
require_once( ROOT_DIR . '/niver/gui/sql_table.php' );
require_once(ROOT_DIR . '/tools/people/people.php');


// Selectors
$selectors = array();
$selectors["project_id"] = "gui_select_project";
$selectors["date"] = "gui_input_date";
$selectors["owner"] = "gui_select_client";
$selectors["creator"] = "gui_select_client";

print header_text( false, true, true, array(
	"/niver/gui/client_tools.js",
	"/tools/admin/data.js",
	"/vendor/sorttable.js"
) );


function show_task($row_id)
{
	global $entity_name, $table_name;

	$selectors = array();
	$selectors["project_id"] = "gui_select_project";
//	$selectors["date"] = "gui_input_date";
	$selectors["owner"] = "gui_select_client";
	$selectors["creator"] = "gui_select_client";
	$selectors["preq"] = "gui_select_task";

	print gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = 1;
	$args["add_checkbox"] = true;
	$args["skip_id"]      = true;
	$args["selectors"] = $selectors;
	$args["events"] = "onchange=\"selected()\"";
	$args["transpose"] = true;
	$args["where"] = "project_id = " . sql_query_single_scalar("select project_id from im_tasklist where id = " .$row_id);

	print GuiRowContent( $table_name, $row_id, $args );
	print gui_button( "btn_save", "save_entity('$table_name', " . $row_id . ')', "שמור" );

	return;
}


function show_active_tasks($debug = false, $time = false)
{
	global $this_url;
	global $entity_name_plural;
	global $table_name;

	$links       = array();
	$links["id"] = $this_url . "?row_id=%s";

	print gui_header( 1, "ניהול " . $entity_name_plural );

	print Date("G:i", strtotime("now"));

	print gui_hyperlink("משימות חוזרות", "admin.php?operation=templates");

	$sum     = null;
//
	$query   = "where status in (0, 1) and (isnull(preq) or task_status(preq) >= 2) and date <= Curdate()";
	if ($time) $query .= " and task_active_time(id)";

	$query .= " and owner = " . wp_get_current_user()->ID;

	$actions = array(
		array( "התחל", "tasklist.php?operation=start&id=%s" ),
		array( "בוצע", "tasklist.php?operation=end&id=%s" ),
		array( "בטל", "tasklist.php?operation=cancel&id=%s" ),
		array( "דחה", "tasklist.php?operation=postpone&id=%s" )
	);
	$order   = "order by priority desc ";
	$args             = array();

	$links["task_template"] = "admin.php?task_template_id=%s";
	$links["id"] = "admin.php?row_id=%s";
	$args["links"]    = $links;
//$args["first_id"] = true;
	$args["actions"]  = $actions;
	$args["id_col"] = 0;

//var_dump($args);
	$more_fields = "";

	if ($debug and ! $time) $more_fields .= ", task_template_time(id) ";

	$sql = "select id, date(date), task_description, task_template, started, project_name(project_id),
       priority, preq, task_active_time(id) $more_fields from $table_name $query $order";

	if ($debug)
		print $sql;
	print GuiTableContent( $table_name, $sql, $args );

}

