<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/04/18
 * Time: 11:04
 */

// require_once( ROOT_DIR . "/tools/suppliers/suppliers.php" );

require_once( "../../im-config.php" );
$conn = new mysqli( IM_DB_HOST, IM_DB_NAME, IM_DB_PASSWORD, IM_DB_NAME );
mysqli_set_charset( $conn, 'utf8' );
$obj_name = "suppliers";


$table_prefix  = "im_";
$root_file     = ROOT_DIR . '/tools/im_tools.php';
$target_folder = "tools/suppliers";

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 3 ";
$useMultiSite = false;

$header_text = "ספקים";

// transform value
$trans = [];
//$trans["task_template"] = "get_task_link";
$trans["supplier"]  = "get_supplier_name";
$trans["paid_date"] = "display_date";
$trans["status"]    = "display_status";

$single_url = "../suppliers/c-get-suppliers.php";

// display_part_name()'
//$actions = array( array( "שכפל", "missions.php?operation=dup&id=" ) );
//				 array("בוצע", "tasklist.php?operation=end&id="),
//	             array("בטל", "tasklist.php?operation=cancel&id="));

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

// $query = " status in (" . SupplyStatus::NewSupply . ", " . SupplyStatus::Sent . ", " . SupplyStatus::Supplied . ")";
// $query = " active = 1 ";
