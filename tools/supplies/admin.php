<?php
// Created 14/8/2019
// By: agla

require_once( '../r-shop_manager.php' );

$entity_name_plural = "הספקות";
$table_name = "im_supplies";
$general_selectors = array("supplier" => "gui_select_supplier");
// function update_table_field(post_file, table_name, id, field_name, finish_action) {

$update_event = 'onchange="update_table_field(\'/tools/admin/data.php\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';

print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js" ) );

$operation = get_param( "operation", false );

global $user_ID; // by wordpress.

if ($operation) {
	switch ( $operation ) {
		default:
			print $operation . " not handled <br/>";
			die(1);

	}
}

$row_id = get_param( "row_id", false );
// if ($row_id) { show_supply($row_id); return; }

show_last_supplies();

function show_last_supplies()
{
	global $general_selectors;
	global $this_url;
	global $entity_name_plural;
	global $table_name;
	global $update_event;

	$args = array("selectors" => $general_selectors,
	              "events" => $update_event,
	              "edit" => true);

	print gui_header( 1, "ניהול " . $entity_name_plural );

	$sql = "select * from $table_name order by id desc limit 30";

	print GuiTableContent( $table_name, $sql, $args );

}
