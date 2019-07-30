<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/04/16
 * Time: 07:58
 */

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/im_tools.php' );
require_once( STORE_DIR . '/tools/orders/orders-common.php' );
// require_once( TOOLS_DIR . '/business/business-post.php' );
// require_once(TOOLS_DIR . '/multi-site/imMulti-site.php');
require_once( ROOT_DIR . '/niver/gui/inputs.php' );

#############################
# legacy_users              #
# 1) Classical store client #
# 2) Chef clients           #
#############################

function get_daily_rate( $user_id ) {
	return sql_query_single_scalar( "SELECT day_rate FROM im_working WHERE worker_id = " . $user_id );
}

function get_rate( $user_id, $project_id ) {

	$sql = 'select rate '
	       . ' from im_working '
	       . ' where worker_id = ' . $user_id
	       . ' and project_id = ' . $project_id;

	$rate = sql_query_single_scalar( $sql );

	if ( $rate ) {
		return round( $rate, 2 );
	}

	$result = sql_query( $sql );
	$sql    = 'select rate '
	          . ' from im_working '
	          . ' where worker_id = ' . $user_id
	          . ' and project_id = 0';

	$rate = sql_query_single_scalar( $sql );
	if ( $rate )
		return round( $rate, 2);

	print "no default rate for " . $user_id . " ";

	return 0;
}

function account_add_transaction( $client_id, $date, $amount, $ref, $type ) {
	$sql = "INSERT INTO im_client_accounts (client_id, date, transaction_amount, transaction_method, transaction_ref) "
	       . "VALUES (" . $client_id . ", \"" . $date . "\", " . $amount . ", \"" . $type . "\", " . $ref . ")";

	my_log( $sql, "account_add_transaction" );
	sql_query( $sql );
}

function account_update_transaction( $total, $delivery_id, $client_id ) {
	$sql = "UPDATE im_client_accounts SET transaction_amount = " . $total .
	       " WHERE transaction_ref = " . $delivery_id . " and client_id = " . $client_id;

	my_log( $sql, "account_update_transaction" );
	sql_query( $sql );
}

$total = 0;

function client_balance( $client_id ) {
	$sql = 'select sum(transaction_amount) '
	       . ' from im_client_accounts '
	       . ' where client_id = ' . $client_id;

	return round( sql_query_single_scalar( $sql ), 2 );

}

function balance_email( $date, $email ) {
	$client_id = get_customer_by_email( strtolower( $email ) );

	return balance( $date, $client_id );
}

function balance( $date, $client_id ) {
	$sql = 'select sum(transaction_amount) '
	       . ' from im_client_accounts where date <= "' . $date
	       . '" and client_id = ' . $client_id;

	return round( sql_query_single_scalar( $sql ), 2 );

}

// View_type:
class eTransview {
	const
		default = 0,
		from_last_zero = 1,
		not_paid = 2,
		read_last = 3;
}


function show_trans( $customer_id, $view = eTransview::default ) {
	// $from_last_zero = false, $checkbox = true, $top = 10000

	// Show open deliveries
	$from_last_zero = false;
	$checkbox       = true;
	$top            = null;
	$not_paid       = false;
	switch ( $view ) {
		case eTransview::from_last_zero:
			$from_last_zero = true;
			break;
		case eTransview::not_paid:
			$not_paid = true;
			break;

		case eTransview::read_last:
			$top = 100;
			break;
	}
	$sql = 'select date as תאריך,
	 transaction_amount as סכום,
	  client_balance(client_id, date) as יתרה,
	   transaction_method as תנועה,
	    transaction_ref as סימוכין, 
		order_from_delivery(transaction_ref) as הזמנה,
		delivery_receipt(transaction_ref) as קבלה,
		id'
	       . ' from im_client_accounts where client_id = ' . $customer_id;

	if ($not_paid)
		$sql .= " and transaction_method = 'משלוח' ";

	$sql .= ' order by date desc ';
//	 print $sql . "<br/>";

	if ( $top ) {
		$sql .= " limit " . $top;
	}

	$args = array();
	$args["links"] = array();
	$args["links"]["סימוכין"] = "/tools/delivery/get-delivery.php?id=%s";
	$args["links"]["הזמנה"] = "/tools/orders/get-order.php?order_id=%s";
	$args["col_ids"] = array("chk", "dat", "amo", "bal", "des", "del", "ord");
	$id_col = 8;
	$args["show_cols"] = array(); $args["show_cols"][$id_col] = 0;
	$args["id_col"] = $id_col;
	$first = true;

	$data1 = TableData($sql, $args);
	foreach ($data1 as $id => $row)
	{
		$row_id = $data1[$id][7];
		$value = "";
		if ($first)
		{
			$first = false;
			$value = "בחר";
		} else {
			if ($data1[$id][3] == "משלוח" and ! $data1[$id][6]){ // Just unpaid deliveries
				$value =  gui_checkbox("chk" . $row_id, "trans_checkbox", false, "onchange=update_sum()");
			}
		}
		array_unshift($data1[$id], $value);
	}

	print gui_table_args($data1, "trans_table", $args);
	die(1);

	// Todo: Add open invoices

	$sql = "select id, date, amount, 'חשבונית', ref, id from im_business_info where part_id = $customer_id and document_type = " . ImDocumentType::invoice;
	$data2 = TableData($sql, $args);

//	$args["add_checkbox"] = true;
//	$args["checkbox_class"] = "trans_checkbox";
	$args["hide_first"] = true; // for row id
	return gui_table_args(array_merge($data1, $data2), "transactions", $args);


	$result = sql_query( $sql );

	$data = "<table id='transactions_table' border=\"1\" ><tr>";
	if ( $checkbox ) {
		$data .= "<td>בחר</td>";
	}
	$data .= "<td>תאריך</td><td>סכום</td><td>מע\"ם</td><td>יתרה</td><td>פעולה</td>" .
	         "<td>אסתמכא</td><td>מס הזמנה</td>";

	$data .= gui_cell( "מקבל" );
	$data .= gui_cell( "קבלה/חשבונית" );

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

		$is_delivery = ( $type == "משלוח" );
		$receipt     = sql_query_single_scalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $doc_id );

		if ( $not_paid ) { // We want only the not paid deliveries.
			if ( ! $is_delivery ) {
				continue;
			}
			if ( $receipt ) {
				continue;
			}
		}

		// <input id=\"chk" . $doc_id . "\" class=\"trans_checkbox\" type=\"checkbox\">
		if ( $checkbox ) {
			do {
				if ( $is_delivery ) {
					$d = new Delivery( $doc_id );
					if ( $d->isDraft() ) {
						$line .= gui_cell( "טיוטא" );
						break;
					}
				}
				if ( $receipt or ! $is_delivery ) {
					$line .= gui_cell( "" );
					break;
				} else {
					$line .= "<td>" . gui_checkbox( "chk" . $doc_id, "trans_checkbox", "", "onchange=\"update_sum()\"" ) . "</td>";
					break;
				}
			} while ( false );
		}

		$line    .= "<td>" . $date . "</td>";
		$line    .= "<td>" . gui_label( "amo_" . $doc_id, $amount ) . "</td>";
		$line    .= "<td>" . $vat . "</td>";
		$balance = balance( $date, $customer_id );
		$line    .= "<td>" . $balance . "</td>";
		$line    .= "<td>" . $type . "</td>";

		// Display item name
		if ( $is_delivery ) {
			$line     .= gui_cell( gui_hyperlink( $doc_id, "/tools/delivery/get-delivery.php?id=" . $doc_id ) );
			$order_id = get_order_id( $doc_id );
			try {
				$o = new Order( $order_id );
			} catch ( exception $e ) {
				print "cat get order " . $order_id . "<br/>";
			}
			$line     .= "<td>" . $order_id . "</td>";
			if ( is_numeric( $order_id ) ) {
				$line .= "<td>" . $o->getOrderInfo( '_shipping_first_name' ) . "</td>";
			} else {
				$line .= "<td></td>";
			}
			$line .= gui_cell( $receipt );
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

// [displa-posts][su_posts posts_per_page="3"][su_posts posts_per_page="3" tax_term="21" order="desc"]
function get_payment_method_name( $client_id ) {
	if ( $client_id > 0 ) {
		$p = get_payment_method( $client_id );
		if ( $p > 0 ) {
			return sql_query_single_scalar( "SELECT name FROM im_payments WHERE `id` = " . $p );
		}
		print "לא נבחר אמצעי ברירת מחדל<br/>";
	} else {
		return "לא נבחר לקוח";
	}
}

function get_payment_method( $client_id ) {
	$m = get_user_meta( $client_id, "payment_method", true );
	if ( $m ) {
		return $m;
	}

	$p = sql_query_single_scalar( "SELECT id FROM im_payments WHERE `default` = 1" );
	if ( $p ) {
		return $p;
	} else {
		return "לא נבחר אמצעי ברירת מחדל";
	}
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
