<?php




if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES',  dirname(dirname( dirname( __FILE__)  ) ));
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

init();

print sql_query_single_scalar("select client_displayname(1)");
