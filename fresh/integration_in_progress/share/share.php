<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/04/19
 * Time: 07:25
 */




if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

// TODO:
if ( ! im_user_can( "share_events" ) ) {
	die( "no permissions" );
}