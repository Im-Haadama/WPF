<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/10/17
 * Time: 08:01
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/tools/im_tools.php" );
require_once( ROOT_DIR . "/niver/data/sql.php" );
require_once( ROOT_DIR . "/niver/fund.php" );
require_once( ROOT_DIR . "/tools/people/people.php" );
require_once( ROOT_DIR . '/niver/gui/window.php' );


$operation = get_param( "operation" );

switch ( $operation ) {
	case "cancel":
		$id = get_param( "id" );
		if ( ! is_numeric( $id ) ) {
			print "must send numeric id to cancel";
			die( 1 );
		}
		sql_query( "DELETE FROM im_task_templates WHERE " .
		           " id = " . $id );
		redirect_back();
		break;
}