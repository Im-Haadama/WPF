<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ))));
}
echo FRESH_INCLUDES;
require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . '/core/data/sql.php' );
require_once( FRESH_INCLUDES . '/core/gui/text_inputs.php' );


require_once( FRESH_INCLUDES . "/focus/focus_class.php" );

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