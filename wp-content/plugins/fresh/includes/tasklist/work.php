<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/04/19
 * Time: 19:01
 */

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}


require_once( "Tasklist.php" );

$u = wp_get_current_user();

if ( ! $u ) {
	die( "יש להתחבר" );
}

$u_id = $u->id;

print header_text( false, true, true, array( "/core/gui/client_tools.js", "work.js" ) );
// Show last 5 new tasks.
// Allow priority change. Set date.

$last_tasks = sql_query_array_scalar( "SELECT id FROM im_tasklist " .
                                      " WHERE status = " . eTasklist::waiting .
                                      " AND owner = " . $u_id .
                                      " ORDER BY id DESC " .
                                      " LIMIT 5 " );

print Core_Html::gui_header( 1, "משימות חדשות");
print task_table( $last_tasks );

print Core_Html::gui_header( 1, "התחל עבודה" );
print gui_select_project( "project", null, "project_selected()");

// Show next task to handle