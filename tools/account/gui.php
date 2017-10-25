<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:24
 */
require_once( "../tools_wp_login.php" );


function gui_select_client( $active_days, $new = false ) {
	if ( $active_days > 0 ) {
		$sql_where = "where id in (select client_id from im_client_accounts where DATEDIFF(now(), date) < " . $active_days;
		if ( $new ) {
			$sql_where .= " union select id from wp_users where DATEDIFF(now(), user_registered) < 3";
		}
		$sql_where .= ")";
		$sql_where .= "order by 2";
	} else {
		$sql_where = "where 1 order by 2";
	}

	// print $sql_where;
	return gui_select_table( "client_select", "wp_users", 2, "", "", "display_name",
		$sql_where );
}

function gui_select_mission( $selected = 0, $events = "" ) {
	$sql_where = " where date >= curdate()";

	// print $sql_where;
	return gui_select_table( "mission_select", "im_missions", $selected, $events, "",
		"name", $sql_where );
}

