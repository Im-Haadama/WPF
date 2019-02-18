<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

$obj_name      = "bank";
$root_file     = realpath( ROOT_DIR ) . '/tools/business/business.php';
$target_folder = "/tools/business";

require_once( ROOT_DIR . '/im-config.php' );
// require_once( ROOT_DIR . '/tools/business/business.php' );

$im_table_suffix = "";
$table_name      = $im_table_prefix . $obj_name . $im_table_suffix;
$order           = "order by 2 ";

$useMultiSite = false;

$header_text = "מצב חשבון";

$import_csv = true;

$import_key = array( "account_id", "select_bank_account" );

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
