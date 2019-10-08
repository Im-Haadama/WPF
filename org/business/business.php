<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/03/18
 * Time: 17:11
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/fresh/im_tools.php" );

function business_add_transaction(
	$part_id, $date, $amount, $delivery_fee, $ref, $project, $net_amount = 0,
	$document_type = ImDocumentType::delivery,
	$document_file = null
) {
	// print $date . "<br/>";
	$sunday = sunday( $date );
	if ( ! $part_id ) {
		die ( "no supplier" );
	}

	$fields = "part_id, date, week, amount, delivery_fee, ref, project_id, net_amount, document_type ";
	$values = $part_id . ", \"" . $date . "\", " .
	          "\"" . $sunday->format( "Y-m-d" ) .
	          "\", " . ( $amount - $delivery_fee ) . ", " . $delivery_fee . ", '" . $ref . "', '" . $project . "', " .
	          $net_amount . ", " . $document_type;

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
	$sql = "UPDATE im_business_info SET is_active = 0 WHERE id IN (" . $ids . ")";
	sql_query($sql);
	my_log( $sql );
}

function business_open_ship( $part_id ) {
	$sql = "select id, date, amount, net_amount, ref " .
	       " from im_business_info " .
	       " where part_id = " . $part_id .
	       " and invoice is null " .
	       " and document_type = " . ImDocumentType::ship;

	// print $sql;

	$data = GuiTableContent( "table", $sql );

	// $rows = sql_query_array($sql );

	return $data; // gui_table($rows);
}

function gui_select_bank_account( $id, $value, $args ) {
	return gui_select_table( $id, "im_bank_account", $value,
		GetArg($args, "events", null), null, "name" );
}

function bank_check_dup( $fields, $values ) {
// 	var_dump($values);
	$account_idx = array_search( "account_id", $fields );
	$date_idx    = array_search( "date", $fields );
	$balance_idx = array_search( "balance", $fields );
	$account     = $values[ $account_idx ];
	$date        = $values[ $date_idx ];
	$balance     = $values[ $balance_idx ];
//	print "a=" . $account . " d=" . $date . " b=" . $balance;

	$sql = "SELECT count(*) FROM im_bank WHERE account_id = " . $account .
	       " AND date = " . quote_text( $date ) .
	       " AND round(balance, 2) = " . round( $balance, 2 );
	// print sql_query($sql) . "<br/>";
	$c = sql_query_single_scalar( $sql );
//	print " c=" . $c . "<br/>";

	return $c;
}

function user_is_business_owner() {
	$user = wp_get_current_user();

	return in_array( 'business', (array) $user->roles );
}
