<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/10/16
 * Time: 18:11
 */

if ( ! defined( TOOLS_DIR ) ) {
	define( TOOLS_DIR, dirname( __FILE__ ) );
}

require_once( TOOLS_DIR . '/im_tools.php' );

$operation = $_GET["operation"];

function get_env( $var, $default ) {
	if ( isset( $_GET[ $var ] ) ) {
		return $var;
	} else {
		return $default;
	}
}

switch ( $operation ) {
	case "add_item":
		print "Adding item<br/>";
		$part_id      = $_GET["part_id"];
		$date         = $_GET["date"];
		$amount       = $_GET["amount"];
		$delivery_fee = get_env( "delivery_fee", 0 );
		$ref          = $_GET["ref"];
		$project      = get_env( "project", 1 );

		business_add_transaction( $part_id, $date, $amount, $delivery_fee, $ref, $project );
		print $part_id . ", " . $date . ", " . $amount . ", " . $delivery_fee . ", " . $ref . ", " . $project . "<br/>";
		print "done<br/>";
		break;
	case "delete_items":
		$ids = $_GET["ids"];
		my_log( "Deleting ids: " . $ids );
		business_logical_delete( $ids );
		break;
}

function business_add_transaction( $part_id, $date, $amount, $delivery_fee, $ref, $project ) {
	global $conn;
	// print $date . "<br/>";
	$sunday = sunday( $date );

	$sql = "INSERT INTO im_business_info(part_id, date, week, amount, delivery_fee, ref, project_id) "
	       . "VALUES (" . $part_id . ", \"" . $date . "\", " .
	       "\"" . $sunday->format( "Y-m-d" ) .
	       "\", " . ( $amount - $delivery_fee ) . ", " . $delivery_fee . ", '" . $ref . "', " . $project . ")";

	my_log( $sql, __FILE__ );

	mysqli_query( $conn, $sql ) or my_log( "SQL failed " . $sql, __FILE__ );

	return mysqli_insert_id( $conn );
}

function business_update_transaction( $delivery_id, $total, $fee ) {
	$sql = "UPDATE im_business_info SET amount = " . $total . ", " .
	       " delivery_fee = " . $fee .
	       " WHERE ref = " . $delivery_id;

	my_log( $sql, __FILE__ );
	sql_query( $sql );
}

function business_logical_delete( $ids ) {
	global $conn;
	$sql = "UPDATE im_business_info SET is_active = 0 WHERE id IN (" . $ids . ")";
	$conn->query( $sql );
	my_log( $sql );
}

function business_delete_transaction( $ref ) {
	$sql = "DELETE FROM im_business_info "
	       . " WHERE ref = " . $ref;

	my_log( $sql, __FILE__ );
	sql_query( $sql );
}