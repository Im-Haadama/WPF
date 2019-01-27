<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/04/18
 * Time: 15:55
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . "/im_tools.php" );

require_once( TOOLS_DIR . '/multi-site/sync.php' );

sync_from_master();
