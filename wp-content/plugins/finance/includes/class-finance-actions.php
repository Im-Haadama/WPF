<?php


class Finance_Actions {
	function init_hooks(&$loader) {
		$loader->AddAction("account_add_trans", $this);
		$loader->AddAction("create_invoice_receipt", $this, 'create_invoice_receipt'); // חשבונית קבלה
		$loader->AddAction("create_receipt", $this, 'create_receipt'); // קבלה
		$loader->AddAction("create_invoice", $this, 'create_invoice'); // חשבונית
	}

	function account_add_trans()
	{
		$customer_id = $_GET["customer_id"];
		$amount      = $_GET["amount"];
		$date        = $_GET["date"];
		if (! strlen($date)) $date = date('Y-m-d');
		$ref         = $_GET["ref"];
		$type        = $_GET["type"];
		FinanceLog(__FUNCTION__.  "$customer_id $amount $date $ref $type");

		$u = new Finance_Client($customer_id);
		return $u->add_transaction($date, $amount, $ref, $type);
	}

	function create_receipt() {
		$cash    = (float) GetParam( "cash", false, 0 );
		$bank    = (float) GetParam( "bank", false, 0 );
		$check   = (float) GetParam( "check", false, 0 );
		$credit  = (float) GetParam( "credit", false, 0 );
		$row_ids = GetParamArray( "row_ids" );
		$user_id = GetParam( "user_id", true );
		$date    = GetParam( "date" );
		if (! strlen($date)) $date = date('Y-m-d');

		if ( ! ( $cash + $bank + $check + $credit > 1 ) ) {
			print ( "failed: No payment ammount given" );

			return false;
		}

		$doc_id = Finance_Client_Accounts::CreateReceipt( $user_id, $date, $cash, $bank, $check, $credit);
		// print "doc=$doc_id<br/>";
		print $doc_id;
		die ( 1 );
	}

	function create_invoice_receipt() {
		$cash    = (float) GetParam( "cash", false, 0 );
		$bank    = (float) GetParam( "bank", false, 0 );
		$check   = (float) GetParam( "check", false, 0 );
		$credit  = (float) GetParam( "credit", false, 0 );
		$row_ids = GetParamArray( "row_ids" );
		$user_id = GetParam( "user_id", true );
		$date    = GetParam( "date" );
		if (! strlen($date)) $date = date('Y-m-d');

		if ( ! ( $cash + $bank + $check + $credit > 1 ) ) {
			print ( "No payment ammount given" );

			return false;
		}

		$doc_id = Finance_Client_Accounts::create_receipt_from_account_ids( $cash, $bank, $check, $credit, $user_id, $date, $row_ids );
		// print "doc=$doc_id<br/>";
		print $doc_id;
		die ( 1 );
	}

	function create_invoice() {
		$row_ids = GetParamArray( "row_ids" );
		$user_id = GetParam( "user_id", true );
		$date    = GetParam( "date" );
		if (! strlen($date)) $date = date('Y-m-d');

		$doc_id = Finance_Client_Accounts::create_invoice_from_account_ids( $user_id, $date, $row_ids );
		// print "doc=$doc_id<br/>";
		print $doc_id;
		die ( 1 );
	}
}