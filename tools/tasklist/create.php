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
require_once( ROOT_DIR . "/agla/sql.php" );
require_once( "tasklist.php" );
require_once( "../multi-site/im_simple_html_dom.php" );

print header_text( false );

create_tasks( true );