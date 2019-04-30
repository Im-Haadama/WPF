<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/04/19
 * Time: 07:25
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/tools/im_tools.php" );

// TODO:
if ( ! im_user_can( "share_events" ) ) {
	die( "no permissions" );
}