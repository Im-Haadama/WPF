<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/05/17
 * Time: 14:13
 */
// ini_set( 'display_errors', 'on' );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( FRESH_INCLUDES . "/fresh-public/account/account.php" );
require_once( FRESH_INCLUDES . "/core/gui/inputs.php" );
require_once( "people.php" );

if ( ! isset( $_GET["operation"] ) ) {
	print "bad usage 1";
	die( 1 );
}
$operation = $_GET["operation"];
require_once( FRESH_INCLUDES . "/init.php" );

switch ( $operation ) {
	case "get_balance":
		$customer_id = GetParam( "customer_id" );
		$date        = $_GET["date"];
		print balance( $date, $customer_id );
		return;
	case "get_balance_email":
		$email = GetParam( "email" );
		$date  = $_GET["date"];
		print balance_email( $date, $email );
		return;

	case "add_sick_leave":
		$date      = $_GET["date"];
		$project   = $_GET["project"];
		$worker_id = GetParam( "worker_id" );
		if ( isset( $_GET["user_id"] ) ) {
			$user_id = $_GET["user_id"];
		} else {
			if ( isset( $worker_id ) ) {
//				$w       = $_GET["worker_id"];
//				print "w=" . $w;
				$user_id = sql_query_single_scalar( "SELECT user_id FROM im_working WHERE id = " . $worker_id );
				//print "uid=" . $user_id . "<br/>";
			} else {
				$user_id = get_user_id();
			}
		}
		if ( ! $user_id ) {
			print "no user selected";
			die( 1 );
		}
		// if ($user_id = 1) $user_id = 238;
		$result = add_activity_sick_leave( $user_id, $date, $project );
		if ( $result ) {
			print $result;
		}
		return;

	case "add_time":
		return;

	case "show_all":
		print header_text(true);
		$month = GetParam("month", false, date('Y-m'));
		$edit = GetParam("edit");
		$args = array();
		$edit = GetParam("edit", false, false);
		if ($edit)
			$args["add_checkbox"] = true;
		$args["edit"] = $edit;
		$wp_user = get_user_by( 'id', get_user_id() );
		$roles = $wp_user->roles;
		if ( isset( $roles ) and count( array_intersect( array( "hr" ), $roles ) ) >= 1 ) {
			$args["show_salary"] = 1;
			show_all( $month, $args );
		} else {	$a = explode( "-", $month );
			$y = $a[0];
			$m = $a[1];
			print print_transactions( get_user_id(), $m, $y, $args);
		}

		return;
}

$result = "";
switch($operation)
{
	case "edit_worker":
		$id = GetParam("id", true);
		$result = Core_Html::gui_header(1, "Worker info") . GetuserName($id);
		$args = [];
		$args["id_field"] = "id";
		$args["query"] = "is_active = 1 and user_id = $id";
		$args["hide_cols"] = array("is_active" => 1, "volunteer" => 1);
		// $args["selectors"] = array("company_id" =>)
		$result .= GemTable("im_working",$args);
		break;

	default:
		print "bad usage 2";
		die( 2 );
}
print HeaderText();
print $result;
