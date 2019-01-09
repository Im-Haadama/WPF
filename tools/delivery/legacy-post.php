<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/10/18
 * Time: 11:17
 */

//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );
require_once( "../im_tools.php" );
require_once( "../invoice4u/invoice.php" );
require_once( "../orders/orders-common.php" );
require_once( "../orders/Order.php" );
require_once( "../catalog/catalog.php" );

$operation = $_GET["operation"];
// print "operation = " . $operation . "<br/>";

switch ( $operation ) {
	case "save_legacy":
		// print "saving legacy deliveries<br/>";
		$ids_ = $_GET["ids"];
		$ids  = explode( ',', $ids_ );
//		var_dump($ids);
		save_legacy( $ids );
		break;

	case "create_ship":
		global $legacy_user; // From im-config.php
		// print "creating ship<br/>";
		$ids_ = $_GET["ids"];
		$ids  = explode( ',', $ids_ );
//		var_dump($ids);
		// TODO: select customer id.
		print invoice_create_ship( $legacy_user, $ids );
		break;
}

function invoice_create_ship( $customer_id, $order_ids ) {
	global $invoice_user;
	global $invoice_password;

//	print "start<br/>";
	$invoice = new Invoice4u( $invoice_user, $invoice_password );
//	var_dump($invoice);

	if ( is_null( $invoice->token ) ) {
		die ( "can't login to invoice4u" );
	}

	// print "customer id : " . $customer_id . "<br/>";

	$invoice_client_id = $invoice->GetInvoiceUserId( $customer_id );

	// print "invoice client id " . $invoice_client_id . "<br/>";

	$client = $invoice->GetCustomerById( $invoice_client_id );

	if ( ! ( $client->ID ) > 0 ) {
		print "Invoice client not found " . $customer_id . "<br>";

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

	$doc->ClientID     = $client->ID;
	$doc->DocumentType = DocumentType::InvoiceShip;

	// Set the subject
	$subject      = "משלוחים " . " " . comma_implode( $order_ids );
	$doc->Subject = $subject;

	// Add the deliveries
	$doc->Items = Array();

	$total_lines = 0;
	foreach ( $order_ids as $order_id ) {
		// print "del id " . $del_id;

		$o          = new Order( $order_id );
		$item       = new Item();
		$item->Name = order_get_excerpt( $order_id );
		if ( strlen( $item->Name ) < 2 ) {
			$item->Name = "משלוח עבור " . get_customer_name( order_get_customer_id( $order_id ) ) . " תאריך " . $o->OrderDate();
		}

		// TODO: display prices.
		$item->Price           = 32 * 1.17;
		$item->Quantity        = 1;
		$item->TaxPercentage   = 17;
		$item->TotalWithoutTax = 32;
		$item->Total           = round( $item->Price * $item->Quantity, 2 );
		array_push( $doc->Items, $item );
		$total_lines += $item->Total;

		order_change_status( $order_id, "wc-completed" );
	}

	// print "create<br/>";
	$doc_id = $invoice->CreateDocument( $doc );

	// var_dump($doc);
	return $doc_id;

}

function save_legacy( $ids ) {

	$pid = sql_query_single_scalar( "SELECT id FROM wp_posts WHERE post_title = 'משלוח בלבד'" );
	if ( ! $pid > 0 ) {
		$pid = Catalog::DoCreateProduct( "משלוח בלבד", 32 * 1.17, "עם האדמה" );
	}
	foreach ( $ids as $user_id ) {
		// print $id . "<br/>";
		$o = create_order( $user_id, 0, array( $pid ), array( 1 ),
			" משלוח המכולת " . date( 'Y-m-d' ) . " " . get_customer_name( $user_id ));
		// print "order: " . $o. "<br/>";
		order_change_status( $o, 'wc-processing' );
		// print "ex: " . sql_query_single_scalar("select post_excerpt from wp_posts where id = " . $id) . "<br/>";
	}
//	global $conn;
//	$sql    = "UPDATE im_delivery_legacy SET status = 2 WHERE status = 1";
//	$result = mysqli_query( $conn, $sql );
//	if ( ! $result ) {
//		print mysqli_error( $conn ) . " " . $sql;
//		die ( 1 );
//	}
//	$mission_id = sql_query_single_scalar( "SELECT max(id) FROM im_missions WHERE date <= curdate() AND name = \"סבב מרכז\"" );
//	foreach ( $ids as $id ) {
//		$sql = "INSERT INTO im_delivery_legacy (client_id, date, status, mission_id) " .
//		       " VALUES (" . $id . ", CURRENT_TIMESTAMP(), 1, $mission_id)";
//
//		$result = mysqli_query( $conn, $sql );
//		if ( ! $result ) {
//			print mysqli_error( $conn ) . " " . $sql;
//			die ( 1 );
//		}
//	}
}
