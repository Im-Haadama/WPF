<?php

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

//require_once( ROLES_ABSPATH . '/im-config.php' );
//require_once( ROLES_ABSPATH . '/init.php' );
//require_once( ROLES_ABSPATH . '/org/gui.php' );
//require_once( ROLES_ABSPATH . '/routes/gui.php' );

$operation = GetParam('operation', true);
$roles = Roles::instance();
if ( ! get_user_id(true) ) die('Not connected');

print $roles->handle_operation($operation);