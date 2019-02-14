<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */


$obj_name      = "bank_account";
$table_prefix  = "im_";
$root_file     = realpath( ROOT_DIR ) . '/tools/im_tools.php';
$target_folder = "/tools/business";

require_once( ROOT_DIR . '/tools/im_tools_light.php' );

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 2 ";

$useMultiSite = false;

$header_text = "חשבונות";

$import_csv = true;

//$actions = array(
//	array( "בטל", "task_templates.php?operation=cancel&id=" )
//);

// transform value
//$trans = [];
////$trans["task_template"] = "get_task_link";
//$trans["project_id"] = "get_project_name";

//$actions = array(array("התחל", "tasklist.php?operation=start&id="),
//				 array("בוצע", "tasklist.php?operation=end&id="),
//	             array("בטל", "tasklist.php?operation=cancel&id="));

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

//$insert               = array();
//$insert["project_id"] = "gui_select_project";
