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
	$document_type = ImDocumentType::delivery,
	$document_file = null
) {
	// print $date . "<br/>";
	$sunday = sunday( $date );
	if ( ! $part_id ) {
		die ( "no supplier" );
	}

	$fields = "part_id, date, week, amount, delivery_fee, ref, project_id, net_total, document_type ";
	$values = $part_id . ", \"" . $date . "\", " .
	          "\"" . $sunday->format( "Y-m-d" ) .
	          "\", " . ( $amount - $delivery_fee ) . ", " . $delivery_fee . ", '" . $ref . "', '" . $project . "', " .
	          $net_total . ", " . $document_type;

	if ( $document_file ) {
		$fields .= ", invoice_file";
		$values .= ", " . quote_text( $document_file );
	}
	$sql = "INSERT INTO im_business_info (" . $fields . ") "
	       . "VALUES (" . $values . " )";

	my_log( $sql, __FILE__ );

	sql_query( $sql);

	return sql_insert_id();
}

function business_delete_transaction( $ref ) {
	$sql = "DELETE FROM im_business_info "
	       . " WHERE ref = " . $ref;

	my_log( $sql, __FILE__ );
	sql_query( $sql );
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

function business_open_ship( $part_id ) {
	$sql = "select id, date, amount, net_total, ref " .
	       " from im_business_info " .
	       " where part_id = " . $part_id .
	       " and invoice is null " .
	       " and document_type = " . ImDocumentType::ship;

	// print $sql;

	$data = table_content( $sql );

	// $rows = sql_query_array($sql );

	return $data; // gui_table($rows);
}