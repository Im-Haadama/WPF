<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:24
 */
if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) );
}

require_once( FRESH_INCLUDES . "/core/fund.php" );
require_once( FRESH_INCLUDES . "/core/gui/inputs.php" );



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
function gui_select_client( $id, $value, $args = null )
{
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

	$select_args = array("name" => "client_displayname(id)", "include_id" => 1, "where"=> $sql_where, "events" => $events, "value"=>$value, "datalist" => 1);
	// var_dump($select_args);
	// return GuiSelectTable( $id, "wp_users", $select_args);
	return GuiAutoList( $id, "users", $select_args);
}

function gui_select_client_type( $id, $value, $events = null ) {
	$none = array( "id" => 0, "type" => "רגיל" );

	return gui_select_table( $id, "im_client_types", $value, $events, array( $none ), "type",
		null, true );
//		$sql_where );
}

function gui_select_path( $id, $selected = 0, $args = null )
{
//	return gui_select_table( $id, "im_missions", $selected, $events, null,
//		"path_code", "where date > CURDATE()", true, false, null, "path_code" );
	$args["selected"] = $selected;
	$args["name"] = "description";
	return GuiSelectTable($id, "im_paths", $args);
}


function gui_select_payment( $id, $events, $default ) {
	return gui_select_table( $id, "im_payments", $default, $events );
}