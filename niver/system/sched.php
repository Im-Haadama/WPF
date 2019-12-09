<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ))));
}
echo ROOT_DIR;
require_once(ROOT_DIR . '/im-config.php');
require_once(ROOT_DIR . '/niver/data/sql.php');
require_once(ROOT_DIR . '/niver/gui/text_inputs.php');


require_once(ROOT_DIR . "/focus/focus_class.php" );

function sched_log($text)
{
	$log_file = 'sched.' . date("w") . ".txt";
	my_log($text, '', $log_file);
}

sched_log("Started");

while (1)
{
	sched_log("Awaken");
	sched_log("doing something");
	// Connect to db.
	$conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	// Save the connection
	get_sql_conn($conn);
	// Do the work
	sched_log(handle_focus_do("create_tasks"));
	// Close the connection
	mysqli_close($conn);
	sched_log("Going to sleep");
	sleep(60);
}