<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:24
 */
require_once( "../im_tools.php" );


function gui_select_client( $active_days ) {
	if ( $active_days > 0 ) {
		$sql_where = "where id in (select client_id from im_client_accounts where DATEDIFF(now(), date) < " . $active_days . ") order by 2";
	} else {
		$sql_where = "where 1 order by 2";
	}

	return gui_select_table( "client_select", "wp_users", 2, "", "", "display_name",
		$sql_where );
}