<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

$obj_name      = "tasklist";
$table_prefix  = "im_";
$root_file     = realpath( ROOT_DIR ) . '/tools/im_tools.php';
$target_folder = "/tools/tasklist";

require_once( ROOT_DIR . '/tools/im_tools_light.php' );

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 12 desc, 6 desc, 2 ";
$preset_query = array(
	"",
	"(date(date) <= CURRENT_DATE or isnull(date)) and (status < 2) " .
	" and (not mission_id > 0) and task_active_time(id) " .
	" and (isnull(preq) or task_status(preq) >= 2) "
);
$useMultiSite = false;

$header_text = "משימות פעילות";

// transform value
$trans = [];
//$trans["task_template"] = "get_task_link";
$trans["url"]        = "show_zone_names";
$trans["project_id"] = "get_project_name";

$page_actions = array(
	array( "רענן", "create.php?verbose=1" ),
	array( "תבניות", "c-get-all-task_templates.php" )
);

$actions = array(
	array( "התחל", "tasklist.php?operation=start&id=" ),
	array( "בוצע", "tasklist.php?operation=end&id=" ),
	array( "בטל", "tasklist.php?operation=cancel&id=" )
);

$display_url                  = array();
$display_url["task_template"] = "c-get-task_templates.php?id=";

// $load_actions = array( "create_tasks" );

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

$insert["project_id"] = "gui_select_project";
$insert["mission_id"] = "gui_select_mission";
$insert["preq"]       = "gui_select_task_related";
$insert_id["preq"]    = true;

