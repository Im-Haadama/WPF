<?php

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

get_sql_conn() || die("not connected!");
require_once( '../r-shop_manager.php' ); // for authentication
require_once( "admin.php" );

$operation = GetParam("operation", false, null);

if ($operation) handle_admin_operation($operation);
