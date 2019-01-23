<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

require_once( "../../im-config.php" );
$conn = new mysqli( IM_DB_HOST, IM_DB_NAME, IM_DB_PASSWORD, IM_DB_NAME );
mysqli_set_charset( $conn, 'utf8' );

$obj_name      = "projects";
$table_prefix  = "im_";
$root_file     = realpath( ROOT_DIR ) . '/tools/business/business_info.php';
$target_folder = "/tools/people";

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
// $order        = "order by 12 desc, 6 desc, 2 ";
$query = "1";
// "(date(date) <= CURRENT_DATE or isnull(date)) and (status < 2) ";
// $query .= " and (not mission_id > 0) and task_active_time(id)";
$useMultiSite = false;

$header_text = "פרויקטים";

// transform value
$trans = [];
//$trans["task_template"] = "get_task_link";
// $trans["zones"] = "show_zone_names";
$trans["document_type"] = "get_document_type_name";
$trans["part_id"]       = "get_customer_name";

//$page_actions = array( array("רענן", "create.php?verbose=1"),
//	array("תבניות", "c-get-all-task_templates.php"));

$page_actions = array( array( "חשבוניות", "c-get-all-business_info.php?document_type=4" ) );

//$actions = array(
//	array( "התחל", "tasklist.php?operation=start&id=" ),
//	array( "בוצע", "tasklist.php?operation=end&id=" ),
//	array( "בטל", "tasklist.php?operation=cancel&id=" )
//);

//$display_url= array();
//$display_url["task_template"] = "c-get-task_templates.php?id=";

// $load_actions = array( "create_tasks" );

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

$insert["project_id"] = "gui_select_project";
$insert["mission_id"] = "gui_select_mission";
$insert["part_id"]    = "gui_select_supplier";
