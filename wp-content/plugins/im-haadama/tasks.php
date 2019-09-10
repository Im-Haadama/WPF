<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

add_shortcode( 'task_focus', 'task_focus' );
add_shortcode( 'task_greeting', 'task_greeting' );


if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( dirname( dirname( IM_HAADAMA_PLUGIN ) ) ) ) . "/tools" );
}

if (file_exists(TOOLS_DIR . "/admin/admin.php"))
	require_once (TOOLS_DIR  . "/admin/admin.php");
else {
	print "Module focus missing";
	return;
}

require_once( TOOLS_DIR . '/im_tools.php' );

function task_focus()
{
	focus_init();
	// im_init();
//	$url = get_url();
//	print "u=$url";
	$operation = get_param("operation", false, null);
	if ($operation) {
		handle_admin_operation($operation);
		return;
	}
	print active_tasks();
}

function task_greeting()
{
	focus_init();
	print greeting();
}

function focus_init()
{
	static $once = 0;

	if ($once) return;

	$once = 1;
	global $style_file;
	if ( isset( $style_file ) ) {
		$text = "<style>";
		$text .= file_get_contents( $style_file );
		$text .= "</style>";

		print $text;
	}

}