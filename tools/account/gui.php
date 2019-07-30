<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:24
 */
if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

// require_once( TOOLS_DIR . "/r-shop_manager.php" );

//function gui_select_client( $active_days, $new = false ) {
//	if ( $active_days > 0 ) {
//		$sql_where = "where id in (select client_id from im_client_accounts where DATEDIFF(now(), date) < " . $active_days;
//		if ( $new ) {
//			$sql_where .= " union select id from wp_users where DATEDIFF(now(), user_registered) < 3";
//		}
//		$sql_where .= ")";
//		$sql_where .= "order by 2";
//	} else {
//		$sql_where = "where 1 order by 2";
//	}
//
//	// print $sql_where;
//	return gui_select_table( "client_select", "wp_users", 2, "", "", "display_name",
//		$sql_where );
//}

// $key, $data, $args
function gui_select_client( $id = null, $value, $args = null ) {

	if ( ! $id ) {
		$id = "client_select";
	}

	$events = GetArg($args, "events", null);
	$active_days = GetArg($args, "active_days", null);
	$new = GetArg($args, "new", false);

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
	return gui_select_table( $id, "wp_users", $value, $events, "", "display_name",
		$sql_where, true, true );
}

function gui_select_client_type( $id, $value, $events = null ) {
	// print $sql_where;
	$none = array( "id" => 0, "type" => "רגיל" );

	return gui_select_table( $id, "im_client_types", $value, $events, array( $none ), "type",
		null, true );
//		$sql_where );
}

function gui_select_path_code( $id, $selected = 0, $events = "" ) {
	return gui_select_table( $id, "im_missions", $selected, $events, null,
		"path_code", "where date > CURDATE()", true, false, null, "path_code" );
}

function gui_select_mission( $id, $selected = 0, $events = "" ) {
//	IFNULL(DATE,NOW()) >= NOW()
	$sql_where = " where date >= curdate() or date is null";
	// print "events: " . $events . "<br/>";

	// print $sql_where;
	//print "selected: " . $selected . "<br/>";
//	$s = "";
//	if ($selected) $s = $selected . ") " . get_mission_name($selected);
	return gui_select_table( $id, "im_missions", $selected, $events, array( 1 ),
		"ifnull(concat (name, ' ', DAYOFMONTH(date), '/', month(date)), name)", $sql_where, true, false, "date" );
}

function gui_edit_user( $user_id ) {
	print gui_hyperlink( "", "../account/get-customer-account.php?customer_id=" . $user_id );
}

function gui_select_payment( $id, $events, $default ) {
	return gui_select_table( $id, "im_payments", $default, $events );
}