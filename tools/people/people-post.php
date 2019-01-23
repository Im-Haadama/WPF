<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/05/17
 * Time: 14:13
 */
// ini_set( 'display_errors', 'on' );
require_once( "../account/account.php" );
require_once( ROOT_DIR . "/agla/gui/inputs.php" );
require_once( "people.php" );

if ( ! isset( $_GET["operation"] ) ) {
	print "bad usage 1";
	die( 1 );
}
$operation = $_GET["operation"];

switch ( $operation ) {
	case "get_balance":
		$user_id = $_GET["user_id"];
		$date    = $_GET["date"];
		print balance( $date, $user_id );
		break;
	case "display":
	case "display_all":
		$month = null;
		$year  = null;
		if ( isset( $_GET["month"] ) ) {
			$m     = $_GET["month"];
			$month = substr( $m, 5 );
			$year  = substr( $m, 0, 4 );

		}
		$user = get_current_user_id();

		if ( current_user_can( "working_hours_all" ) ) {
			$user = 0;
		}
		print print_transactions( 0, $month, $year );

		break;

	case "add_time":
		$start      = $_GET["start"];
		$end        = $_GET["end"];
		$date       = $_GET["date"];
		$project    = $_GET["project"];
		$worker_id  = $_GET["worker_id"];
		// print "wid=" . $worker_id . "<br/>";
		$vol        = $_GET["vol"];
		$traveling  = $_GET["traveling"];
		$extra_text = $_GET["extra_text"];
		$extra      = $_GET["extra"];

		if ( isset( $_GET["user_id"] ) ) {
			$user_id = $_GET["user_id"];
		} else {
			if ( isset( $_GET["worker_id"] ) ) {
				// $w       = $_GET["worker_id"];
				$user_id = sql_query_single_scalar( "SELECT worker_id FROM im_working WHERE id = " . $worker_id );
				//print "uid=" . $user_id . "<br/>";
			} else {
				$user_id = get_user_id();
			}
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
	if ( ! current_user_can( "show_all_hours" ) ) {
		print "אין הרשאה";
		die ( 1 );
	}

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
	$result = sql_query( $sql);

	while ( $row = mysqli_fetch_row( $result ) ) {
		$u = $row[0];

		if ( $row[1] ) {
			print gui_header( 1, get_user_name( $u ) );
			print get_customer_email( $u ) . "<br/>";

			print print_transactions( $u, $m, $y );
		}
	}
}
