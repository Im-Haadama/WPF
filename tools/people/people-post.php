<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/05/17
 * Time: 14:13
 */
require_once "../r-staff.php";
require_once( "../account/account.php" );
require_once( "../gui/inputs.php" );
require_once( "people.php" );

if ( ! isset( $_GET["operation"] ) ) {
	print "bad usage 1";
	die( 1 );
}
$operation = $_GET["operation"];

switch ( $operation ) {
	case "display":
		$user = wp_get_current_user();
		print $user->id;

		if ( $user->id == 1 ) {
			print print_transactions();
		} else {
			print print_transactions( $user->id );
		}
		break;

	case "display_all":
		print print_transactions();

		break;

	case "add_time":
		$start      = $_GET["start"];
		$end        = $_GET["end"];
		$date       = $_GET["date"];
		$project    = $_GET["project"];
		$user_id    = $_GET["user_id"];
		$vol        = $_GET["vol"];
		$traveling  = $_GET["traveling"];
		$extra_text = $_GET["extra_text"];
		$extra      = $_GET["extra"];

		if ( isset( $_GET["user_id"] ) ) {
			$user_id = $_GET["user_id"];
		} else {
			$user_id = get_user_id();
		}
		// if ($user_id = 1) $user_id = 238;
		add_activity( $user_id, $date, $start, $end, $project, $vol, $traveling, $extra_text, $extra );
		break;

	case "show_all":
		$month = $_GET["month"];
		show_all( $month );
		break;
	case "delete":
		// $params = explode(',', $_GET["params"]);
		$sql = "DELETE FROM im_working_hours WHERE id IN (" . $_GET["params"] . ")";
		// print $sql;
		$result = mysqli_query( $conn, $sql );
		// var_dump($result);
//		print "done delete";
		break;

	default:
		print "bad usage 2";
		die( 2 );

}

function show_all( $month ) {
	global $conn;

	$a = explode( "-", $month );
	$y = $a[0];
	$m = $a[1];

	$sql = "select distinct user_id, report " .
	       " from im_working_hours h " .
	       " join im_working w " .
	       " where month(date)=" . $m .
	       " and year(date) = " . $y .
	       " and h.user_id = w.worker_id ";
	// print $sql;
	$result = mysqli_query( $conn, $sql );
	while ( $row = mysqli_fetch_row( $result ) ) {
		$u = $row[0];

		if ( $row[1] ) {
			print gui_header( 1, get_user_name( $u ) );
			print print_transactions( $u, $m, $y );
		}
	}

}



