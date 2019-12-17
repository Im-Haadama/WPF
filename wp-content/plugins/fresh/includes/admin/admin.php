<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/08/15
 * Time: 08:19
 */

require_once( "tasklist.php" );
require_once( FRESH_INCLUDES . "/core/web.php" );
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
require_once( FRESH_INCLUDES . '/core/gui/sql_table.php' );
require_once( FRESH_INCLUDES . '/fresh/people/people.php' );
require_once( FRESH_INCLUDES . "/core/fund.php" );
require_once( FRESH_INCLUDES . "/account/gui.php" );
require_once( FRESH_INCLUDES . '/people/people.php' );
require_once( FRESH_INCLUDES . "/gui.php" );
require_once( FRESH_INCLUDES . "/core/gui/gem.php" );

require_once( "common.php" );
require_once( "data.php" );

//$this_url           = $_SERVER['REQUEST_URI'];
//$entity_name        = "משימה";
//$entity_name_plural = "משימות";
//$table_name         = "im_tasklist";

//
//
//$operation = get_param( "operation", false );
//if ( $operation ) {
//	switch ( $operation ) {
////		case "add":
////			$args = array();
////			foreach ( $_GET as $key => $data ) {
////				if ( ! in_array( $key, array( "operation", "table_name" ) ) ) {
////					if ( ! isset( $args["fields"] ) ) {
////						$args["fields"] = array();
////					}
////				}
////				$args["fields"][ $key ] = $data;
////			}
////			$args["edit"] = true;
////			print NewRow( "im_business_info", $args, true );
////			print gui_button( "btn_add", "save_new('im_business_info')", "הוסף" );
////			break;
//
//		case "templates":
//			show_templates();
//			break;
//		default:
//			die( "$operation not handled" );
//	}
//
//	return;
//}

// Selection:
global $user_ID; // by wordpress.

$admin_scripts = array( "/core/gui/client_tools.js", "/core/data/data.js", "/fresh/admin/admin.js" );




//	print gui_header( 1, $title );

//	print greeting();

//	print gui_hyperlink("repeating tasks", $url . "?operation=templates");

//	print " ";

//	print gui_hyperlink("add tasks", $url . "?operation=new_task");

//	print " ";

//	print managed_workers($user_id, $_SERVER['REQUEST_URI']);

//	print " ";

//	print gui_hyperlink("projects", $url . "?operation=projects");

//	$sum     = null;
