<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/04/18
 * Time: 15:55
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( ROOT_DIR . "/im_tools.php" );

require_once( ROOT_DIR . '/multi-site/sync.php' );
require_once( ROOT_DIR . "/init.php" );

$debug = get_param("debug", false, false);
sync_from_master($debug);
