<?php

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname(dirname( dirname( __FILE__)  ) ));
}

require_once(ROOT_DIR . '/niver/gui/sql_table.php');
require_once (ROOT_DIR . '/im-config.php');

update_user_meta( 10147, '_client_type', 'legacy' );
update_user_meta( 10146, '_client_type', 'legacy');

print "done";
//$teams = sql_query_array_scalar("select id, team_name from im_working_teams where manager = " . get_user_id());
//foreach($teams as $team) {
//	$manager = team_manager( $team );
//	team_add_worker($team, $manager);
//}
