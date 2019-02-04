<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/04/18
 * Time: 11:04
 */

$obj_name      = "working";
$table_prefix  = "im_";
$root_file     = realpath( ROOT_DIR ) . '/tools/im_tools.php';
$target_folder = "/tools/people";

require_once( ROOT_DIR . '/tools/im_tools_light.php' );

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 3 ";
$useMultiSite = false;

$header_text = "עובדים";

// transform value
$trans              = [];
$trans["worker_id"] = "get_user_name";
//$trans["task_template"] = "get_task_link";
//$trans["supplier"]  = "get_supplier_name";
//$trans["paid_date"] = "display_date";
//$trans["status"]    = "display_status";

//$page_actions = array(
//	array( "רענן", "create.php?verbose=1" ),
//	array( "תבניות", "c-get-all-task_templates.php" )
//);
//
//$actions = array(
//	array( "התחל", "tasklist.php?operation=start&id=" ),
//	array( "בוצע", "tasklist.php?operation=end&id=" ),
//	array( "בטל", "tasklist.php?operation=cancel&id=" )
//);

$display_url                  = array();
$display_url["task_template"] = "c-get-task_templates.php?id=";

// $load_actions = array( "create_tasks" );

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

// $query = " status in (" . SupplyStatus::NewSupply . ", " . SupplyStatus::Sent . ", " . SupplyStatus::Supplied . ")";
$query = " is_active = 1 ";

//$insert["worker_id"]  = "gui_select_client";
//$insert["project_id"] = "gui_select_project";
