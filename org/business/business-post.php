<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/10/16
 * Time: 18:11
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );
if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname(dirname( dirname( __FILE__)  ) ));
}

require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/org/business/business.php' );
require_once( ROOT_DIR . '/fresh/invoice4u/invoice.php' );
require_once( ROOT_DIR . '/niver/data/html2array.php' );
require_once( ROOT_DIR . '/fresh/multi-site/imMulti-site.php' );
require_once( ROOT_DIR . '/org/business/BankTransaction.php' );
require_once( ROOT_DIR . '/fresh/suppliers/gui.php' );
require_once( ROOT_DIR . '/niver/gui/input_data.php' );
require_once( ROOT_DIR . '/niver/gui/input_data.php' );
require_once( ROOT_DIR . '/fresh/account/account.php' );


require_once( ROOT_DIR . "/init.php" );
$multi_site = ImMultiSite::getInstance();

function get_env( $var, $default ) {
	if ( isset( $_GET[ $var ] ) ) {
		return $var;
	} else {
		return $default;
	}
}

$operation = get_param("operation", false, null);
if ( $operation) {
	// print "op=" . $operation . "<br/>";
	switch ( $operation ) {
		case "exists_invoice":
			$bank_id = get_param("bank_id", true);
			$invoice = get_param("invoice", true);
			$b = BankTransaction::createFromDB( $bank_id );
			$b->Update( 0, $invoice, 0 );
			print "done";
			break;
		case "get_amount":
			$sql = "SELECT amount FROM im_business_info \n" .
			       " WHERE id = " . get_param( "id", true );
			print sql_query_single_scalar( $sql );
			break;

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
				print "no permission";
				my_log( "show_makolet user " . $user->Id );
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

		case "create_pay_bank":
			require_once( ROOT_DIR . '/org/business/BankTransaction.php' );
			require_once( ROOT_DIR . '/fresh/account/gui.php' );
			print header_text( false, true, true,
				array(
					"business.js",
					"/niver/gui/client_tools.js",
					"/fresh/account/account.js"
				) );
			$id = get_param( "id" );
			print gui_header( 1, "רישום העברה שבוצעה " );

			$b = BankTransaction::createFromDB( $id );
			print gui_header( 2, "פרטי העברה" );
			$free_amount = $b->getOutAmount( true );
			print gui_table_args( array(
					array( "תאריך", gui_div( "pay_date", $b->getDate() ) ),
					array( "סכום", gui_div( "bank", $b->getOutAmount() ) ),
					array( "סכום לתיאום", gui_div( "bank", $free_amount ) ),
					array( "מזהה", gui_div( "bank_id", $id ) )
				)
			);

			$lines = $b->getAttached();
			if ( $lines ) {
				print gui_header( 2, "שורות מתואמות" );

				print gui_table_args( $lines );
			}

			if ( $free_amount > 0 ) {
//				print "a=" . $amount . "<br/>";
				print gui_header( 2, "Select Supplier" );
				print gui_select_open_supplier();
			}
			print '<div id="logging"></div>';
			print '<div id="transactions"></div>';
			print gui_table( array(
				array(
					"קשר",
					gui_button( "btn_receipt", "link_invoice_bank()", "קשר תשלום לחשבוני/ות" )
				),
				array( "סה\"כ", " <div id=\"total\"></div>" )
			), "payment_table", true, true, $sums, "", "payment_table" );

			break;

		case "show_pay_to_link":
			$data = GuiTableContent("invoices", "SELECT id, date, out_amount, description,
					bank_amount_to_link(id) FROM im_bank " .
			                            " WHERE out_amount > 0  " .
			                            " AND bank_amount_to_link(id) > 0 " .
			                            " AND description NOT IN ('פרעון הלוואה', 'מסלול מורחב', 'לאומי ויזה י' )" .
			                            " ORDER BY 2 DESC", $args );

			foreach ( $data as $key => $row ) {
				$id = $data[ $key ][0];
				$b  = BankTransaction::createFromDB( $id );
				if ( $b->getOutAmount( true ) ) {
					array_push( $data[ $key ], gui_hyperlink( "קשר", "business-post.php?operation=create_pay_bank&id=" . $id ) );
				}
			}
			print gui_table_args( $data );
			break;
		case "link_invoice_bank":
			$bank_id      = get_param( "bank_id", true );
			$supplier_id  = get_param( "supplier_id", true );
			$site_id      = get_param( "site_id", true );
			$ids          = get_param_array( "ids" );
			$bank         = get_param( "bank" );

			// 1) mark the bank transaction to invoice.
			foreach ( $ids as $id ) {
				$command = "org/business/business-post.php?operation=get_amount&id=" . $id;
				$amount = doubleval(strip_tags($multi_site->Run($command , $site_id)));
				$line_amount = min ($amount, $bank);

				$sql    = "INSERT INTO im_bank_lines (line_id, amount, site_id, part_id, invoice)\n" .
				          "VALUES (" . $bank_id . ", " . $line_amount . ", " . $site_id . ", " . $supplier_id . ", " .
				          $id . ")";

				sql_query($sql);
			}
			$b    = BankTransaction::createFromDB( $bank_id );
			$date = $b->getDate();

			// 2) mark the invoices to transaction.
			$command = "org/business/business-post.php?operation=add_payment&ids=" . implode( $ids, "," ) . "&supplier_id=" . $supplier_id .
			           "&bank_id=" . $bank_id . "&date=" . $date .
			           "&amount=" . $bank;
//			print $command;
			print $multi_site->Run( $command, $site_id );

			print "מעדכן שורות<br/>";
			$sql = "update im_bank " .
			       " set receipt = \"" . comma_implode($ids) . "\", " .
			       " site_id = " . $site_id .
			       " where id = " . $bank_id;

		     sql_query($sql);

			break;

		case "add_payment":
			$supplier_id = get_param( "supplier_id", true );
			$bank_id     = get_param( "bank_id", true );
			$ids         = get_param_array( "ids" );
			$date        = get_param( "date", true );
			$amount      = get_param( "amount", true );
			$sql         = "INSERT INTO im_business_info (part_id, date, amount, ref, document_type)\n" .
			               "VALUES(" . $supplier_id . ", '" . $date . "' ," . $amount . ", " . $bank_id . ", " . ImDocumentType::bank . ")";
			sql_query( $sql );
			print "התווסף תשלום בסך " . $amount . " לספק " . get_supplier_name( $supplier_id ) . "<br/>";

			$sql = "update im_business_info\n" .
			       "set pay_date = '" . $date . "'\n" .
			       "where id in (" . comma_implode( $ids ) . ")";

			sql_query( $sql );
			print "מסמכים מספר  " . comma_implode( $ids ) . " סומנו כמשולמים<br/>";
			break;

		case "create_invoice_bank":
			require_once( ROOT_DIR . '/org/business/BankTransaction.php' );
			require_once( ROOT_DIR . '/fresh/account/gui.php' );
			print header_text( false, true, true,
				array(
					"business.js",
					"/niver/gui/client_tools.js",
					"/fresh/account/account.js"
				) );
			$id = get_param( "id" );
			$b = BankTransaction::createFromDB( $id );
			print gui_header( 1, "הפקת חשבונית קבלה להפקדה מבנק " );

			print gui_header( 2, "פרטי העברה" );
			print gui_table_args( array(
					array( "תאריך", gui_div( "pay_date", $b->getDate() ) ),
					array( "סכום", gui_div( "bank", $b->getInAmount() ) ),
					array( "מזהה", gui_div( "bank_id", $id ) )
				)
			);

			print gui_header(2, "חשבונית שהופקה");
			print GuiInput("invoice_id");
			print gui_button("btn_invoice_exists", "invoice_exists()", "Exists invoice");

			print gui_header( 2, "בחר לקוח" );
			print gui_select_client_open_account();
			print '<div id="logging"></div>';
			print '<div id="transactions"></div>';
			print gui_table( array(
				array(
					"תשלום",
					gui_button( "btn_receipt", "create_receipt_from_bank()", "הפק חשבונית מס קבלה" )
				),
				array( "עודף", " <div id=\"change\"></div>" )
			), "payment_table", true, true, $sums, "", "payment_table" );

			break;

		case "get_supplier_open_account":
			$sql = "select " . $multi_site->LocalSiteId() . ", part_id, supplier_displayname(part_id), round(sum(amount),2) as total\n"
			       . "from im_business_info\n"
			       . "group by 2\n"
			       . "having total < 0";

			$data   = "<table>";
			$result = sql_query( $sql );
			while ( $row = sql_fetch_row( $result ) ) {
				$data .= gui_row( $row );
			}
			$data .= "</table>";
			print $data;
			break;

		case "get_client_open_account":
			$sql = "select " . $multi_site->LocalSiteId() . ", client_id, client_displayname(client_id), round(sum(transaction_amount),2) as total\n"
			       . "from im_client_accounts\n"
			       . "group by 2\n"
			       . "having total > 1";

			$data   = "<table>";
			$result = sql_query( $sql );
			while ( $row = sql_fetch_row( $result ) ) {
				$data .= gui_row( $row );
			}
			$data .= "</table>";
			print $data;
			break;

		case "get_trans":
			$client_id = get_param( "client_id" );
			$site_id   = get_param( "site_id" );
			// $data .= $this->Run( $func, $site_id, $first, $debug );
			$link = "/fresh/multi-site/multi-get.php?operation=get_open_trans&client_id=" . $client_id;
			print $multi_site->Run( $link, $site_id );
			break;

		case "get_open_invoices":
			$debug = get_param("debug");
			$supplier_id = get_param( "supplier_id", true );
			$site_id     = get_param( "site_id", true );
			// $func, $site_id, $first = false, $debug = false ) {
			print $multi_site->Run( "/org/business/business-post.php?operation=get_open_site_invoices&supplier_id=" . $supplier_id,
				$site_id, true, $debug);
			break;

		case "get_open_site_invoices":
			$debug = get_param("debug");
			$sum         = array();
			$supplier_id = get_param( "supplier_id", true );
			$sql         = "SELECT id, ref, amount, date FROM im_business_info WHERE part_id=" . $supplier_id .
			               " AND document_type = 4\n" .
			               " and pay_date is null " .
			               " order by 4 desc";

			$args = array();
			if ($debug) $args["debug"] = true;
			$args["add_checkbox"] = true;
			$args["checkbox_events"] = "onchange = \"update_display()\"";
			$args["checkbox_class"] = "trans_checkbox";
			print GuiTableContent("table_invoices", $sql, $args);

//			print table_content( "table_invoices", $sql, true, null, null, $sum, true,
//				"trans_checkbox", "onchange=\"update_display()\"" );
			break;

		case "create_receipt":
			$bank_amount = get_param( "bank" );
			$date        = get_param( "date" );
			$change      = get_param( "change" );
			$ids         = get_param( "ids", true );
			$site_id     = get_param( "site_id" );
			$user_id     = get_param( "user_id" );
			$bank_id     = get_param( "bank_id" );

			business_create_multi_site_receipt( $bank_id, $bank_amount, $date, $change, $ids, $site_id, $user_id );
			break;
	}
}

function business_create_multi_site_receipt( $bank_id, $bank_amount, $date, $change, $ids, $site_id, $user_id ) {
	// IDS sent as string.

	// $msg = $bank . " " . $date . " " . $change . " " . comma_implode($ids) . " " . $site_id . " " . $user_id . "<br/>";
	global $multi_site;
	$debug = false;

//var request = "account-post.php?operation=create_receipt" +
//              "&cash=" + cash +
//              "&credit=" + credit +
//              "&bank=" + bank +
//              "&check=" + check +
//              "&date=" + date +
//              "&change=" + change.innerHTML +
//              "&ids=" + del_ids.join() +
//              "&user_id=" + <?php print $customer_id; <!--;-->

	$command = "/fresh/multi-site/multi-get.php?operation=create_receipt&row_ids=" . $ids .
	           "&user_id=" . $user_id . "&bank=" . $bank_amount . "&date=" . $date .
	           "&change=" . $change;
//	print "ZZZZ" . $command;
	$result  = $multi_site->Run( $command, $site_id, true, $debug );

	if ($multi_site->getHttpCode($site_id) != 200) {
		print "can't create<br/>";
		if (developer()) print "getting $command, status: " . $multi_site->getHttpCode($site_id) . "<br/>";
		return false;
	}

	if ( strstr( $result, "כבר" ) ) {
		die( "already paid" );
	}
	if ( strlen( $result ) < 2 ) {
		if (developer()) print $command . "<br/>";
		die( "bad response" );
	}
	if ( strlen( $result ) > 10 ) {
		die( $result );
	}
	// print "r=" . $result . "<br/>";

	$receipt = intval( trim( $result ) );

	// print "re=" . $receipt . '<br/>';

	if ( $receipt > 0 ) {
		// TODO: to parse $id from $result;
		$b = BankTransaction::createFromDB( $bank_id );
		$b->Update( $user_id, $receipt, $site_id );
		print "done.$receipt";
	} else {
		print $receipt;
	}
}

function gui_select_open_supplier( $id = "supplier" ) {
	global $multi_site;

	$values  = html2array( $multi_site->GetAll( "org/business/business-post.php?operation=get_supplier_open_account" ) );

	if (! $values) {
		 return "nothing found";
	}
	// var_dump($values);
	$open    = array();
	$list_id = 0;
	foreach ( $values as $value ) {
		$new                = array();
		$new["id"]          = $list_id ++;
		$new["site_id"]     = $value[0];
		$new["supplier_id"] = $value[1];
		$new["name"]        = $value[2];
		$new["balance"]     = $value[3];
		array_push( $open, $new );
	}

	$datalist_id = "open_supplier";
	$result = GuiDatalist($datalist_id, $open, "id", "name");
	$result .= GuiInputDatalist("supplier", $datalist_id, 'onchange="supplier_selected()"');
	return $result;
	// return gui_select_datalist( $id, "im_suppliers", "open_supplier", "name", $open, 'onchange="supplier_selected()"', null, true );
}

function gui_select_client_open_account( $id = "open_account" ) {
	$output = "";
	global $multi_site;
	$url = "org/business/business-post.php?operation=get_client_open_account";
	$result = $multi_site->GetAll( $url );
	foreach ($multi_site->getHttpCodes() as $side_id => $code){
		if ($code != 200) {
			$output .= "Can't get result from " . $multi_site->getSiteName($side_id) . " error: $code <br/>";
			if (get_user_id()== 1) $output .= $url . "<br/>";
		}
	}
	$values  = html2array( $result );
	$open    = array();
	$list_id = 0;
	foreach ( $values as $value ) {
		$new              = array();
		$new["id"]        = $list_id ++;
		$new["site_id"]   = $value[0];
		$new["client_id"] = $value[1];
		$new["name"]      = $value[2];
		$new["balance"]   = $value[3];
		array_push( $open, $new );
	}
	$events = 'onchange="client_selected()"';
	$datalist_id = $id . "_datalist";
	$output .= GuiInputDatalist($id, $datalist_id, $events);
	$output .= GuiDatalist( $datalist_id, $open, "id","name", false);

	return $output;
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
	print $month_year;
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
		$row[0] = gui_hyperlink( $row[0], "/fresh/delivery/get-delivery.php?id=" . $row[0] );
		if ( $row[6] == 0 ) {
			print $row[0] . "<br/>";
			$sql1   = "SELECT round(reduce_vat(line_price),2) FROM im_delivery_lines WHERE delivery_id = " . $row[0] . " AND product_name LIKE '%משלוח%'";
			$row[6] = sql_query_single_scalar( $sql1 );
			$row[7] = $row[4] - $row[5] - $row[6];
		}
//		$id =  $row[0];

		// $row = array($id, $row[1]);
		array_push( $table, $row );
		// $driver_array[ $row[8] ] += $row[6];
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


// array(5) { ["id"]=> int(1) ["site_id"]=> string(1) "4" ["client_id"]=> string(1) "0" ["name"]=> string(0) "" ["balance"]=> string(7) "1831.98" } [2]=> array(5)