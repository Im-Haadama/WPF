<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/01/19
 * Time: 18:21
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once( ROOT_DIR . "/tools/im_tools.php" );
require_once( "../multi-site/imMulti-site.php" );
header( ImMultiSite::CORS() );

require_once( ROOT_DIR . "/agla/fund.php" );
require_once( "Tasklist.php" );

$operation = get_param( "operation" );
// print "op=" . $operation . "<br/>";
switch ( $operation ) {
	case "delivered": // Done
		$ids = get_param_array( "ids" );
		foreach ( $ids as $id ) {
			$T = new Tasklist( $id );
			$T->ended();
			print "delivered";
		}
		break;
	case "start":
		$task_id = get_param( "id" );
		$sql     = "UPDATE im_tasklist SET started = now(), status = " . eTasklist::started .
		           " WHERE id = " . $task_id;
		sql_query( $sql );

		$sql = "SELECT task_url FROM im_task_templates WHERE id = "
		       . " (SELECT task_template FROM im_tasklist WHERE id = " . $task_id . ")";
		$url = sql_query_single_scalar( $sql );
		if ( strlen( $url ) > 1 ) // print $url;
		{
			header( "Location: " . $url );
		} else {
			redirect_back();
		}
		break;
	case "end":
		$task_id = get_param( "id" );
		print $task_id . "<br/>";
		$T       = new Tasklist( $task_id );
		$T->ended();
		redirect_back();
		create_tasks( $T->getRepeatFreq() );
		break;
	case "cancel":
		$task_id = get_param( "id" );
		$sql     = "UPDATE im_tasklist SET ended = now(), status = " . eTasklist::canceled .
		           " WHERE id = " . $task_id;
		sql_query( $sql );
		redirect_back();
		break;
	case "check":
		check_condition();
		break;
}

?>
