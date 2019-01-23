<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/10/17
 * Time: 17:30
 */
if ( ! defined( 'TOOLS_DIR' ) ) {
	define( TOOLS_DIR, dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . '/im_tools.php' );
require_once( dirname( TOOLS_DIR ) . '/wp-config.php' );
require_once( ROOT_DIR . "/niver/sql.php" );
require_once( "tasklist.php" );
require_once( ROOT_DIR . "/niver/im_simple_html_dom.php" );

print header_text( false, true, false );

print "Creating tasks from templates<br/>";


// create_tasks( get_param( "verbose" ), get_param( "force" ) );
$freq = get_param_array( "freq" );

if ( count( $freq ) < 1 ) {
	$freq = sql_query_array_scalar( "select DISTINCT repeat_freq from im_task_templates" );
}


create_tasks( $freq, true );