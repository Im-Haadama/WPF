<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/10/16
 * Time: 18:11
 */
if ( ! defined( "TOOLS_DIR" ) ) {
	define( TOOLS_DIR, dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . '/r-staff.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( TOOLS_DIR . '/business/business.php' );

function get_env( $var, $default ) {
	if ( isset( $_GET[ $var ] ) ) {
		return $var;
	} else {
		return $default;
	}
}

if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
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
			$user  = wp_get_current_user();
			$roles = $user->roles;
			if ( in_array( "administrator", $roles ) ) {
				$month = $_GET["month"];
				show_makolet( $month );
			} else {
				my_log( "show_makolet user $user" );
			}
			break;
	}
}


function show_makolet( $month_year ) {
	$year  = substr( $month_year, 0, 4 );
	$month = substr( $month_year, 5, 2 );
	print gui_header( 1, "משלוחים שבוצעו" );
	$sql = "select d.id, client_from_delivery(d.id), d.date, order_id, total, vat, round(reduce_vat(fee),2), " .
	       " round(total-vat-reduce_vat(fee),2), client_displayname(driver), city_from_delivery(d.id), payment_receipt " .
	       " from im_delivery d" .
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
		"דמי משלוח",
		"עסקה נטו",
		"נהג"
	);

	array_push( $table, $header );
	$driver_array = array();

	while ( $row = mysqli_fetch_row( $result ) ) {
		if ( $row[6] == 0 ) {
			$sql1   = "SELECT round(reduce_vat(line_price),2) FROM im_delivery_lines WHERE delivery_id = " . $row[0] . " AND product_name LIKE '%משלוח%'";
			$row[6] = sql_query_single_scalar( $sql1 );
			$row[7] = $row[4] - $row[5] - $row[6];
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