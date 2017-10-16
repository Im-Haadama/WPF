<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/10/17
 * Time: 23:04
 */
if ( ! defined( TOOLS_DIR ) ) {
	define( TOOLS_DIR, dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . '/im_tools.php' );
require_once( dirname( TOOLS_DIR ) . '/wp-config.php' );
require_once( TOOLS_DIR . '/sql.php' );
require_once( 'tasklist.php' );

if ( ! isset( $_GET["condition"] ) ) {
	print "must send condition";
	die ( 1 );
}
$condition = $_GET["condition"];
switch ( $condition ) {
	case "daily":
		if ( ! isset( $_GET["id"] ) ) {
			print "daily must send id";
			die ( 2 );
		}
		$id          = $_GET["id"];
		$last_finish = sql_query_single_scalar( "SELECT max(datediff(curdate(), ended)) FROM im_tasklist WHERE task_template = " . $id );
		// print $last_finish;
		print $last_finish >= 1;
		break;
}
