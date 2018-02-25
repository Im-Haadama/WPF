<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

$table_prefix  = "c_";
$root_file     = "../config.php";
$target_folder = "../summary";

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 3 ";
$useMultiSite = false;

$header_text = "סיכום שעות למורים";

// transform value
$trans = [];
//$trans["task_template"] = "get_task_link";
//$trans["part_id"] = "display_part_name";


//$actions = array( array( "שכפל", "missions.php?operation=dup&id=" ) );
//				 array("בוצע", "tasklist.php?operation=end&id="),
//	             array("בטל", "tasklist.php?operation=cancel&id="));

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

//$query = "date > date_sub(curdate(), interval 7 day)";