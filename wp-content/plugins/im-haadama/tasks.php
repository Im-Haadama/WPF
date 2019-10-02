<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

add_shortcode( 'task_focus', 'task_focus' ); // [task_focus]
add_shortcode( 'task_greeting', 'task_greeting' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( dirname( __FILE__) ) ) ) );
}

require_once (ROOT_DIR . '/niver/data/sql.php');
require_once (ROOT_DIR . '/niver/wp.php');
require_once (ROOT_DIR . '/focus/focus.php');

// require_once( TOOLS_DIR . '/im_tools.php' );

function task_focus()
{
	focus_init();
	// im_init();
//	$url = get_url();
//	print "u=$url";
	$operation = get_param("operation", false, null);
	if ($operation) {
		 handle_focus_operation($operation);
		return;
	}
	print active_tasks();
}

function task_greeting()
{
	focus_init();
	print greeting();
}


