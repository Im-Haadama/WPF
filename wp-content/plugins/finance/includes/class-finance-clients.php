<?php


/**
 * Class Finance_Clients
 */
class Finance_Clients {

	static function admin_page() {
		$client_id    = GetParam( "id", false, null );
		$include_zero = GetParam( "include_zero", false, false );
		if ( $client_id ) {
			print self::client_account( $client_id );
		} else {
			print self::all_clients( $include_zero );
		}
	}

	static function all_clients( $include_zero = false ) {
		$output = "<center><h1>יתרת לקוחות לתשלום</h1></center>";
		$output .= Core_Html::GuiHyperlink( __( "include paid" ), AddToUrl( "include_zero", 1 ) ) . "<br/>";

		$sql = 'select round(sum(ia.transaction_amount),2), ia.client_id, wu.display_name, client_payment_method(ia.client_id), max(date) '
		       . ' from im_client_accounts ia'
		       . ' join wp_users wu'
		       . ' where wu.id=ia.client_id'
		       . ' group by client_id '
		       . ' order by 5 desc';

		$result = sql_query( $sql );

		$table_lines         = array();
		$table_lines_credits = array();

		while ( $row = mysqli_fetch_row( $result ) ) {
			// $line = '';
			$customer_total = $row[0];
			$customer_id    = $row[1];
			$customer_name  = $row[2];
//			print $customer_name . " " . $customer_total . "<br/>";
			$C = new Fresh_Client( $customer_id );

			$line = Core_Html::gui_cell( gui_checkbox( "chk_" . $customer_id, "user_chk" ) );
			$line .= "<td><a href = \"" . self::getLink( $customer_id ) . "\">" . $customer_name . "</a></td>";

			$line           .= "<td>" . $customer_total . "</td>";
			$payment_method = $C->get_payment_method();
//			$accountants = self::payment_get_accountants($payment_method);
			// print $accountants . " " . get_user_id() . "<br/>";
//			if (!strstr($accountants, (string) get_user_id())) continue;

			$line .= "<td>" . $row[4] . "</td>";
			$line .= "<td>" . Finance_Payment_Methods::get_payment_method_name( $payment_method ) . "</td>";
			if ( $customer_total > 0 or $include_zero ) {
				array_push( $table_lines, array( - $customer_total, $line ) );
			}
//				print "pushed $customer_name<br/>";
//			if ( $include_zero || $customer_total > 0 ) {
			//array_push( $table_lines, array( - $customer_total, $line ) );
//				array_push( $table_lines, array( $payment_method, $line ) );
//			} else if ( $customer_total < 0 ) {
//				print "pushed $customer_name<br/>";
		}
//		}

		if ( count( $table_lines ) ) {
			$table = "<table>";
			$table .= "<tr>";
			$table .= Core_Html::gui_cell( "בחר" );

			$table .= "<td>לקוח</td>";
			$table .= "<td>יתרה לתשלום</td>";
			$table .= "<td>הזמנה אחרונה</td>";
			$table .= "</tr>";
			for ( $i = 0; $i < count( $table_lines ); $i ++ ) {
				$line  = $table_lines[ $i ][1];
				$table .= "<tr> " . trim( $line ) . "</tr>";
			}
			$table  .= "</table>";
			$output .= $table;
		} else {
			$output .= __( "All paid!" ) . "<br/>";
		}

		$output .= Core_Html::GuiButton( "btn_pay", "Pay", array( "action" => "pay_credit('" . Finance::getPostFile() . "')" ) );

//		$table = str_replace( "\r", "", $table );
		return $output;
	}

	static function client_account( $customer_id ) {
		global $invoice_user;
		global $invoice_password;
		require_once( ABSPATH . "im-config.php" );

		$result = "";
		try {
			$invoice = new Finance_Invoice4u( $invoice_user, $invoice_password );
		} catch ( Exception $e ) {
			$invoice = null;
		}
		$u         = new Fresh_Client( $customer_id );
		$client_id = ( $invoice ? $invoice->GetInvoiceUserId( $customer_id, $u->get_customer_email() ) : "Not connected" );
//	var_dump($client);

		$user_info = Core_Html::gui_table_args( array(
			array( "name", $u->getName() ),
			array( "דואל", $u->get_customer_email() ),
			array( "טלפון", $u->get_phone_number() ),
			array( "מספר מזהה", Core_Html::gui_label( "invoice_client_id", $client_id ) ),
			array(
				"אמצעי תשלום",
				Finance_Payment_Methods::gui_select_payment( "payment", "onchange=\"save_payment_method()\"", $u->get_payment_method() )
			)
		), "customer_details", array( "class" => "widefat" ) );
		$style     = "table.payment_table { border-collapse: collapse; } " .
		             " table.payment_table, td.change, th.change { border: 1px solid black; } ";
		$args      = array( "style" => $style, "class" => "payment_table" );

		if ( $invoice ) {
			$new_tran = Core_Html::gui_table_args( array(
				array(
					"תשלום",
					Core_Html::GuiButton( "btn_receipt", "Invoice Reciept", array( "action" => "create_receipt('" . Finance::getPostFile() . "', " . $customer_id . ")" ) )
				),
				array( "תאריך", Core_Html::gui_input_date( "pay_date", "" ) ),
				array( "מזומן", Core_Html::gui_input( "cash", "", array( 'onkeyup="update_sum()"' ) ) ),
				array( "אשראי", Core_Html::gui_input( "credit", "", array( 'onkeyup="update_sum()"' ) ) ),
				array( "העברה", Core_Html::gui_input( "bank", "", array( 'onkeyup="update_sum()"' ) ) ),
				array( "המחאה", Core_Html::gui_input( "check", "", array( 'onkeyup="update_sum()"' ) ) ),
				array( "עודף", " <div id=\"change\"></div>" )
			), "payment_table",
				array( "class" => "widefat" ) );
		} else {
			$new_tran = Core_Html::GuiHeader( 1, "לא ניתן להחבר ל Invoice4u. בדוק את ההגדרות ואת המנוי. יוזר $invoice_user" );
		}

		$result .= Core_Html::gui_table_args( array(
			array( Core_Html::gui_header( 2, "פרטי לקוח", true ), Core_Html::gui_header( 2, "קבלה", true ) ),
			array( $user_info, $new_tran )
		) );

		$result .= Core_Html::GuiHeader( 2, __( "Balance" ) . ": " .
		                                    sql_query_single_scalar( "SELECT round(sum(transaction_amount), 1) FROM im_client_accounts WHERE client_id = " . $customer_id ) );
		$result .= '<div id="logging"></div>';

		$args   = array( "post_file" => Finance::getPostFile(), "class" => "widefat" );
		$result .= Fresh_Client_Views::show_trans( $customer_id, eTransview::default, $args );

		return $result;
	}


	/**
	 * Gets row ids of client transactions to create invoice receipt.
	 * @param $cash
	 * @param $bank
	 * @param $check
	 * @param $credit
	 * @param $change - must balace the difference between the sum of lines and the paid amount.
	 * @param $user_id
	 * @param $date
	 * @param $row_ids - client_accounts row ids. Should be non draft documents.
	 *
	 * @return bool|int|string
	 * @throws Exception
	 */
	static function create_receipt_from_account_ids( $cash, $bank, $check, $credit, $change, $user_id, $date, $row_ids )
	{
		if ( ! $date ) {
			$date = date( 'Y-m-d' );
		}
		if ( ! ( $user_id > 0 ) ) {
			throw  new Exception( "Bad customer id " . __CLASS__ );
		}

		$del_ids = array();
		$no_ids  = true;
		foreach ( $row_ids as $id ) {
			if ( $id > 0 ) {
				$no_ids = false;
				$del_id = sql_query_single_scalar( "select transaction_ref from im_client_accounts where ID = " . $id );
				if ( $del_id > 0 ) {
					array_push( $del_ids, $del_id );
				} else {
					print "Didn't find delivery id for account row " . $id;

					return false;
				}
			} else {
				die ( "bad id " . $id );
			}
		}

		if ( $no_ids ) {
			print "לא נבחרו תעודות משלוח";

			return false;
		}
		return self::CreateReceipt($cash, $bank, $check, $credit, $change, $user_id, $date, $del_ids);
	}

	private static function CreateReceipt($cash, $bank, $check, $credit, $change, $user_id, $date, $del_ids)
	{
		$u = new Fresh_Client( $user_id );
		$c = $cash - $change;

		// Check if paid (some bug cause double invoice).
		$sql = "SELECT count(payment_receipt) FROM im_delivery WHERE id IN (" . CommaImplode( $del_ids ) . " )";
		if ( sql_query_single_scalar( $sql ) > 0 ) {
			print " כבר שולם" . CommaImplode( $del_ids ) . " <br/>";

			return false;
		}

		$doc_id   = self::CreateDocument( "r", $del_ids, $user_id, $u->get_customer_email(), $date, $c, $bank, $credit, $check );

		$pay_type = self::pay_type( $cash, $bank, $credit, $check );
		if ( is_numeric( $doc_id ) && $doc_id > 0 ) {
			$pay_description = $pay_type . " " . CommaImplode( $del_ids );
			$sql = "UPDATE im_delivery SET payment_receipt = " . $doc_id . " WHERE id IN (" . CommaImplode( $del_ids ) . " ) ";
			sql_query( $sql );
			$u->add_transaction( $date, $change - ( $cash + $bank + $credit + $check ), $doc_id, $pay_description );
			if ( abs( $change ) > 0 ) {
				$u->add_transaction($date, - $change, $doc_id, $change > 0 ? "עודף" : "יתרה" );
			}
			return  $doc_id;
		} else {
			print "Error: " . $doc_id . "<br/>";
			return false;
		}
	}

	static function pay_type( $cash, $bank, $credit, $check ) {
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

	static function CreateDocument( $type, $ids, $customer_id, $email, $date, $cash = 0, $bank = 0, $credit = 0, $check = 0, $subject = null ) {
		global $debug;
		global $invoice_user;
		global $invoice_password;
		require_once(ABSPATH . "im-config.php");

		if ( ! ( $customer_id > 0 ) )
			throw new Exception( "Bad customer id" . __CLASS__);

		$invoice = new Finance_Invoice4u( $invoice_user, $invoice_password );

		$invoice->Login();

//	print "customer id : " . $customer_id . "<br/>";

		$invoice_client_id = $invoice->GetInvoiceUserId( $customer_id, $email );

//	print "invoice client id " . $invoice_client_id . "<br/>";

		$client = $invoice->GetCustomerById( $invoice_client_id );

		if ( !$client or  ! ( $client->ID ) > 0 ) {
			print "Client not found " . $customer_id . "<br>";

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
			$subject = "סלים" . " " . CommaImplode( $ids );
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
			var_dump($ids);
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


	static function getLink($client_id) {
		return "/wp-admin/users.php?page=client-accounts&id=$client_id";
	}

	static function payment_get_accountants($payment_id)
	{
		return sql_query_single_scalar("select accountants from im_payments where id = " . $payment_id);
	}

	static function install()
	{
		return;
		$db_prefix = get_table_prefix();
		if (! table_exists("client_accounts"))
			sql_query("create table ${db_prefix}client_accounts
(
	ID bigint auto_increment
		primary key,
	client_id bigint not null,
	date date not null,
	transaction_amount double not null,
	transaction_method text not null,
	transaction_ref bigint not null
)
charset=utf8;
");
	}
}
