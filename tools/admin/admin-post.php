<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once(ROOT_DIR . "/tools/im_tools.php");
get_sql_conn() || die("not connected!");
require_once( '../r-shop_manager.php' ); // for authentication
require_once("admin.php");

$operation = get_param("operation", false, null);

if ($operation) handle_admin_operation($operation);
