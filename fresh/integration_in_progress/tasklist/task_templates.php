<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/10/17
 * Time: 08:01
 */

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( FRESH_INCLUDES . "/core/data/sql.php" );
require_once( FRESH_INCLUDES . "/core/fund.php" );
require_once( FRESH_INCLUDES . "/fresh/people/people.php" );
require_once( FRESH_INCLUDES . '/core/gui/window.php' );


$operation = GetParam( "operation" );

switch ( $operation ) {
	case "cancel":
		$id = GetParam( "id" );
		if ( ! is_numeric( $id ) ) {
			print "must send numeric id to cancel";
			die( 1 );
		}
		sql_query( "DELETE FROM im_task_templates WHERE " .
		           " id = " . $id );
		redirect_back();
		break;
}