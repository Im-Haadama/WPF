<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../tools_wp_login.php' );
require_once( 'account.php' );
require_once( '../invoice4u/invoice.php' );
require_once( '../delivery/delivery.php' );

$debug = true;

$operation = $_GET["operation"];

my_log( __FILE__, "operation = " . $operation );

$operation = $_GET["operation"];
switch ( $operation ) {
	case "save_payment":
		$user_id   = $_GET["user_id"];
		$method_id = $_GET["method_id"];
		update_user_meta( $user_id, 'payment_method', $method_id );

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
		print "my log <br/>";
		my_log( "create_invoice" );
		$delivery_ids = $_GET["ids"];
		$user_id      = $_GET["user_id"];
		$ids          = explode( ',', $delivery_ids );
		$doc_id       = invoice_create_document( "i", $ids, $user_id );
		break;

	case "create_receipt":
		print "my log <br/>";
		my_log( "create_receipt" );
		$cash         = $_GET["cash"];
		$bank         = $_GET["bank"];
		$check        = $_GET["check"];
		$credit       = $_GET["credit"];
		$change       = $_GET["change"];
		$delivery_ids = $_GET["ids"];
		$user_id      = $_GET["user_id"];
		$ids          = explode( ',', $delivery_ids );
		$c            = $cash - $change;
//        if (abs($c) < 0) $c =0;
		//      if (round($c,0) < 1 or round($c,0) < 1)
		$doc_id   = invoice_create_document( "r", $ids, $user_id, $c, $bank, $credit, $check );
		$pay_type = pay_type( $cash, $bank, $credit, $check );
		if ( is_numeric( $doc_id ) && $doc_id > 0 ) {
			$pay_description = $pay_type . " " . $_GET["ids"];
			account_add_transaction( $user_id, date( "Y-m-d" ), $change - ( $cash + $bank + $credit + $check ), $doc_id, $pay_description );
			account_add_transaction( $user_id, date( "Y-m-d" ), - $change, $doc_id, $change > 0 ? "עודף" : "חוסר/ניצול יתרה" );
			print "חשבונית מס קבלה מספר " . $doc_id . "נוצרה!" . "<br/>";
		} else {
			print "doc_id: " . $doc_id . "<br/>";
		}
		break;

	case "create_invoice_user":
		$id = $_GET["id"];
		invoice_create_user( $id );
		break;


	case "get_client_id":
		$customer_id = $_GET["customer_id"];
		$invoice     = new Invoice4u();
		$invoice->Login();

		if ( is_null( $invoice->token ) ) {
			die ( "can't login" );
		}

		// print "client name " . $client_name . "<br/>";
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
		print "adding user";
		$user    = $_GET["user"];
		$name    = urldecode( $_GET["name"] );
		$email   = $_GET["email"];
		$address = urldecode( $_GET["address"] );
		$city    = urldecode( $_GET["city"] );
		$phone   = $_GET["phone"];
		$zip     = $_GET["zip"];
		add_im_user( $user, $name, $email, $address, $city, $phone, $zip );
		break;

}

function add_im_user( $user, $name, $email, $address, $city, $phone, $zip ) {
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
	update_user_meta( $id, 'legacy_user', 1 );
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

$current_user = 0;

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
		$post_text    = sql_query_single_scalar( "SELECT post_content FROM wp_posts WHERE post_title='סיכום חודשי' AND " .
		                                         " post_type='page'" );

		$message .= '<body dir="rtl">';
		$message .= do_shortcode( $post_text );
		$message .= '</body>';

		$message .= "</html>";

		$user_info = get_userdata( $id );

		print "שולח סיכום חודשי ללקוח " . sc_display_name( null ) . "<br/>";
		send_mail( "סיכום חודשי עם האדמה", $user_info->user_email . ", " . $support_email, $message );
	}
}

function invoice_create_user( $user_id ) {
	$invoice = new Invoice4u();
	$invoice->Login();

	$name  = get_customer_name( $user_id );
	$email = get_customer_email( $user_id );
	$phone = get_customer_phone( $user_id );

//    print $name . "<br/>";
//    print $email . "<br/>";
//    print $phone . "<br/>";
	$invoice = new Invoice4u();
	$invoice->Login();
	if ( is_null( $invoice->token ) ) {
		die ( "can't login" );
	}
	$invoice->CreateUser( $name, $email, $phone );

	$client = $invoice->GetCustomerByName( $name );
	print $client->ID;

}

function invoice_create_document( $type, $ids, $user_id, $cash = 0, $bank = 0, $credit = 0, $check = 0 ) {
	// print "create invoice<br/>";
	global $debug;

	$invoice = new Invoice4u();
	$invoice->Login();

	if ( is_null( $invoice->token ) ) {
		die ( "can't login" );
	}

	$client_name = get_customer_name( $user_id );

	// print "client name " . $client_name . "<br/>";
	$client = $invoice->GetCustomerByName( $client_name );
	// var_dump($client);

	if ( is_null( $client->ID ) ) {
		// var_dump($invoice);
		die( "cant get client name" );
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
			$doc->DocumentType = 3; // invoice receipt
			break;
		case "i":
			$doc->DocumentType = 1; // invoice
			break;
	}

	// Set the subject
	$subject = "סלים" . " ";
	foreach ( $ids as $del_id ) {
		$subject .= $del_id . ", ";
	}
	$doc->Subject = trim( $subject, "," );

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
				$item->Price    = $row[3];
				$item->Quantity = $row[1];
				if ( $row[2] > 0 ) {
					$item->TaxPercentage   = 17;
					$item->TotalWithoutTax = round( $row[4] / 1.17, 2 );
				} else {
					$item->TaxPercentage   = 0;
					$item->TotalWithoutTax = $row[4];
				}
				$item->Total = $row[4];
				//            if ($debug) {
				//                print $item->Name . ", " . $row[2] . "total: " . $item->Total . "<br/>";
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

