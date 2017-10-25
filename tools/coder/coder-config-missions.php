<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

$table_prefix  = "im_";
$root_file     = "im_tools.php";
$target_folder = "../delivery";

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 2 ";
$useMultiSite = false;

$header_text = "ניהול תבנית משימות";

// transform value
$trans = [];
//$trans["task_template"] = "get_task_link";
$trans["zones"] = "show_zone_names";

$actions = array( array( "שכפל", "missions.php?operation=dup&id=" ) );
//				 array("בוצע", "tasklist.php?operation=end&id="),
//	             array("בטל", "tasklist.php?operation=cancel&id="));

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();


$query = " date >= curdate() -7 or date is null ";