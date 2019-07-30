<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../r-shop_manager.php' );
require_once( 'account.php' );
require_once( '../invoice4u/invoice.php' );
require_once( '../delivery/delivery.php' );

$debug = true;

$operation = $_GET["operation"];
my_log( __FILE__, "operation = " . $operation );
global $invoice_user;
global $invoice_password;

$operation = $_GET["operation"];
switch ( $operation ) {
	case "set_client_type":
		$id   = $_GET["id"];
		$type = $_GET["type"];
		set_client_type( $id, $type );
		break;

	case "save_payment":
		$user_id   = $_GET["user_id"];
		$method_id = $_GET["method_id"];
		update_user_meta( $user_id, 'payment_method', $method_id );
		break;

	case "send_month_summary":
		$user_ids = $_GET["ids"];
		$ids      = explode( ',', $user_ids );
		send_month_summary( $ids );
		break;

	case "zero_near_zero":
		my_log( "zero_near_zero" );
		zero_near_zero();
		break;

	case "create_invoice":
		my_log( "create_invoice" );
		$delivery_ids = $_GET["ids"];
		$user_id      = $_GET["user_id"];
		$ids          = explode( ',', $delivery_ids );
		create_invoice( $ids, $user_id );
		break;

	case "create_receipt":
		my_log( "create_receipt" );
		$cash         = get_param( "cash" );
		$bank         = get_param( "bank" );
		$check        = get_param( "check" );
		$credit       = get_param( "credit" );
		$change       = get_param( "change" );
		$row_ids      = get_param_array( "row_ids" );
		$user_id      = get_param( "user_id", true );
		$date         = get_param( "date" );

		//print "create receipt<br/>";
		// (NULL, '709.6', NULL, NULL, '205.44', '', '2019-01-22', Array)
		create_receipt( $cash, $bank, $check, $credit, $change, $user_id, $date, $row_ids );
		break;

	case "create_invoice_user":
		$id = $_GET["id"];
		invoice_create_user( $id );
		break;

	case "update_invoice_user":
		$id = $_GET["id"];
		invoice_create_user( $id );
		break;

	case "get_client_id":
		$customer_id = $_GET["customer_id"];
		$invoice     = new Invoice4u( $invoice_user, $invoice_password );

		print "client name " . $client_name . "<br/>";
		$client_name = get_customer_name( $customer_id );
		$client      = $invoice->GetCustomerByName( $client_name );
		print $client->ID;
		break;

	case "check_email":
		$email = $_GET["email"];
		if ( sql_query_single_scalar( "SELECT count(*) FROM wp_users WHERE user_email = '" . $email . "'" ) > 0 ) {
			print "exists";

			return;
		}
		// New. find login name
		$new_login = strtok( $email, "@" );
		if ( sql_query_single_scalar( "SELECT count(*) FROM wp_users WHERE user_login = '" . $new_login . "'" ) == 0 ) {
			print $new_login;

			return;
		}
		$trial = 1;
		while ( 1 ) {
			$new_login = strtok( $email, "@" ) . $trial;

			if ( sql_query_single_scalar( "SELECT count(*) FROM wp_users WHERE user_login = '" . $new_login . "'" ) == 0 ) {
				print $new_login;

				return;
			}
			$trial ++;

		}
		print $new_login;
		break;

	case "add_user":
		// print "adding user";
		$user    = $_GET["user"];
		$name    = urldecode( $_GET["name"] );
		$email   = $_GET["email"];
		$address = urldecode( $_GET["address"] );
		$city    = urldecode( $_GET["city"] );
		$phone   = $_GET["phone"];
		$zip     = $_GET["zip"];
		add_im_user( $user, $name, $email, $address, $city, $phone, $zip );
		break;

	case "table":
		$customer_id = $_GET["customer_id"];
		$table_lines = show_trans( $customer_id );
		print $table_lines;
		break;

	case "total":
		$customer_id = $_GET["customer_id"];
		print "יתרה: " . sql_query_single_scalar( "SELECT round(sum(transaction_amount), 1) FROM im_client_accounts WHERE client_id = " . $customer_id );
		break;

	case "send":
		$del_ids = explode( ",", $_GET["del_ids"] );
		foreach ( $del_ids as $del_id ) {
			$delivery = new delivery( $del_id );
			print "נשלח ל: " . $info_email;
			print "track: " . $track_email;
			$delivery->send_mail( $track_email, $edit );
		}
		break;

	case "get_open_trans":
		$client_id = get_param( "client_id" );
		print show_trans( $client_id, eTransview::not_paid );
		break;

}

function create_invoice( $ids, $user_id ) {
	$no_ids = true;
	foreach ( $ids as $id ) {
		if ( $id > 0 ) {
			$no_ids = false;
		}
	}
	if ( $no_ids ) {
		print "לא נבחרו תעודות משלוח";

		return;
	}
	$doc_id = invoice_create_document( "i", $ids, $user_id, date( "Y-m-d" ) );

	if ( is_numeric( $doc_id ) && $doc_id > 0 ) {
		$sql = "UPDATE im_delivery SET payment_receipt = " . $doc_id . " WHERE id IN (" . comma_implode( $ids ) . " ) ";
		sql_query( $sql );

		print $doc_id;
	} else {
		print "doc_id: " . $doc_id . "<br/>";
	}
}

function create_receipt( $cash, $bank, $check, $credit, $change, $user_id, $date, $row_ids ) {

	if ( ! ( $user_id > 0 ) ) {
		throw  new Exception( "Bad customer id " . __CLASS__ );
	}

	$del_ids = array();
	$no_ids = true;
	foreach ( $row_ids as $id ) {
		if ( $id > 0 ) {
			$no_ids = false;
			array_push($del_ids, sql_query_single_scalar("select transaction_ref from im_client_accounts where ID = " . $id));
		} else {
			die ("bad id " . $id);
		}
	}

	if ( $no_ids ) {
		print "לא נבחרו תעודות משלוח";

		return;
	}
	$c = $cash - $change;
//        if (abs($c) < 0) $c =0;
	//      if (round($c,0) < 1 or round($c,0) < 1)
	// Check if paid (some bug cause double invoice).
	$sql = "SELECT count(payment_receipt) FROM im_delivery WHERE id IN (" . comma_implode( $del_ids ) . " )";
	if ( sql_query_single_scalar( $sql ) > 0 ) {
		print " כבר שולם" . comma_implode( $del_ids ) . " <br/>";

		return;
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
	} else {
		print "doc_id: " . $doc_id . "<br/>";
	}
}

function add_im_user( $user, $name, $email, $address, $city, $phone, $zip ) {

	if ( strlen( $email ) < 1 ) {
		$email = randomPassword() . "@aglamaz.com";
	}


	if ( $user == "אוטומטי" or strlen( $user ) < 5 ) {
		$user = substr( $email, 0, 8 );
		print "user: " . $user . "<br/>";
	}

	print "email: " . $email . "<br/>";
	print "user: " . $user . "<br/>";

	$id = wp_create_user( $user, randomPassword(), $email );
	if ( ! is_numeric( $id ) ) {
		print "לא מצליח להגדיר יוזר";
		var_dump( $id );

		return;
	}
	$name_part = explode( " ", $name );
	update_user_meta( $id, 'first_name', $name_part[0] );
	update_user_meta( $id, 'shipping_first_name', $name_part[0] );
	unset( $name_part[0] );
	update_user_meta( $id, 'billing_address_1', $address );
	update_user_meta( $id, 'billing_city', $city );

	update_user_meta( $id, 'last_name', implode( " ", $name_part ) );
	update_user_meta( $id, 'shipping_last_name', implode( " ", $name_part ) );
	update_user_meta( $id, 'billing_phone', $phone );
	update_user_meta( $id, 'billing_postcode', $zip );

	update_user_meta( $id, 'shipping_address_1', $address );
	update_user_meta( $id, 'shipping_postcode', $zip );
	update_user_meta( $id, 'shipping_city', $city );
	update_user_meta( $id, 'legacy_user', 2 );

	im_set_default_display_name( $id);
	print "משתמש התווסף בהצלחה";

}

function randomPassword() {
	$alphabet    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass        = array(); //remember to declare $pass as an array
	$alphaLength = strlen( $alphabet ) - 1; //put the length -1 in cache
	for ( $i = 0; $i < 8; $i ++ ) {
		$n      = rand( 0, $alphaLength );
		$pass[] = $alphabet[ $n ];
	}

	return implode( $pass ); //turn the array into a string
}

// $current_user = 0;

function sc_display_name( $atts ) {
	global $current_user;

	return get_user_name( $current_user );
}

function sc_id( $atts ) {
	global $current_user;

	return $current_user;
}

function sc_trans( $atts ) {
	global $current_user;
	$text = "תנועות אחרונות" . "<br/>";

	$text .= show_trans( $current_user, true );

	return $text;
}

function sc_balance( $atts ) {
	global $current_user;

	$bal = client_balance( $current_user );
	if ( is_numeric( $bal ) and $bal > 50 ) {
		$text = "יתרה לתשלום " . $bal;
	}

	return $text;
}

function set_client_type( $id, $type ) {
	print $id . " " . $type . "<br/>";
	if ( $type == 0 ) {
		delete_user_meta( $id, "_client_type" );

		return;
	}
	$meta = sql_query_single_scalar( "select type from im_client_types where id = " . $type );
	// print "meta: " . $meta . "<br/>";
	update_user_meta( $id, "_client_type", $meta );
}

function send_month_summary( $user_ids ) {
	global $current_user;
	global $support_email;
	add_shortcode( 'display_name', 'sc_display_name' );
	add_shortcode( 'trans', 'sc_trans' );
	add_shortcode( 'id', 'sc_id' );
	add_shortcode( 'balance', 'sc_balance' );

	foreach ( $user_ids as $id ) {
		$current_user = $id;
		$message      = header_text( true );
		$post_id      = info_get( "summary_post" );
		if ( ! $post_id ) {
			print "בקש ממנהל מערכת להגדיר את פוסט הסיכום. פרמטר מערכת summary_post<br/>";
			print "הודעה לא נשלחה";

			return;

		}
		$post_text = sql_query_single_scalar( "SELECT post_content FROM wp_posts WHERE id = " . $post_id );

		if ( strlen( $post_text ) < 20 ) {
			print "לא נמצא פוסט סיכום'<br/>";
			print "הודעה לא נשלחה";

			return;

		}

		$message .= '<body dir="rtl">';
		// $message .= do_shortcode(replace_shortcode($post_text, $id));
		// print "text = " .  $post_text;
		$message .= do_shortcode( replace_shortcode( $post_text, $id ) );
		// // $message .= ( $post_text );
		$message .= '</body>';

		$message .= "</html>";

		$user_info = get_userdata( $id );

		print "שולח סיכום חודשי ללקוח " . get_customer_name( $id ) . " " . get_customer_email( $id ) . "<br/>";
		send_mail( "סיכום חודשי עם האדמה", $user_info->user_email . ", " . $support_email, $message );
		// print $message;
		print "הסתיים";
	}
}

function replace_shortcode( $text, $id ) {
	$new_text = gui_header( 1, "מצב חשבון" );
	$new_text .= "יתרה לתשלום " . client_balance( $id ) . "<br/>";
	$new_text .= show_trans( $id, eTransview::read_last ) . "<br/>";
	$new_text = str_replace( "[im-haadama-account-summary]", $new_text, $text );

	return str_replace( "[display_name]", get_customer_name( $id ), $new_text );

}

function invoice_create_user( $user_id ) {
	// First change wordpress display name
	im_set_default_display_name( $user_id);
	global $invoice_user, $invoice_password;

	$invoice = new Invoice4u( $invoice_user, $invoice_password );

	// $invoice->Login();

	$name  = get_customer_name( $user_id );
	$email = get_customer_email( $user_id );
	$phone = get_customer_phone( $user_id );

	// print "Creating user. name=" . $name . " email = " . $email . " phone = " . $phone. "<br/>";

	$invoice->CreateUser( $name, $email, $phone );

	$client = $invoice->GetCustomerByName( $name );

	$id = $client->ID;

	// Save locally.
	update_user_meta( $user_id, 'invoice_id', $id );

	print $id;
}

function invoice_update_user( $user_id ) {
	// First change wordpress display name
	global $invoice_user, $invoice_password;

	$invoice = new Invoice4u( $invoice_user, $invoice_password );

	// $invoice->Login();

	$name  = get_customer_name( $user_id );

	// print "Creating user. name=" . $name . " email = " . $email . " phone = " . $phone. "<br/>";

		$client = $invoice->GetCustomerByName( $name );

	$id = $client->ID;

	// Save locally.
	update_user_meta( $user_id, 'invoice_id', $id );

	print $id;
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
		// print "del id " . $del_id;

		$sql = 'select product_name, quantity, vat, price, line_price '
		       . ' from im_delivery_lines where delivery_id = ' . $del_id;

		$result = sql_query( $sql );

		// var_dump ($client->ClientID);

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
	$doc_id = $invoice->CreateDocument( $doc );

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

function zero_near_zero() {
	print "מאפס קרובים לאפס <br/>";

	$sql = 'select round(sum(ia.transaction_amount),2), ia.client_id, wu.display_name'
	       . ' from im_client_accounts ia'
	       . ' join wp_users wu'
	       . ' where wu.id=ia.client_id'
	       . ' group by client_id';


	$result = sql_query( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		// $line = '';
		$customer_total = $row[0];
		$customer_id    = $row[1];
		$customer_name  = $row[2];
		// print "name = " . $customer_name . " total= " . $customer_total . "<br/>";

		if ( $customer_total < 5 and $customer_total > - 5 and $customer_total <> 0 ) {
			print ( $customer_id . ". " . $customer_total . "<br/>" );
			account_add_transaction( $customer_id, date( "Y-m-d" ), - $customer_total, 0, "איפוס" );
		}
	}
}

?>

