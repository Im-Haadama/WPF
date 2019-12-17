<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/10/17
 * Time: 17:30
 */
if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( FRESH_INCLUDES . '/wp-config.php' );
require_once( FRESH_INCLUDES . "/core/data/sql.php" );
require_once( FRESH_INCLUDES . "/fresh/tasklist/tasklist.php" );
require_once( FRESH_INCLUDES . "/core/data/im_simple_html_dom.php" );

print header_text( false, true, false );

print "Creating tasks from templates<br/>";

// create_tasks( get_param( "verbose" ), get_param( "force" ) );
$freq = get_param_array( "freq" );

create_tasks( $freq, true );