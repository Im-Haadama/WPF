<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

$obj_name      = "tasklist";
$table_prefix  = "im_";
// $root_file     = realpath( ROOT_DIR ) . '/tools/im_tools.php';
$root_file     = realpath( ROOT_DIR ) . '/tools/tasklist/tasklist.php'; //im_tools.php';
$target_folder = "/tools/tasklist";

require_once( ROOT_DIR . '/tools/im_tools_light.php' );

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 12 desc, 6 desc, 2 ";

//$preset_basic_query = "(date(date) <= CURRENT_DATE or isnull(date)) and (status < 2) " .
//                      " and (not mission_id > 0) and task_active_time(id) " .
//                      " and (isnull(preq) or task_status(preq) >= 2) ";
//$preset_query       = array(
//	"",
//	$preset_basic_query,
//	$preset_basic_query . " and owner = 1",
//	$preset_basic_query . " and owner = 369 or creator = 369",
//	$preset_basic_query . " and owner = 369"
//);

//foreach ( $preset_query as $q ) {
//	print $q . " <br/>";
//}

$preset_query = "preset_query";

$preset_title = array( "", "פעילים", "יעקב", "אלה" );

$useMultiSite = false;

$header_text = "משימות פעילות";

// transform value
$trans = [];
//$trans["task_template"] = "get_task_link";
$trans["url"]        = "show_zone_names";
$trans["project_id"] = "get_project_name";
$trans["creator"]    = "get_customer_name";

$page_actions = "tasklist_page_actions";
//	array(
//	array( "רענן", "create.php?verbose=1" ),
//	array( "תבניות", "c-get-all-task_templates.php" )
//);

//for ( $i = 0; $i < count( $preset_title ); $i ++ )
//	array_push( $page_actions, array( $preset_title[ $i ], "c-get-all-tasklist.php?preset=" . $i ));

$actions = array(
	array( "התחל", "tasklist.php?operation=start&id=" ),
	array( "בוצע", "tasklist.php?operation=end&id=" ),
	array( "בטל", "tasklist.php?operation=cancel&id=" ),
	array( "דחה", "tasklist.php?operation=postpone&id=" )
);

$display_url                  = array();
$display_url["task_template"] = "c-get-task_templates.php?id=";

// $load_actions = array( "create_tasks" );

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array(
	"location_name",
	"location_address",
	"mission_id",
	"end_time",
	"preq",
	"status",
	"ended",
	"creator"
);

$insert["project_id"] = "gui_select_project";
$insert["mission_id"] = "gui_select_mission";
$insert["preq"]       = "gui_select_task_related";
$insert["owner"]      = "gui_select_worker";
$insert["creator"]    = "gui_select_creator";

$insert_id["preq"]    = true;

