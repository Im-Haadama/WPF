<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/10/16
 * Time: 18:11
 */

// error_reporting( E_ALL );
// ini_set( 'display_errors', 'on' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . '/r-staff.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( TOOLS_DIR . '/business/business.php' );
require_once( TOOLS_DIR . '/invoice4u/invoice.php' );

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
		case "create_makolet":
			$user  = wp_get_current_user();
			$roles = $user->roles;
			if ( in_array( "administrator", $roles ) ) {
				$month = $_GET["month"];
				create_makolet( $month );
			} else {
				my_log( "show_makolet user $user" );
			}
			break;
		case "show_control":
			$user  = wp_get_current_user();
			$roles = $user->roles;
			if ( ! isset( $month ) ) {
				die( "no month send" );
			}
			if ( in_array( "administrator", $roles ) ) {
				$month = $_GET["month"];
				show_control( $month );
			} else {
				my_log( "show_control user $user" );
			}
			break;
	}
}

function create_makolet( $month_year ) {
	print "מפיק חשבונית לחודש " . $month_year . "<br/>";

	$year  = substr( $month_year, 0, 4 );
	$month = substr( $month_year, 5, 2 );
	$sums = array(
		"סה\"כ",
		'',
		'',
		'',
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' )
	);

	$table = calc_makolet( $year, $month );

	// Just for summing
	gui_table( $table, "", true, true, $sums );

	$net_sell = $sums[7][0];
	$delivery = $sums[6][0];
	print "עמלה: " . $net_sell * 0.2 . "<br/>";
	print "משלוחים: " . $delivery . "<br/>";

	$doc_id = create_subcontract_invoice( $month, $year, $net_sell, $delivery );

	if ( $doc_id ) {
		print "חשבונית מספר " . $doc_id . " הופקה בהצלחה";
	}

	$sql = "INSERT INTO im_subcontract_invoice (month, year, invoice) VALUES (" .
	       $month . ", " .
	       $year . ", " .
	       $doc_id . ")";

	sql_query( $sql );
}

function create_subcontract_invoice( $month, $year, $net_sell, $net_delivery ) {
	// Connect to invoice of subcontractor and create invoice for this site
	global $invoice_user_sub;
	global $invoice_password_sub;
	global $client_customer_sub;
	global $percent_sub;

	$type = "i";

//	print "user: " . $invoice_user_sub . "<br/>";
//	print "password: " . $invoice_password_sub . "<br/>";

	$invoice = new Invoice4u( $invoice_user_sub, $invoice_password_sub );

	if ( is_null( $invoice->token ) ) {
		die ( "can't login" );
	}

	// print "customer id : " . $customer_id . "<br/>";

//	$invoice_client_id = $invoice->GetInvoiceUserId( $client_customer_sub );

	// print "invoice client id " . $invoice_client_id . "<br/>";

	$client = $invoice->GetCustomerById( $client_customer_sub );

//	var_dump($client);

	if ( ! ( $client->ID ) > 0 ) {
		print "Client not found " . $client_customer_sub . "<br>";

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
	$subject = " עמלה ודמי משלוח לחודש " . $month . "/" . $year;

	$doc->Subject = $subject;

	// Add the deliveries
	$doc->Items = Array();

	$total_lines = 0;

	// Add the selling commission
	$item                  = new Item();
	$item->Name            = "עמלה";
	$item->Price           = round( $net_sell, 2 ) * 1.17;
	$item->Quantity        = $percent_sub;
	$item->TaxPercentage   = 17;
	$item->TotalWithoutTax = round( $net_delivery * $percent_sub, 2 );
	$item->Total           = round( $item->Price * $item->Quantity, 2 );
	array_push( $doc->Items, $item );

	// Add the delivery fees
	$item                  = new Item();
	$item->Name            = "דמי משלוח שניגבו";
	$item->Price           = round( $net_delivery, 2 ) * 1.17;
	$item->Quantity        = 1;
	$item->TaxPercentage   = 17;
	$item->TotalWithoutTax = round( $net_delivery * $percent_sub, 2 );
	$item->Total           = round( $item->Price * $item->Quantity, 2 );
	array_push( $doc->Items, $item );
	$total_lines += $item->Total;

//	if ( $type == "r" ) {
//		if ( is_numeric( $cash ) and $cash <> 0 ) {
//			$pay         = new PaymentCash();
//			$pay->Amount = $cash;
//			array_push( $doc->Payments, $pay );
//		}
//		if ( $bank > 0 ) {
//			$pay         = new PaymentBank();
//			$pay->Amount = $bank;
//			$pay->Date   = $date;
//			array_push( $doc->Payments, $pay );
//		}
//		if ( $credit > 0 ) {
//			$pay         = new PaymentCredit();
//			$pay->Amount = $credit;
//			array_push( $doc->Payments, $pay );
//		}
//		if ( $check > 0 ) {
//			$pay         = new PaymentCheck();
//			$pay->Amount = $check;
//			array_push( $doc->Payments, $pay );
//		}
//
////        if ($total_lines <> ($cash + $bank + $credit + $check)){
////            print "total lines " . $total_lines . "<br/>";
////            print "cash " . $cash . "<br/>";
////            print "bank " . $bank . "<br/>";
////            print "credit " . $credit . "<br/>";
////        }
//		//$pay->Amount = $doc->Total;
//		// print "Amount: " . $pay->Amount . "<br/>";
//		// $doc->RoundAmount = 69;
//		// $doc->Total = 69;
//		// $doc->TaxPercentage = 17;
//		$doc->Total = $credit + $bank + $cash + $check;
//		// $doc->RoundAmount = round($total_lines - $doc->Total, 2);
//		$doc->ToRoundAmount = false;
//		// print "round = " . $doc->RoundAmount . "<br/>";
//		// print "total = " . $doc->Total . "<br/>";
//	}

	// print "create<br/>";
	$doc_id = $invoice->CreateDocument( $doc );

	// var_dump($doc);
	return $doc_id;

}
function show_makolet( $month_year ) {
	$year  = substr( $month_year, 0, 4 );
	$month = substr( $month_year, 5, 2 );
	print gui_header( 1, "משלוחים שבוצעו" );

	$sums  = array(
		"סה\"כ",
		'',
		'',
		'',
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' ),
		array( 0, 'sum_numbers' )
	);
	$table = calc_makolet( $year, $month );
	print gui_table( $table, "", true, true, $sums );

//	foreach ( $driver_array as $key => $value ) {
//		print $key . " " . $value . "<br/>";
//	}

	print gui_header( 1, "ריכוז חיוב" );
	print "עמלה: " . $sums[7][0] * 0.2 . "<br/>";
	print "משלוחים: " . $sums[6][0] . "<br/>";

	$sql = "select invoice from im_subcontract_invoice where month = " . $month .
	       " and year = " . $year;

	$doc_id = sql_query_single_scalar( $sql );


	if ( $doc_id ) {
		print "הופקה חשבונית מספר  " . $doc_id . "<br/>";
	} else {
		print gui_button( "btn_create_invoice", "create_invoice()", "הפק חשבונית" );
	}
}

function calc_makolet( $year, $month ) {
	$sql = "select d.id, client_from_delivery(d.id), d.date, order_id, total, vat, round(reduce_vat(fee),2), " .
	       " round(total-vat-reduce_vat(fee),2), payment_receipt " .
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
		"חשבונית קבלה"
//		"נהג"
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

	return $table;
}

function show_control( $month_year ) {
	print $month_year;
	$year = substr( $month_year, 0, 4 );
	if ( ! $year ) {
		die ( "bad year" );
	}
	$month = substr( $month_year, 5, 2 );
	if ( ! $month ) {
		die ( "bad month" );
	}
	print gui_header( 1, "תשלומים באשראי" );

	$sql = "SELECT id, client_id, date, transaction_amount, transaction_ref FROM im_client_accounts WHERE month(date) = " . $month .
	       " AND year(date) = " . $year .
	       " AND transaction_method LIKE '%אשראי%'";

	$result = sql_query( $sql );
	$table  = array();

	$header = array(
		"לקוח",
		"תאריך הזנה",
		"סה\"כ שולם",
		"קבלה"
	);

	array_push( $table, $header );

	while ( $row = mysqli_fetch_row( $result ) ) {
//		$id =  $row[0];

		$row = array( get_customer_name( $row[1] ), $row[2], - $row[3], $row[4] );
		array_push( $table, $row );
	}
	$sums = array(
		"סה\"כ",
		'',
		array( 0, 'sum_numbers' )
	);
	print gui_table( $table, "", true, true, $sums );

}

