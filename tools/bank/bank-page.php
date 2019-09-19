<?php

require_once("../r-shop_manager.php");
require_once( "admin.php" );
require_once(ROOT_DIR . "/niver/web.php");

$debug = get_param( "debug", false, false );

print header_text( true, true, is_rtl());

print load_scripts(array("/tools/admin/data.js", "/niver/gui/client_tools.js"));
// focus_init();

$operation = get_param( "operation", false, null );
if ( $operation ) {
	switch ( $operation ) {
		case "new_task":
			print greeting();
			break;
	}

	handle_bank_operation( $operation );

	return;
}
//$project_id = get_param( "project_id" );
//if ( $project_id ) {
//	$args            = [];
//	$args["project"] = $project_id;
//	print active_tasks( $args );
//
//	return;
//}

// print "X" . get_param("templates") != null . "X";

if ( get_param( "templates", false, "none" ) !== "none" ) {
	print header_text( false, true, true, array(
		"/niver/gui/client_tools.js",
		"/tools/admin/data.js",
		"/tools/admin/admin.js"
	) );

	print gui_hyperlink( "הוסף תבנית", get_url( true ) . "?operation=new_template" );

	show_templates( get_url( 1 ) );

	return;
}

$row_id = get_param( "row_id", false );
if ( $row_id ) {
	im_init(  );
	show_task( $row_id );

	return;
}

$time_filter = get_param( "time", false, true );

$args["url"] = basename( __FILE__ );
// print "url=". $args["url"];

im_init(  );
print greeting();

print gui_hyperlink('Create Receipts', add_to_url("operation" , "receipts")); print " ";
print gui_hyperlink('Mark payments', add_to_url("operation", "payments")); print " ";
print gui_hyperlink('Import bank pages', add_to_url("operation" ,"import")); print " ";
print gui_hyperlink('Edit transation types', add_to_url("operation" ,"transaction_types")); print " ";

$account_id = 1;
$page = get_param("page", false, 1);
$rows_per_page = 20;
// $args["debug"] = (get_user_id() == 1);
$offset = ($page - 1) * $rows_per_page;

print GuiTableContent("im_banking", "select * from im_bank where account_id = " . $account_id .
	" order by date desc limit $rows_per_page offset $offset ", $args);

print gui_hyperlink("Older", add_to_url("page", $page + 1));

