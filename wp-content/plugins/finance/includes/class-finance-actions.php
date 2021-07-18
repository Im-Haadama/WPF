<?php


class Finance_Actions {
	function init_hooks($loader) {
		$loader->AddAction("account_add_trans", $this);
		$loader->AddAction("create_invoice_receipt", $this, 'create_invoice_receipt'); // חשבונית קבלה
		$loader->AddAction("create_receipt", $this, 'create_receipt'); // קבלה
		$loader->AddAction("create_invoice", $this, 'create_invoice'); // חשבונית
		$loader->AddAction( "bank_create_pay", $this );
		$loader->AddAction("finance_add_payment", $this);
		$loader->AddAction("delivery_send_mail", $this);
		$loader->AddAction("finance_check_card", $this);
		$loader->AddAction("check_progress", $this);
//		$loader->AddAction("add_test", $this);
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

	public function bank_create_pay()
	{
		$id = GetParam( "id" );
		print Core_Html::GuiHeader( 1, "רישום העברה שבוצעה " );

		$b = Finance_Bank_Transaction::createFromDB( $id );
		print Core_Html::GuiHeader( 2, "פרטי העברה" );
		$free_amount = $b->getOutAmount( true );
		$client_name = $b->getClientName();
		print Core_Html::gui_table_args( array(
			array( "תאריך", Core_Html::gui_div( "pay_date", $b->getDate() ) ),
			array( "סכום", Core_Html::gui_div( "bank", $b->getOutAmount() ) ),
			array( "סכום לתיאום", Core_Html::gui_div( "bank", $free_amount ) ),
			array( "מזהה", Core_Html::gui_div( "bank_id", $id )),
			array( "Comment", $client_name	)
		));

		$lines = $b->getAttached();
		if ( $lines ) {
			print Core_Html::GuiHeader( 2, "שורות מתואמות" );

			print Core_Html::gui_table_args( $lines );
		}
		$sums = array();
		if ( $free_amount > 0 ) {
//				print "a=" . $amount . "<br/>";
			print Core_Html::GuiHeader( 2, "Select Supplier" );
			print Finance_Bank::gui_select_open_supplier();
		}
		print '<div id="logging"></div>';
		print '<div id="transactions"></div>';

		$table = array();
		$row[0] = "קשר";
		$row[1] = Core_Html::GuiButton( "btn_receipt", "To invoices", array("action" => "link_invoice_bank(1)"));
		$row[2] = "<div id=\"total\"></div>";
		array_push($table,$row);
//		array(
//			array("קשר",
//				,
//					array( "סה\"כ", " <div id=\"total\"></div>" )), "payment_table")
//			.
//		$table[0]=
		print Core_Html::GuiDiv("link",
			Core_Html::gui_table_args($table) .
		Core_Html::GuiButton( "btn_link", "To account", array("action" => "link_invoice_bank(0)")),
			array("style" => "display:none"));

	}

	function finance_add_payment()
	{
		$supplier_id = GetParam( "supplier_id", true );
		$bank_id     = GetParam( "bank_id", true );
		$ids         = GetParamArray( "ids" );
		$date        = GetParam( "date", true );
		$amount      = GetParam( "amount", true );
		$sql         = "INSERT INTO im_business_info (part_id, date, amount, ref, document_type)\n" .
		               "VALUES(" . $supplier_id . ", '" . $date . "' ," . $amount . ", " . $bank_id . ", " . Finance_DocumentType::bank . ")";
		SqlQuery( $sql );

		$S = new Finance_Supplier($supplier_id);
		$result = "התווסף תשלום בסך " . $amount . " לספק " . $S->getSupplierName() . "<br/>";

		$sql = "update im_business_info\n" .
		       "set pay_date = '" . $date . "'\n" .
		       "where id in (" . CommaImplode( $ids ) . ")";

		SqlQuery( $sql );
		$result .= "מסמכים מספר  " . CommaImplode( $ids ) . " סומנו כמשולמים<br/>";
		return $result;
	}

	static function delivery_send_mail_wrap()
	{
		$id = GetParam("delivery_id");
		return self::delivery_send_mail($id);
	}

	static function delivery_send_mail($id)
	{

//		print "info: " . $info_email;
//		print "track: " . $track_email;

//		$option = $delivery->getPrintDeliveryOption();

		$track_email = get_option('admin_email');
//		if ( strstr( $option, 'M' ) ) {
//		$id = GetParam("id", true);
		$delivery = new Finance_Delivery(0, $id);
		return $delivery->send_mail( $track_email);
	}

	function finance_check_card()
	{
		$user_id = GetParam("user_id", true);
		$bl = new Finance_Business_Logic();
		$args = array("check_only"=>true,
			"amount"=>10,
			"payment_number"=>1,
			"user"=>$user_id);
		$bl->pay_user_credit_wrap($args);
	}

	function check_progress()
	{
		$key = __FUNCTION__ . "progress";
//		$prob = GetParam("prob");
//		if ($prob)
//		{
//			print InfoGet($key);
//			return;
//		}
		InfoUpdate($key, "started");
		$total = 10;
		for ($i = 0; $i < $total; $i++ ) {
			InfoUpdate($key, (100 * ($i / $total)) . " percent");
			sleep(1);
		}
		InfoUpdate($key, "done");

	}

	function add_test()
	{
		foreach (array(2103, 341) as $exist_order_id) {
			$order     = new Finance_Order( $exist_order_id );

			// Duplcate the order
			$new_id    = $order->duplicate();
			$new_order = new Finance_Order( $new_id );

			// Change to processing status
			$new_order->update_status( 'wc-processing' );

			// Create delivery.
			$new_del = new Finance_Delivery($new_id);
			$new_del->CreateDeliveryFromOrder($new_id, 1);
			$new_order->update_status("wc-completed");
		}
	}
}