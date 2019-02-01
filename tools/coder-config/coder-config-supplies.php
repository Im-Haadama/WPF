<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

$obj_name      = "supplies";
$table_prefix  = "im_";
$root_file     = realpath( ROOT_DIR ) . "/tools/supplies/supplies.php";
$target_folder = "tools/supplies";

require_once( ROOT_DIR . '/tools/im_tools_light.php' );

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 3 ";
$useMultiSite = false;

$filename = ROOT_DIR . "/$target_folder/c-get-$obj_name.php";

$header_text = "אספקות";

// transform value
$trans = [];
//$trans["task_template"] = "get_task_link";
$trans["supplier"]  = "get_supplier_name";
$trans["paid_date"] = "display_date";
$trans["status"]    = "display_status";

$single_url = "../supplies/supply-get.php";

$display_url                = array();
$display_url["business_id"] = "../business/c-get-business_info.php?id=";

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

$query = " status in (1, 3, 5)";
//$query = "date > date_sub(curdate(), interval 7 day)";
