<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

require_once( "../supplies/supplies.php" );

$table_prefix  = "im_";
$root_file     = "im_tools.php";
$target_folder = "../supplies";

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 3 ";
$useMultiSite = false;

$header_text = "אספקות";

// transform value
$trans = [];
//$trans["task_template"] = "get_task_link";
$trans["supplier"]  = "get_supplier_name";
$trans["paid_date"] = "display_date";
$trans["status"]    = "display_status";
// display_part_name()'
//$actions = array( array( "שכפל", "missions.php?operation=dup&id=" ) );
//				 array("בוצע", "tasklist.php?operation=end&id="),
//	             array("בטל", "tasklist.php?operation=cancel&id="));

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

$query = " status in (" . SupplyStatus::NewSupply . ", " . SupplyStatus::Sent . ", " . SupplyStatus::Supplied . ")";
//$query = "date > date_sub(curdate(), interval 7 day)";
