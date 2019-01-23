<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

define( 'STORE_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );

print "STORE_DIR: " . ROOT_DIR . "<br/>";
require_once( ROOT_DIR . "/tools/catalog/bundles.php" );

$table_prefix = "im_";
$root_file    = realpath( ROOT_DIR ) . '/tools/im_tools.php';


$target_folder = "/tools/catalog";

$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 3 ";
$useMultiSite = false;

$header_text = "מארזים";

// transform value
$trans             = [];
$trans["prod_id"]  = "get_product_name";
$insert["prod_id"] = "gui_select_product";

// $select["prod_id"] = gui_input_select_from_datalist;
//$trans["supplier"]  = "get_supplier_name";
//$trans["paid_date"] = "display_date";
//$trans["status"]    = "display_status";

$single_url = "c-get-bundles.php";

// display_part_name()'
$actions = array( array( "בטל", "bundles-post.php?operation=disable&id=" ) );
//$actions = array( array( "שכפל", "missions.php?operation=dup&id=" ) );
//				 array("בוצע", "tasklist.php?operation=end&id="),
//	             array("בטל", "tasklist.php?operation=cancel&id="));

$defaults         = [];
$defaults["date"] = "date(\"m/d/y\")";

// Fields to skip in horizontal
$skip_in_horizontal = array();

// $query = " status in (" . SupplyStatus::NewSupply . ", " . SupplyStatus::Sent . ", " . SupplyStatus::Supplied . ")";
//$query = "date > date_sub(curdate(), interval 7 day)";
$query = " is_active = true ";

$datalist = 'print gui_datalist( "products", "im_products", "post_title", true );';
