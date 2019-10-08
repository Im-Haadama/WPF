<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( ROOT_DIR . '/im-config.php' );
require_once( ROOT_DIR . "/init.php" );

require_once( "Mission.php" );

$debug = get_param( "debug", false, false );

print header_text( true, true, is_rtl(), array( "/niver/data/data.js", "/niver/gui/client_tools.js" ) );
// focus_init();

$operation = get_param( "operation", false, null );
$entity_name = "mission";
$table_name = "im_missions";

if ( $operation ) {
	handle_mission_operation( $operation );

	return;
}

// print "X" . get_param("templates") != null . "X";

if ( get_param( "templates", false, "none" ) !== "none" ) {
	print header_text( false, true, true, array(
		"/niver/gui/client_tools.js",
		"/niver/data/data.js",
		"/fresh/admin/admin.js"
	) );

	print gui_hyperlink( "add repeating task", get_url( true ) . "?operation=new_template" );

	// Print links to filter task templates (repeating tasks).
	// Team
	// Freq
	$args = array();
//	$args["events"] = "filter_repeat_time()";
//	print gui_select_repeat_time("select_repeat", "", $args);

	show_templates( get_url( 1 ) );

	return;
}
$team_id = get_param( "team" );
if ( $team_id ) {
	global $admin_scripts;
	focus_init( $admin_scripts );
	show_team( $team_id, get_param( "active_only", false, true ) );

	return;
}

$row_id = get_param( "row_id", false );
if ( $row_id ) {
	print gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = 1;
	$args["skip_id"]      = true;
	$args["transpose"] = true;

	print GuiRowContent( $table_name, $row_id, $args );
	print gui_button( "btn_save", "save_entity('/routes/missions/missions-post.php', '$table_name', " . $row_id . ')', "שמור" );

	return;
}

$time_filter = get_param( "time", false, true );

$args["url"] = basename( __FILE__ );
// print "url=". $args["url"];

$url = get_url( 1 );
print greeting();


// Tasks I need to handle
print gui_header( 1, "$entity_name" );
$args["query"]       = " owner = " . get_user_id();
$args["limit"]       = get_param( "limit", false, 10 );
$args["active_only"] = get_param( "active_only", false, true );
print active_missions( $args );
// if (get_user_id() != 1) return;

