<?php
// Created 14/8/2019
// By: agla

define ('FRESH_INCLUDES', dirname(dirname(dirname(__FILE__))));

require_once( '../r-shop_manager.php' );
require_once( FRESH_INCLUDES . '/niver/web.php' );
require_once(FRESH_INCLUDES . '/fresh/supplies/Supply.php');
require_once(FRESH_INCLUDES . '/fresh/catalog/gui.php');

$entity_name_plural = "Supplies";
$table_name = "im_supplies";
$general_selectors = array("supplier" => "gui_select_supplier");
// function update_table_field(post_file, table_name, id, field_name, finish_action) {

$update_event = 'onchange="update_table_field(\'/niver/data/data.php\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';

print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/fresh/supplies/supply.js" ) );

global $user_ID; // by wordpress.

$id = get_param("id", false);
require_once(FRESH_INCLUDES . '/init.php');

print gui_div("log");

$operation = get_param("operation", false, "show_all");
if ($operation) {
    handle_supplies_operation($operation);
    return;
}

