<?php

add_shortcode( 'task_focus', 'task_focus' );


error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( dirname( dirname( IM_HAADAMA_PLUGIN ) ) ) ) . "/tools" );
}

if (file_exists(TOOLS_DIR . "/admin/focus.php"))
	require_once (TOOLS_DIR  . "/admin/focus.php");
else {
	print "Module focus missing";
	return;
}

function task_focus()
{
	show_active_tasks();
}
