<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/04/16
 * Time: 07:58
 */

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) );
}

require_once( FRESH_INCLUDES . '/orders/orders-common.php' );
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );

#############################
# legacy_users              #
# 1) Classical store client #
# 2) Chef clients           #
#############################

function get_daily_rate( $user_id ) {
//	return get_user_meta($user_id, 'day_rate');
	return sql_query_single_scalar( "SELECT day_rate FROM im_working WHERE user_id = " . $user_id );
}

function get_rate( $user_id, $project_id ) {
	// Check project specific rate
	$sql = 'select rate '
	       . ' from im_working '
	       . ' where user_id = ' . $user_id
	       . ' and project_id = ' . $project_id;

	$rate = sql_query_single_scalar( $sql );

	if ( $rate ) {
		return round( $rate, 2 );
	}

	// Check global rate.
	$sql    = 'select rate '
	          . ' from im_working '
	          . ' where user_id = ' . $user_id
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


function show_trans( $customer_id, $view = eTransview::default, $args )
{
	$page = GetArg($args, "page", null);
	$query = GetArg($args, "query", null);
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
	$sql = 'select id, date,
	 round(transaction_amount, 2) as transaction_amount,
	  client_balance(client_id, date) as balance,
	   transaction_method,
	    transaction_ref, 
		order_from_delivery(transaction_ref) as order_id,
		delivery_receipt(transaction_ref) as receipt,
		id'
	       . ' from im_client_accounts where client_id = ' . $customer_id;

	if ($not_paid)
		$sql .= " and transaction_method = 'משלוח' ";

	if ($query) $sql .= " and " . $query;

	$sql .= ' order by date desc ';

	if ( $top ) {
		$sql .= " limit " . $top;
	}

	$args = array();
	$args["links"] = array();
	$args["links"]["transaction_ref"] = "/fresh/delivery/get-delivery.php?id=%s";
	$args["links"]["order"] = "/fresh/orders/get-order.php?order_id=%s";
	$args["col_ids"] = array("chk", "id", "dat", "amo", "bal", "des", "del", "ord");
//	$args["show_cols"] = array(); $args["show_cols"]['id'] = 0;
	$args["add_checkbox"] = false; // Checkbox will be added only to unpaid rows
	$args["page"] = $page;
	$first = true;

	$args["page"] = -1;// all rows
	$data1 = TableData($sql, $args);

	if (! $data1) {
		print im_translate("No orders");
		return;
	}

//	var_dump($data1);
	foreach ($data1 as $id => $row)
	{
		$row_id = $row['id'];
//		print "row_id=" . $row_id . " " . $id . "<br/>";
		$value = "";
		if ($first) { $first = false; $value = "בחר";}
		else {
//			var_dump($data1[$id]); print"<br/>";
			 if ($data1[$id]['transaction_method'] == "משלוח" and ! $data1[$id]['receipt']){ // Just unpaid deliveries
				$value =  gui_checkbox("chk_" . $row_id, "trans_checkbox", false, "onchange=update_sum()");
			 }
		}
		array_unshift($data1[$id], $value);
	}

	print GemArray($data1, $args, "trans_table");
	// print gui_table_args($data1, "trans_table", $args);
	return;

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
			$line     .= gui_cell( gui_hyperlink( $doc_id, "/fresh/delivery/get-delivery.php?id=" . $doc_id ) );
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

function payment_get_accountants($payment_id)
{
	return sql_query_single_scalar("select accountants from im_payments where id = " . $payment_id);
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

function create_receipt( $cash, $bank, $check, $credit, $change, $user_id, $date, $row_ids )
{
	if (! $date) $date = date('Y-m-d');
	if ( ! ( $user_id > 0 ) ) {
		throw  new Exception( "Bad customer id " . __CLASS__ );
	}

	$del_ids = array();
	$no_ids = true;
	foreach ( $row_ids as $id ) {
		if ( $id > 0 ) {
			$no_ids = false;
			$del_id = sql_query_single_scalar("select transaction_ref from im_client_accounts where ID = " . $id);
			if ($del_id > 0)
				array_push($del_ids, $del_id);
			else {
				print "Didn't find delivery id for account row " . $id;
				return false;
			}
		} else {
			die ("bad id " . $id);
		}
	}

	if ( $no_ids ) {
		print "לא נבחרו תעודות משלוח";

		return false;
	}
	$c = $cash - $change;
//        if (abs($c) < 0) $c =0;
	//      if (round($c,0) < 1 or round($c,0) < 1)
	// Check if paid (some bug cause double invoice).
	$sql = "SELECT count(payment_receipt) FROM im_delivery WHERE id IN (" . comma_implode( $del_ids ) . " )";
	if ( sql_query_single_scalar( $sql ) > 0 ) {
		print " כבר שולם" . comma_implode( $del_ids ) . " <br/>";

		return false;
	}

	$doc_id   = invoice_create_document( "r", $del_ids, $user_id, $date, $c, $bank, $credit, $check );

	$pay_type = pay_type( $cash, $bank, $credit, $check );
	if ( is_numeric( $doc_id ) && $doc_id > 0 ) {
		$pay_description = $pay_type . " " . comma_implode( $del_ids );

		$sql = "UPDATE im_delivery SET payment_receipt = " . $doc_id . " WHERE id IN (" . comma_implode( $del_ids ) . " ) ";
		sql_query( $sql );

		account_add_transaction( $user_id, $date, $change - ( $cash + $bank + $credit + $check ), $doc_id, $pay_description );
		if ( abs( $change ) > 0 ) {
			account_add_transaction( $user_id, $date, - $change, $doc_id, $change > 0 ? "עודף" : "יתרה" );
		}
		print $doc_id;
		return true;
	} else {
		print "doc_id: " . $doc_id . "<br/>";
		return false;
	}
}

function invoice_create_document( $type, $ids, $customer_id, $date, $cash = 0, $bank = 0, $credit = 0, $check = 0, $subject = null ) {
	global $debug;
	global $invoice_user;
	global $invoice_password;

	if ( ! ( $customer_id > 0 ) )
		throw new Exception( "Bad customer id" . __CLASS__);

	$invoice = new Invoice4u( $invoice_user, $invoice_password );

	$invoice->Login();

//	print "customer id : " . $customer_id . "<br/>";

	$invoice_client_id = $invoice->GetInvoiceUserId( $customer_id );

//	print "invoice client id " . $invoice_client_id . "<br/>";

	$client = $invoice->GetCustomerById( $invoice_client_id );

	if ( ! ( $client->ID ) > 0 ) {
		print "Client not found " . $customer_id . "<br>";

		// var_dump( $client );

		return 0;
	}
	$email = $client->Email;
	// print "user mail: " . $email . "<br/>";
	$doc = new Document();

	$iEmail                = new Email();
	$iEmail->Mail          = $email;
	$doc->AssociatedEmails = Array( $iEmail );
	//var_dump($client->ID);

	$doc->ClientID = $client->ID;
	switch ( $type ) {
		case "r":
			$doc->DocumentType = DocumentType::InvoiceReceipt;
			break;
		case "i":
			$doc->DocumentType = DocumentType::Invoice;
			break;
	}

	// Set the subject
	if ( ! $subject ) {
		$subject = "סלים" . " " . comma_implode( $ids );
	}
	$doc->Subject = $subject;

	// Add the deliveries
	$doc->Items = Array();

	$total_lines = 0;
	foreach ( $ids as $del_id ) {
		$sql = 'select product_name, quantity, vat, price, line_price '
		       . ' from im_delivery_lines where delivery_id = ' . $del_id;

		$result = sql_query( $sql );

		// drill to lines
		while ( $row = mysqli_fetch_row( $result ) ) {
			if ( $row[4] != 0 ) {
				$item           = new Item();
				$item->Name     = $row[0];
				$item->Price    = round( $row[3], 2 );
				$item->Quantity = round( $row[1], 2 );
				if ( $row[2] > 0 ) {
					$item->TaxPercentage   = 17;
					$item->TotalWithoutTax = round( $row[4] / 1.17, 2 );
				} else {
					$item->TaxPercentage   = 0;
					$item->TotalWithoutTax = round( $row[4], 2);
				}
				$item->Total = round( $item->Price * $item->Quantity, 2);
				//            if ($debug) {
				//     print $item->Name . ":" . $item->Quantity . "*" . $item->Price . " " . $item->Total . "<br/>";
				//            }
				array_push( $doc->Items, $item );
				$total_lines += $item->Total;
			}
		}
	}

	if (! ($total_lines > 0))
	{
		die("no total for invoice<br/>");
	}

	if ( $type == "r" ) {
		if ( is_numeric( $cash ) and $cash <> 0 ) {
			$pay         = new PaymentCash();
			$pay->Amount = $cash;
			array_push( $doc->Payments, $pay );
		}
		if ( $bank > 0 ) {
			$pay         = new PaymentBank();
			$pay->Amount = $bank;
			$pay->Date   = $date;
			array_push( $doc->Payments, $pay );
		}
		if ( $credit > 0 ) {
			$pay         = new PaymentCredit();
			$pay->Amount = $credit;
			array_push( $doc->Payments, $pay );
		}
		if ( $check > 0 ) {
			$pay         = new PaymentCheck();
			$pay->Amount = $check;
			array_push( $doc->Payments, $pay );
		}

//        if ($total_lines <> ($cash + $bank + $credit + $check)){
//            print "total lines " . $total_lines . "<br/>";
//            print "cash " . $cash . "<br/>";
//            print "bank " . $bank . "<br/>";
//            print "credit " . $credit . "<br/>";
//        }
		//$pay->Amount = $doc->Total;
		// print "Amount: " . $pay->Amount . "<br/>";
		// $doc->RoundAmount = 69;
		// $doc->Total = 69;
		// $doc->TaxPercentage = 17;
		$doc->Total = $credit + $bank + $cash + $check;
		// $doc->RoundAmount = round($total_lines - $doc->Total, 2);
		$doc->ToRoundAmount = false;
		// print "round = " . $doc->RoundAmount . "<br/>";
		// print "total = " . $doc->Total . "<br/>";
	}

	// print "create<br/>";
	$doc_id =  $invoice->CreateDocument( $doc );

	// var_dump($doc);
	return $doc_id;
}

function pay_type( $cash, $bank, $credit, $check ) {
	$pay_type = "";
	if ( $cash > 0 ) {
		$pay_type .= "מזומן ";
	}
	if ( $bank > 0 ) {
		$pay_type .= "העברה ";
	}
	if ( $credit > 0 ) {
		$pay_type .= "אשראי ";
	}
	if ( $check > 0 ) {
		$pay_type .= "המחאה ";
	}

	return $pay_type;
}
