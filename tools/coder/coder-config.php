<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

$table_prefix  = "im_";
$obj_name      = "task_templates";
$root_file     = "im_tools.php";
$target_folder = "../tasklist";

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 2 ";
$useMultiSite = false;

$header_text = "ניהול תבנית משימות";
$query       = "(status < 2)";

// transform value
$trans                  = [];
$trans["task_template"] = "get_task_link";
$trans["status"]        = "get_task_status";
//$trans["supplier"] = "get_supplier_name";
//$trans["date"]     = "get_week";

$actions = array(
	array( "התחל", "tasklist.php?operation=start&id=" ),
	array( "בוצע", "tasklist.php?operation=end&id=" ),
	array( "בטל", "tasklist.php?operation=cancel&id=" )
);


$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

// CONS
//$table_prefix = "c_";
//$obj_name     = "employees";
//$trans["name"] = "edit_teacher_program";
//$trans["id"] = "edit_teacher_data";
