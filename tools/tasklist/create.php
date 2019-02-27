<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/10/17
 * Time: 17:30
 */
if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once( ROOT_DIR . '/tools/im_tools.php' );
require_once( ROOT_DIR . '/wp-config.php' );
require_once( ROOT_DIR . "/niver/data/sql.php" );
require_once( ROOT_DIR . "/tools/tasklist/tasklist.php" );
require_once( ROOT_DIR . "/niver/data/im_simple_html_dom.php" );

print header_text( false, true, false );

print "Creating tasks from templates<br/>";

// create_tasks( get_param( "verbose" ), get_param( "force" ) );
$freq = get_param_array( "freq" );

create_tasks( $freq, true );