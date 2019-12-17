<?php

return;
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

add_shortcode( 'task_focus', 'task_focus' ); // [task_focus]
add_shortcode( 'task_greeting', 'task_greeting' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( dirname( __FILE__) ) ) ) );
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );
require_once( ROOT_DIR . '/core/data/sql.php' );
require_once( ROOT_DIR . '/core/wp.php' );
require_once( ROOT_DIR . '/focus/focus_class.php' );

// require_once( TOOLS_DIR . '/im_tools.php' );

$tasks_scripts = array("/core/gui/client_tools.js", "/core/data/data.js", "/focus/focus.js");
function task_focus()
{
	global $style_file;
	global $tasks_scripts;

	init($tasks_scripts, $style_file);
	require_once( ROOT_DIR . "/focus/gui.php" );
	require_once( ROOT_DIR . '/org/gui.php' );
	require_once( ROOT_DIR . "/routes/gui.php" );

	$operation = get_param("operation", false, "focus_main");

	$args = [];
	if ($operation) {
		 handle_focus_operation($operation, $args);
		return;
	}
	print gui_header(1, "Active Tasks");
	print Focus_Views::active_tasks();
}



