<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

//require_once( FRESH_ABSPATH . '/im-config.php' );
//require_once( FRESH_ABSPATH . '/init.php' );
//require_once( FRESH_ABSPATH . '/org/gui.php' );
//require_once( FRESH_ABSPATH . '/routes/gui.php' );

$operation = get_param('operation', true);
$focus = Focus::instance();

if ( ! get_user_id(true) ) die('Not connected');

print $focus->handle_operation($operation);