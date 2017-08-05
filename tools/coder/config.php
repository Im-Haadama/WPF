<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:28
 */

$table_prefix = "im_";
$obj_name     = "supplies";
$table_suffix = "";
$table_name   = $table_prefix . $obj_name . $table_suffix;
$order        = "order by 3  desc";

// transform value
$trans             = [];
$trans["supplier"] = "get_supplier_name";
$trans["date"]     = "get_week";

// Fields to skip in horizontal
$skip_in_horizontal = array( "text" );

