<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) )) ;
}
require_once(ROOT_DIR . '/im-config.php');
require_once(ROOT_DIR . "/init.php" );
require_once(ROOT_DIR . "/niver/web.php");
require_once("suppliers.php");
require_once (ROOT_DIR . '/niver/gui/gem.php');
require_once(ROOT_DIR . "/niver/data/data.php");

// print header_text(false, true, true, array("/niver/gui/client_tools.js", "/niver/data/data.js"));

$operation = get_param("operation", false, null);
if ($operation) {
	handle_supplier_operation($operation);
	return;
}

$id = get_param("id");

if ($id)
{
	$args = array();
	$args["edit"] = true;
	$args["add_checkbox"] = true;
	$args["transpose"] = true;

	print GuiRowContent("im_suppliers", $id, $args);
	print gui_button("btn_save", 'data_save_entity(\'im_suppliers\', ' . $id .')', "שמור");
	return;
}


$suppliers = sql_query_array("select * from im_suppliers where active = 1", true);

$args["links"] = array("id" => add_to_url(array("operation" => "show_supplier", "id"=>"%s")));

// $links = array("admin.php?id=%s");
$sum = array();
$args["page"] = get_param("page", false, -1);

print HeaderText($args);
print GuiTableContent("im_suppliers",null, $args);
//print gui_table($suppliers, "tbl_suppliers", true, true, $sum, null,
//	null, null, $links);

print gui_hyperlink("add", add_to_url("operation", "add"));

?>
