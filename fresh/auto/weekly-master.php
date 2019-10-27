<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/19
 * Time: 19:05
 */

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/fresh/delivery/missions.php" );

print header_text();

create_missions();

function create_missions() {
	$this_week = date( "Y-m-d", strtotime( "last sunday" ) );
	$sql       = "SELECT id FROM im_missions WHERE FIRST_DAY_OF_WEEK(date) = '" . $this_week . "' ORDER BY 1";
//	print $sql;

	$result = sql_query( $sql );
	while ( $row = sql_fetch_row( $result ) ) {
		$mission_id = $row[0];
		print "משכפל את משימה " . $mission_id . "<br/>";

		duplicate_mission( $mission_id );
	}
}
