<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/01/19
 * Time: 11:36
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/tools/im_tools_light.php' );

sql_query( "ALTER TABLE im_business_info
ADD net_total DOUBLE;
" );

print "done";