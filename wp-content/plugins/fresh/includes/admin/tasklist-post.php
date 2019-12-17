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

$operation = get_param( "operation" );
// print "op=" . $operation . "<br/>";
switch ( $operation ) {
	case "create":
	case "create_tasks":
		print "creating tasks<br/>";
		create_tasks( null, true );
		break;
	case "delivered": // Done
		$ids = get_param_array( "ids" );
		foreach ( $ids as $id ) {
			$T = new Focus_Tasklist( $id );
			$T->ended();
			print "delivered";
		}
		break;
	case "end":
		$task_id = get_param( "id" );
		print $task_id . "<br/>";
		$T       = new Focus_Tasklist( $task_id );
		$T->ended();
//		if ( is_array( $T->getRepeatFreq() ) ) {
		create_tasks( $T->getRepeatFreq() );
//		}
		redirect_back();
		break;
	case "cancel":
		$task_id = get_param( "id" );
		$sql     = "UPDATE im_tasklist SET ended = now(), status = " . eTasklist::canceled .
		           " WHERE id = " . $task_id;
		sql_query( $sql );
		create_tasks( null, false );
		redirect_back();
		break;
	case "postpone":
		$task_id = get_param( "id" );
		$T       = new Focus_Tasklist( $task_id );
		$T->Postpone();
		create_tasks( null, false );
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
