<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/03/17
 * Time: 22:41
 */

require_once( "../im_tools.php" );
// TODO: require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( '../maps/build-path.php' );
require_once( ROOT_DIR . '/delivery/missions/Mission.php' );
require_once( '../orders/Order.php' );
require_once( "../supplies/Supply.php" );
require_once( ROOT_DIR . "/init.php" );

// Get parameters.
$debug = get_param( "debug", false, false );
$missing = get_param( "missing" );
$week = get_param("week");
$operation = get_param("operation", false, "show_missions");

//////////////////////////////////////////////////////////////////////////////////////////////
$id = get_param("id", false);
if ($id) {
	print header_text( false, true, true, "delivery.js" );
	show_route($id, $debug, $missing);
	return;
}

switch ($operation)
{
    case "show_today_missions":
        break;

    case "show_coming_week":
	    $sql = "SELECT id FROM im_missions WHERE date = curdate()";
	    show_missions($sql);
	    break;

	case "show_this_week":
		$sql = "SELECT id FROM im_missions WHERE date = curdate()";
		show_missions($sql);
		break;

	case "show_path":
        $mission_id = get_param("mission");
	    show_route($mission_id, $debug, $missing);
	    break;
}


//$missions   = get_param_array( "mission_ids" );
//if ( ! $missions ) {
//	if ( isset( $week ) ) {
//		$missions = sql_query_array_scalar( "SELECT id FROM im_missions WHERE date >= " . quote_text( $week ) .
//		                                    " AND date < DATE_ADD(" . quote_text( $week ) . ", INTERVAL 1 WEEK)" );
//	} else {
//
//	}
//}
