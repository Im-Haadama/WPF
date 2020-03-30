<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/01/19
 * Time: 18:21
 */

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once( FRESH_INCLUDES . "/fresh/multi-site/imMulti-site.php" );
// header( ImMultiSite::CORS() );

require_once( FRESH_INCLUDES . "/core/fund.php" );
require_once( FRESH_INCLUDES . "/focus/Tasklist.php" );

require_once( FRESH_INCLUDES . "/init.php" );

$operation = GetParam( "operation" );
// print "op=" . $operation . "<br/>";
switch ( $operation ) {
	case "delivered": // Done
		$ids = GetParamArray( "ids" );
		foreach ( $ids as $id ) {
			$T = new Focus_Tasklist( $id );
			$T->ended();
			print "delivered";
		}
		break;
	case "end":
		$task_id = GetParam( "id" );
		print $task_id . "<br/>";
		$T       = new Focus_Tasklist( $task_id );
		$T->ended();
		redirect_back();
		break;
	case "cancel":
		$task_id = GetParam( "id" );
		$sql     = "UPDATE im_tasklist SET ended = now(), status = " . enumTasklist::canceled .
		           " WHERE id = " . $task_id;
		sql_query( $sql );
		redirect_back();
		break;
	case "postpone":
		$task_id = GetParam( "id" );
		$T       = new Focus_Tasklist( $task_id );
		$T->Postpone();
		redirect_back();
		break;

	case "check":
		check_condition();
		break;
	case "shit":
		sql_query( "delete from im_tasklist where id >= 423" );
		break;
}

?>