<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once(ROOT_DIR . "/tools/im_tools.php");
im_init();
get_sql_conn() || die("not connected!");
require_once( '../r-shop_manager.php' ); // for authentication
require_once ("data.php");
require_once (ROOT_DIR . '/niver/gui/gem.php');

$operation = get_param("operation", true);

// http://fruity.co.il/tools/admin/data.php?table_name=im_business_info&operation=update&id=2560&ref=22
$table_name = get_param("table_name", true);

// TODO: Check permission to table

if ( $operation )
	switch ( $operation ) {
		case "new":
			data_save_new($table_name);
			print "done";
			break;

		case "update":
			if (update_data($table_name))
				print "done";
			break;

		case "search":
			$args = array();
			$ids = data_search($table_name);
			$args["sql"] = "select * from $table_name where id in (" . comma_implode($ids). ")";
			print GemTable($table_name, "Search results", $args);
			break;

		default:
			print "no operation handler for $operation<br/>";
			die( 2 );
	}
