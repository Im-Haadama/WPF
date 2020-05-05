<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */




if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) )) ;
}
require_once(FRESH_INCLUDES . '/im-config.php');

require_once( '../r-shop_manager.php' );
require_once( 'account.php' );
require_once( '../invoice4u/invoice.php' );
require_once( '../delivery/delivery.php' );

$debug = true;

$operation = $_GET["operation"];
MyLog( __FILE__, "operation = " . $operation );
global $invoice_user;
global $invoice_password;

$operation = $_GET["operation"];
switch ( $operation ) {
	case "set_client_type":

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
		MyLog( "zero_near_zero" );
		zero_near_zero();
		break;

	case "create_invoice":
		MyLog( "create_invoice" );
		$delivery_ids = $_GET["ids"];
		$user_id      = $_GET["user_id"];
		$ids          = explode( ',', $delivery_ids );
		$doc_id = create_invoice( $ids, $user_id );
		if ($doc_id > 0) print "done.$doc_id";
		break;

	case "create_receipt":
		MyLog( "create_receipt" );
		$cash         = GetParam( "cash" );
		$bank         = GetParam( "bank" );
		$check        = GetParam( "check" );
		$credit       = GetParam( "credit" );
		$change       = GetParam( "change" );
		$row_ids      = GetParamArray( "row_ids" );
		$user_id      = GetParam( "user_id", true );
		$date         = GetParam( "date" );

		//print "create receipt<br/>";
		// (NULL, '709.6', NULL, NULL, '205.44', '', '2019-01-22', Array)
		create_receipt( $cash, $bank, $check, $credit, $change, $user_id, $date, $row_ids );
		break;

	case "update_invoice_user":
	case "create_invoice_user":
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
		if ( SqlQuerySingleScalar( "SELECT count(*) FROM wp_users WHERE user_email = '" . $email . "'" ) > 0 ) {
			print "exists";

			return;
		}
		// New. find login name
		$new_login = strtok( $email, "@" );
		if ( SqlQuerySingleScalar( "SELECT count(*) FROM wp_users WHERE user_login = '" . $new_login . "'" ) == 0 ) {
			print $new_login;

			return;
		}
		$trial = 1;
		while ( 1 ) {
			$new_login = strtok( $email, "@" ) . $trial;

			if ( SqlQuerySingleScalar( "SELECT count(*) FROM wp_users WHERE user_login = '" . $new_login . "'" ) == 0 ) {
				print $new_login;

				return;
			}
			$trial ++;

		}
		print $new_login;
		break;

	case "add_user":
	case "legacy_user":
		// print "adding user";
		$user    = $_GET["user"];
		$name    = urldecode( $_GET["name"] );
		$email   = $_GET["email"];
		$address = urldecode( $_GET["address"] );
		$city    = urldecode( $_GET["city"] );
		$phone   = $_GET["phone"];
		$zip     = $_GET["zip"];
		$id = add_im_user( $user, $name, $email, $address, $city, $phone, $zip );

		if ($operation == "legacy_user")
			update_user_meta( $id, '_client_type', 2 );
		print "done";
	break;

	case "table":
		$customer_id = GetParam("customer_id", true);
		$page = GetParam("page", false, 1);
		$search = GetParam("search", false, null);
		if ($search){
			print load_scripts(array("/core/gui/client_tools.js", "/core/data/data.js"));
			$args = array();
			$search_url = "search_table('im_client_accounts', '" . AddParamToUrl($url, array( "operation" => "search", "customer_id" => $customer_id)) . "')";
			$args["search"] = $search_url; //'/fresh/bank/bank-page.php?operation=do_search')";
			GemSearch("im_client_accounts", $args);
			return;
		}
		$args = [];
		$args["page"] = $page;
		$table_lines = show_trans( $customer_id, TransView::default, $args );
		print $table_lines;
		break;

	case "search":
		print HeaderText();
		$args = [];
		$args["ignore_list"] = array("search", "operation", "table_name", "id", "dummy", "customer_id");
		$ids=data_search("im_client_accounts",$args);
		Core_Html::gui_header(1, "Results");
		if (! $ids){
			print ImTranslate("Nothing found");
			return;
		}
		$args["query"] = "id in (" . CommaImplode($ids) . ")";
		$customer_id = GetParam("customer_id", true);
		print show_trans($customer_id, TransView::default, $args);
		return;


	case "send":
		$del_ids = GetParam("del_ids", true);
		send_deliveries($del_ids);
		print "done";
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
	try {
		$doc_id = invoice_create_document( "i", $ids, $user_id, date( "Y-m-d" ) );
	} catch ( Exception $e ) {
		print "error: " . $e->getMessage();
	}

	if ( is_numeric( $doc_id ) && $doc_id > 0 ) {
		$sql = "UPDATE im_delivery SET payment_receipt = " . $doc_id . " WHERE id IN (" . CommaImplode( $ids ) . " ) ";
		SqlQuery( $sql );

		return $doc_id;
	} else {
		print "doc_id: " . $doc_id . "<br/>";
	}
}


// $current_user = 0;

function sc_display_name( $atts ) {
	global $current_user;

	return GetUserName( $current_user );
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
		$post_id      = info_get( "summary_post" );
		if ( ! $post_id ) {
			print "בקש ממנהל מערכת להגדיר את פוסט הסיכום. פרמטר מערכת summary_post<br/>";
			print "הודעה לא נשלחה";

			return;

		}
		$post_text = SqlQuerySingleScalar( "SELECT post_content FROM wp_posts WHERE id = " . $post_id );

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
	$new_text = Core_Html::gui_header( 1, "מצב חשבון" );
	$new_text .= "יתרה לתשלום " . client_balance( $id ) . "<br/>";
	$new_text .= show_trans( $id, TransView::read_last ) . "<br/>";
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



function zero_near_zero() {
	print "מאפס קרובים לאפס <br/>";

	$sql = 'select round(sum(ia.transaction_amount),2), ia.client_id, wu.display_name'
	       . ' from im_client_accounts ia'
	       . ' join wp_users wu'
	       . ' where wu.id=ia.client_id'
	       . ' group by client_id';


	$result = SqlQuery( $sql );

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

