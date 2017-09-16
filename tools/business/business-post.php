<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/10/16
 * Time: 18:11
 */

if ( ! defined( TOOLS_DIR ) ) {
	define( TOOLS_DIR, dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/im_tools.php' );
require_once( TOOLS_DIR . '/gui/inputs.php' );


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

	case "show_makolet":
		$month = $_GET["month"];
		show_makolet( $month );
		break;
}

function sum_numbers( &$sum, $number ) {
	$sum += $number;
	// print $sum . " " . $number . " " . "<br/>";
}

function show_makolet( $month_year ) {
	$year  = substr( $month_year, 0, 4 );
	$month = substr( $month_year, 5, 2 );
	print gui_header( 1, "משלוחים שבוצעו" );
	$sql = "select d.id, client_from_delivery(d.id), d.date, order_id, total, vat, fee, round(total-vat-fee,2), client_displayname(driver), city_from_delivery(d.id) from im_delivery d" .
	       " join im_business_info i " .
	       " where month(d.date)=" . $month . " and year(d.date)=" . $year . " and i.ref = d.id and i.is_active = 1 ";


	$result = sql_query( $sql );
	$table  = array();

	$header = array(
		"מספר משלוח",
		"לקוח",
		"תאריך הזנה",
		"מספר הזמנה",
		"סה\"כ שולם",
		"מע\"מ עסקה",
		"דמי משלוח כולל מע\"מ",
		"עסקה נטו",
		"נהג"
	);

	array_push( $table, $header );
	$driver_array = array();

	while ( $row = mysqli_fetch_row( $result ) ) {
		if ( $row[6] == 0 ) {
			$sql1   = "select line_price from im_delivery_lines where delivery_id = " . $row[0] . " and product_name like '%משלוח%'";
			$row[6] = sql_query_single_scalar( $sql1 );
		}
//		$id =  $row[0];

		// $row = array($id, $row[1]);
		array_push( $table, $row );
		$driver_array[ $row[8] ] += $row[6];
	}
	$sums = array(
		"סה\"כ",
		'',
		'',
		'',
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' ),
		0
	);
	print gui_table( $table, "", true, true, $sums );

	foreach ( $driver_array as $key => $value ) {
		print $key . " " . $value . "<br/>";
	}

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