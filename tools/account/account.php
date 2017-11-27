<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/04/16
 * Time: 07:58
 */

if ( ! defined( TOOLS_DIR ) ) {
	define( TOOLS_DIR, dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/im_tools.php' );
require_once( STORE_DIR . '/tools/orders/orders-common.php' );
// require_once( TOOLS_DIR . '/business/business-post.php' );
require_once( TOOLS_DIR . '/gui/inputs.php' );

#############################
# legacy_users              #
# 1) Classical store client #
# 2) Chef clients           #
#############################

function get_rate( $user_id, $project_id ) {
	global $conn;

	$sql = 'select rate '
	       . ' from im_working '
	       . ' where worker_id = ' . $user_id
	       . ' and project_id = ' . $project_id;

	$result = mysqli_query( $conn, $sql );
	if ( $row = mysqli_fetch_row( $result ) ) { // Specific rate
		$rate = $row[0];
	} else { // Default rate
		$sql = 'select rate '
		       . ' from im_working '
		       . ' where worker_id = ' . $user_id
		       . ' and project_id = 0';

		$result = mysqli_query( $conn, $sql );

		if ( ! $result ) {
			print "no default rate! " . $user_id . " ";
			print mysqli_error( $conn );
			die ( 1 );
		}
		if ( $row = mysqli_fetch_row( $result ) ) {
			$rate = $row[0];
		} else {
			print "no default rate for " . $user_id . " ";
		}
	}

	return round( $rate, 2 );
}

function account_add_transaction( $client_id, $date, $amount, $ref, $type ) {
	$sql = "INSERT INTO im_client_accounts (client_id, date, transaction_amount, transaction_method, transaction_ref) "
	       . "VALUES (" . $client_id . ", \"" . $date . "\", " . $amount . ", \"" . $type . "\", " . $ref . ")";

	my_log( $sql, "db-add-delivery.php" );
	sql_query( $sql );
}

function account_update_transaction( $total, $delivery_id ) {
	$sql = "UPDATE im_client_accounts SET transaction_amount = " . $total .
	       " WHERE transaction_ref = " . $delivery_id;

	my_log( $sql, "db-add-delivery.php" );
	sql_query( $sql );
}

$total = 0;

function client_balance( $client_id ) {
	$sql = 'select sum(transaction_amount) '
	       . ' from im_client_accounts '
	       . ' where client_id = ' . $client_id;

	return round( sql_query_single_scalar( $sql ), 2 );

}

function balance( $date, $client_id ) {
	$sql = 'select sum(transaction_amount) '
	       . ' from im_client_accounts where date <= "' . $date
	       . '" and client_id = ' . $client_id;

	return round( sql_query_single_scalar( $sql ), 2 );

}

function show_trans( $customer_id, $from_last_zero = false, $checkbox = true ) {
	$sql = 'select date, transaction_amount, transaction_method, transaction_ref, id '
	       . ' from im_client_accounts where client_id = ' . $customer_id . ' order by date desc ';

	$result = sql_query( $sql );

	$data = "<table id='transactions_table' border=\"1\" ><tr>";
	if ( $checkbox ) {
		$data .= "<td>בחר</td>";
	}
	$data .= "<td>תאריך</td><td>סכום</td><td>מע\"ם</td><td>יתרה</td><td>פעולה</td>" .
	        "<td>תעודת משלוח</td><td>מס הזמנה</td>";

	$data .= gui_cell( "מקבל" );
	$data .= gui_cell( "קבלה" );

	$data .= "</tr>";
	global $total;

	while ( $row = mysqli_fetch_row( $result ) ) {
		$line   = "<tr class=\"color2\">";
		$date   = $row[0];
		$amount = round( $row[1], 2);
		$total  += $amount;
		$type   = $row[2];
		$doc_id = $row[3];
		$vat    = get_delivery_vat( $doc_id );

		// <input id=\"chk" . $doc_id . "\" class=\"trans_checkbox\" type=\"checkbox\">
		if ( $checkbox )
			$line .= "<td>" . gui_checkbox( "chk" . $doc_id, "trans_checkbox", "", "onchange=\"update_sum()\"" ) . "</td>";
		$line    .= "<td>" . $date . "</td>";
		$line    .= "<td>" . $amount . "</td>";
		$line    .= "<td>" . $vat . "</td>";
		$balance = balance( $date, $customer_id );
		$line    .= "<td>" . $balance . "</td>";
		$line    .= "<td>" . $type . "</td>";

		$delivery_id = $doc_id;

		// Display item name
		if ( $type == "משלוח" ) {
			$line     .= "<td><a href=\"../delivery/get-delivery.php?id=" . $doc_id . "\">" . $doc_id . '</a></td>';
			$order_id = get_order_id( $doc_id );
			$line     .= "<td>" . $order_id . "</td>";
			if ( is_numeric( $order_id ) ) {
				$line .= "<td>" . order_info( $order_id, '_shipping_first_name' ) . "</td>";
			} else {
				$line .= "<td></td>";
			}
			$line .= gui_cell( sql_query_single_scalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $doc_id ) );
		} else {
			$line .= "<td>" . $doc_id . "</td><td></td><td></td>";
			$line .= gui_cell( "" );
		}
		$line .= "</tr>";

		$data .= trim( $line );
		if ( $from_last_zero and abs( $balance ) < 2 ) {
			break;
		}
	}

	$data = str_replace( "\r", "", $data );

	$data .= "</table>";

	$total = round( $total, 2 );

	return $data;
}


function customer_type( $client_id ) {
	// 0 - regular
	// 1 - siton
	// 2 - owner
	$key = get_user_meta( $client_id, '_client_type' );

	if ( is_null( $key[0] ) ) {
		return 0;
	}
	switch ( $key[0] ) {
		case "owner":
			return 2;
		case "siton":
			return 1;
	}
}

function get_payment_method_name( $client_id ) {
	return sql_query_single_scalar( "SELECT name FROM im_payments WHERE `id` = " . get_payment_method( $client_id ) );
}

function get_payment_method( $client_id ) {
	$m = get_user_meta( $client_id, "payment_method", true );
	if ( $m ) {
		return $m;
	}

	return sql_query_single_scalar( "SELECT id FROM im_payments WHERE `default` = 1" );
}

function im_set_default_display_name( $user_id ) {
	// $user = get_userdata( $user_id );
	$user = get_user_by( "id", $user_id );

	$name = $user->user_firstname . " " . $user->user_lastname;;
	// print $user_id . " " . $name;
	if ( strlen( $name ) < 3 ) {
		$name = get_user_meta( $user_id, 'billing_first_name', true ) . " " .
		        get_user_meta( $user_id, 'billing_last_name', true );
		// print "user meta name " . $name;

	}
	$args = array(
		'ID'           => $user_id,
		'display_name' => $name,
		'nickname'     => $name
	);

	// print "<br/>";
	if ( strlen( $name ) > 3 ) {
		wp_update_user( $args );
	}
}