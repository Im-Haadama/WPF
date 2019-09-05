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

$edit = get_param("edit", false, false);
show_last_supplies($edit);

function show_last_supplies($edit = false)
{
	global $general_selectors;
	global $this_url;
	global $entity_name_plural;
	global $table_name;
	global $update_event;

	$args = array("events" => $update_event,
	              "edit" => $edit,
		"links" => array("id" => "supply-get.php?id=%s"),
		"selectors" => array("status" => "gui_select_supply_status", "supplier" => "gui_select_supplier"));

	print gui_header( 1, "ניהול " . $entity_name_plural );

	$sql = "select id, status, date(date), supplier, text, business_id, paid_date from $table_name where status != " . SupplyStatus::Deleted . " order by id desc limit 30";
	$args["header_fields"] = array("Id", "Status", "Date", "Supplier", "Comments", "Transaction", "Pay date");

	// print $sql;
	print GuiTableContent( $table_name, $sql, $args );

}
