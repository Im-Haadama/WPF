<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/05/17
 * Time: 14:13
 */
require_once "../tools_wp_login.php";
require_once( "../account/account.php" );
require_once( "../gui/inputs.php" );
require_once( "people.php" );

if ( ! isset( $_GET["operation"] ) ) {
	print "bad usage 1";
	die( 1 );
}
$operation = $_GET["operation"];

switch ( $operation ) {
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



