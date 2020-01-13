<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) )) ;
}
require_once(FRESH_INCLUDES . '/im-config.php');
require_once(FRESH_INCLUDES . "/init.php" );
require_once( FRESH_INCLUDES . "/core/web.php" );
require_once( "suppliers.php" );
require_once( FRESH_INCLUDES . '/core/gui/gem.php' );
require_once( FRESH_INCLUDES . "/core/data/data.php" );

$operation = GetParam("operation", false, null);
if ($operation) {
	handle_supplier_operation($operation);
	return;
}

$id = GetParam("id");

if ($id)
{
	$args = array();
	$args["edit"] = true;
	$args["add_checkbox"] = true;
	$args["transpose"] = true;

	print GuiRowContent("im_suppliers", $id, $args);
	print Core_Html::GuiButton("btn_save", 'data_save_entity(\'im_suppliers\', ' . $id .')', "שמור");
	return;
}


$suppliers = sql_query_array("select * from im_suppliers where is_active = 1", true);

$args["links"] = array("id" => AddToUrl(array( "operation" => "show_supplier", "id" =>"%s")));

// $links = array("admin.php?id=%s");
$sum = array();
$args["page"] = GetParam("page", false, -1);

print HeaderText($args);
print GuiTableContent("im_suppliers",null, $args);
//print gui_table($suppliers, "tbl_suppliers", true, true, $sum, null,
//	null, null, $links);

print Core_Html::GuiHyperlink("add", AddToUrl("operation", "add"));

?>
