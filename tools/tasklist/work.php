<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/04/19
 * Time: 19:01
 */

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once( ROOT_DIR . '/tools/im_tools.php' );

require_once( "Tasklist.php" );

// Show last 5 new tasks.
// Allow priority change. Set date.

$last_tasks = sql_query_array_scalar( "SELECT id FROM im_tasklist " .
                                      " WHERE status = " . eTasklist::waiting .
                                      " AND owner = " . wp_get_current_user()->id .
                                      " ORDER BY id DESC " .
                                      " LIMIT 5 " );

print task_table( $last_tasks );

// Show next task to handle