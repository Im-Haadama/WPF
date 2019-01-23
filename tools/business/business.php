<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/03/18
 * Time: 17:11
 */

require_once( TOOLS_DIR . "/im_tools.php" );

function business_add_transaction(
	$part_id, $date, $amount, $delivery_fee, $ref, $project, $net_total = 0,
	$document_type = ImDocumentType::delivery
) {
	global $conn;
	// print $date . "<br/>";
	$sunday = sunday( $date );

	$sql = "INSERT INTO im_business_info(part_id, date, week, amount, delivery_fee, ref, project_id, net_total, document_type) "
	       . "VALUES (" . $part_id . ", \"" . $date . "\", " .
	       "\"" . $sunday->format( "Y-m-d" ) .
	       "\", " . ( $amount - $delivery_fee ) . ", " . $delivery_fee . ", '" . $ref . "', '" . $project . "', " .
	       $net_total . ", " . $document_type . " )";

	my_log( $sql, __FILE__ );

	mysqli_query( $conn, $sql ) or my_log( "SQL failed " . $sql, __FILE__ );

	return mysqli_insert_id( $conn );
}

function business_delete_transaction( $ref ) {
	$sql = "DELETE FROM im_business_info "
	       . " WHERE ref = " . $ref;

	my_log( $sql, __FILE__ );
	sql_query( $sql );
}