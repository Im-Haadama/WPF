<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/03/18
 * Time: 17:11
 */

require_once( "../im_tools.php" );

function business_add_transaction( $part_id, $date, $amount, $delivery_fee, $ref, $project ) {
	global $conn;
	// print $date . "<br/>";
	$sunday = sunday( $date );

	$sql = "INSERT INTO im_business_info(part_id, date, week, amount, delivery_fee, ref, project_id) "
	       . "VALUES (" . $part_id . ", \"" . $date . "\", " .
	       "\"" . $sunday->format( "Y-m-d" ) .
	       "\", " . ( $amount - $delivery_fee ) . ", " . $delivery_fee . ", '" . $ref . "', '" . $project . "')";

	my_log( $sql, __FILE__ );

	mysqli_query( $conn, $sql ) or my_log( "SQL failed " . $sql, __FILE__ );

	return mysqli_insert_id( $conn );
}
