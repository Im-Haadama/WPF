<?php

abstract class TransView {
	const
		default = 0,
		from_last_zero = 1,
		not_paid = 2,
		read_last = 3,
		admin = 4;
}

class Finance_Client_Views {
	function init_hooks($loader)
	{
		$loader->AddAction("get_client_open_account", $this);
	}

	static function show_trans( $customer_id = 0, $view = TransView::default, $args =null )
	{
		if (! $customer_id) $customer_id = get_user_id();
		// $from_last_zero = false, $checkbox = true, $top = 10000
		$query = GetArg($args, "param", null);

		// Show open deliveries
		$from_last_zero = false;
		$admin       = ($view == TransView::admin);

		$top            = null;
		$not_paid       = false;
		switch ( $view ) {
			case TransView::from_last_zero:
				$from_last_zero = true;
				break;
			case TransView::not_paid:
				$not_paid = true;
				break;

			case TransView::read_last:
				$top = 100;
				break;
		}
		$sql = 'select 
		id, 
		date,
		round(transaction_amount, 2) as transaction_amount,
		client_balance(client_id, date) as balance,
	    transaction_method,
	    transaction_ref, 
		order_from_delivery(transaction_ref) as order_id,
		delivery_receipt(transaction_ref) as receipt,
		id 
		from im_client_accounts 
		where client_id = ' . $customer_id;

		if ($not_paid)
			$sql .= " and transaction_method = 'משלוח'  and
          delivery_receipt(transaction_ref) is null and date > '2018-01-01'";

		if ($query) $sql .= " and " . $query;

		$sql .= ' order by date desc ';

		if ( $top ) $sql .= " limit " . $top;

		$args["class"] = "widefat";
		$args["links"] = array();
		$args["links"]["transaction_ref"] = Finance_Delivery::getLink('%d');

		// Todo: Finish step 2: "/delivery?id=%s";
		$args["col_ids"] = array("chk", "id", "dat", "amo", "bal", "des", "del", "ord");
		if (! $admin) unset ($args["col_ids"][0]);
		$args["add_checkbox"] = ($view == TransView::not_paid); // Checkbox will be added only to unpaid rows
		$args["post_file"] = Flavor::getPost();
		$first = true;

		$args["page_number"] = -1;// all rows
		$args["header_fields"] = array("transaction_amount" => "Transaction amount",
		                               "transaction_method" => "Operation",
		                               "transaction_ref" => "Reference number",
		                               "balance" => "Balance",
		                               "order_id" => "Order",
		                               "receipt" => "Invoice");


		$args["checkbox_class"] = "trans_checkbox";

		$data1 = Core_Data::TableData($sql, $args);

		if (! $data1) return ETranslate("No orders");

		if ($admin) foreach ($data1 as $id => $row)
		{
			$row_id = $row['id'];
			$value = "";
			if ($first) { $first = false; $value = "בחר";}
			else if ($data1[$id]['transaction_method'] == "משלוח" and ! $data1[$id]['receipt']) // Just unpaid deliveries
				$value =  Core_Html::GuiCheckbox("chk_" . $row_id, false, array("checkbox_class" => "trans_checkbox", "events" => "onchange=update_sum()"));

			array_unshift($data1[$id], $value);
		}
		return Core_Gem::GemArray($data1, $args, "trans_table");
	}

	static function admin_page() {
		$client_id    = GetParam( "id", false, null );
		$include_zero = GetParam( "include_zero", false, false );

		// Connect to invoice.
		Finance::Invoice4uConnect();
		if ( $client_id ) {
			print self::ClientAccount( $client_id );
		} else {
			print self::AllClients( $include_zero );
		}
	}

	static function ClientAccount( $customer_id ) {
		$result = "";
		try {
			$invoice = Finance::Invoice4uConnect();
		} catch ( Exception $e ) {
			$invoice = null;
		}
		$u         = new Finance_Client( $customer_id );
		$invoice_client_id = $u->getInvoiceUserId(true);

		$args = array( "class" => "widefat" );
		$user_info = Core_Html::gui_table_args( array(
			array( "name", $u->getName() ),
			array( "דואל", $u->get_customer_email() ),
			array( "טלפון", $u->get_phone_number() ),
			array( "מספר מזהה", Core_Html::gui_label( "invoice_client_id", $invoice_client_id ) ),
			array( "אמצעי תשלום",
				Finance_Payment_Methods::gui_select_payment( "payment", "onchange=\"save_payment_method('".Finance::getPostFile()."', $customer_id)\"", $u->get_payment_method() )
			)
		), "customer_details", $args );
		$style     = "table.payment_table { border-collapse: collapse; } " .
		             " table.payment_table, td.change, th.change { border: 1px solid black; } ";
		$args      = array( "style" => $style, "class" => "payment_table" );

		if ( $invoice ) {
			$new_tran = Core_Html::gui_table_args( array(
				array(
					"הפקת חשבונית מס קבלה",
					Core_Html::GuiButton( "btn_invoice_receipt", "Invoice Reciept", array( "action" => "create_invoice_receipt('" . Finance::getPostFile() . "', " . $customer_id . ")" ) )
				),
				array( "תאריך", Core_Html::gui_input_date( "pay_date", "" ) ),
				array( "מזומן", Core_Html::gui_input( "cash", "", array( 'onkeyup="update_sum()"' ) ) ),
				array( "אשראי", Core_Html::gui_input( "credit", "", array( 'onkeyup="update_sum()"' ) ) ),
				array( "העברה", Core_Html::gui_input( "bank", "", array( 'onkeyup="update_sum()"' ) ) ),
				array( "המחאה", Core_Html::gui_input( "check", "", array( 'onkeyup="update_sum()"' ) ) ),
				array( "עודף", " <div id=\"change\"></div>" )
			), "payment_table",
				$args );

			$new_invoice = Core_Html::gui_table_args( array(
				array(
					"יצירת חשבונית",
					Core_Html::GuiButton( "btn_invoice", "Invoice", array( "action" => "create_invoice('" . Finance::getPostFile() . "', " . $customer_id . ")" ) )
				),
				array( "תאריך", Core_Html::gui_input_date( "pay_date", "" ) )
			), "payment_table",
				$args );

			$new_receipt = Core_Html::gui_table_args( array(
				array(
					"הפקת קבלה",
					Core_Html::GuiButton( "btn_receipt", "Reciept", array( "action" => "create_receipt('" . Finance::getPostFile() . "', " . $customer_id . ")" ) )
				),
				array( "תאריך", Core_Html::gui_input_date( "receipt_pay_date", "" ) ),
				array( "מזומן", Core_Html::gui_input( "receipt_cash", "") ),
				array( "אשראי", Core_Html::gui_input( "receipt_credit", "" ) ),
				array( "העברה", Core_Html::gui_input( "receipt_bank", "" ) ),
				array( "המחאה", Core_Html::gui_input( "receipt_check", "" ) )
			), "payment_table",
				$args );

		} else {
			$new_tran = Core_Html::GuiHeader( 1, "לא ניתן להתחבר ל Invoice4u. בדוק את ההגדרות ואת המנוי. יוזר $" );
			$new_invoice = "";
		}
		$payment_info_id = SqlQuerySingleScalar( "select id from im_payment_info where email = " . QuoteText($u->get_customer_email()));
		if ($payment_info_id) {
			$args = array("post_file" => Flavor::getPost(), "edit"=>true,
			              "fields"=>array("id", "card_number", "exp_date_month", "exp_date_year", "id_number"),
			              "edit_cols"=> array("card_number"=>1, "card_type"=>1, "exp_date_month"=>1, "id_number"=>1, "exp_date_year"=>1));

			$credit_info = Core_Gem::GemElement( "payment_info", $payment_info_id, $args ) . Finance_Client_Accounts::TokenInfo($u);
		} else {
			$args["post_file"] = Finance::getPostFile();
			$args["values"] = array("email" => $u->get_customer_email(), "full_name"=>$u->getName(), "created_date"=>date('y-m-d'));
			$credit_info = Core_Gem::GemAddRow("payment_info", "Add", $args);
//			$credit_info = "No payment info";
		}

		$result .=  Core_Html::GuiHeader( 2, "פרטי לקוח", true );
		$result .= Core_Html::gui_table_args(array($user_info));

//		$result .= Core_Html::gui_table_args( array(
//			array(  Core_Html::GuiHeader( 2, "קבלה", true ), Core_Html::GuiHeader(1, "Credit info") ),
//			array( $user_info, $new_tran, $credit_info )
//		) );

		$result .= Core_Html::GuiHeader( 2, __( "Balance" ) . ": " .
		                                    SqlQuerySingleScalar( "SELECT round(sum(transaction_amount), 1) FROM im_client_accounts WHERE client_id = " . $customer_id ) );

		// Add to account existing documemnt
		$tabs = [array("exists_document", "Add Exists document", self::AddDocumentBox($u))];
		// Pay the balance with credit card.
		if ($pay = self::PaymentBox($u)) $tabs [] = array("Credit pay", "Credit pay", $pay);
		// Create receipt.
		$tabs[]  = ["invoice_receipt", "Create Invoice Receipt", $new_tran];

		$tabs[]  = ["receipt", "Create Receipt", $new_receipt];

		// Create invoice
		$tabs[]  = ["invoice", "Create Invoice", $new_invoice];
		// Edit Card info
		$tabs[]  = ["card", "Edit card info", $credit_info];


		$result .= Core_Html::GuiTabs("ca", $tabs,
			array("tabs_load_all"=>true));

		$result .= '<div id="logging"></div>';

		$result .= Finance_Client_Views::show_trans( $customer_id, TransView::admin, $args );

		if (class_exists('Finance_Yaad'))
			$result .= Finance_Yaad::History($customer_id);

		return $result;
	}

	static function AllClients( $include_zero = false ) {
		$output = "<center><h1>יתרת לקוחות לתשלום</h1></center>";
		if (! Finance::Invoice4uConnect()) {
			$output .= "תקלה במנוי. יש ליצור קשר עם תמיכה של invoice4u. " . Core_Html::GuiHyperlink("תמיכה", "http://messenger.providesupport.com/messenger/invoice4u.html");
		}

		$output .= Core_Html::GuiHyperlink( __( "include paid" ), AddToUrl( "include_zero", 1 ) ) . "<br/>";

		$sql = 'select round(sum(ia.transaction_amount),2), ia.client_id, wu.display_name, client_payment_method(ia.client_id), max(date) '
		       . ' from im_client_accounts ia'
		       . ' join wp_users wu'
		       . ' where wu.id=ia.client_id'
		       . ' group by client_id '
		       . ' order by 5 desc';

		$result = SqlQuery( $sql );

		$table_lines         = array();
		$table_lines_credits = array();

		$create_invoice_user = true;
		while ( $row = mysqli_fetch_row( $result ) ) {
			// $line = '';
			$customer_total = $row[0];
			if ( ($customer_total < 1) and ! $include_zero ) continue;

			$customer_id    = $row[1];
			$customer_name  = $row[2];
//			print $customer_name . " " . $customer_total . "<br/>";
			$C = new Finance_Client( $customer_id );

			$line = Core_Html::gui_cell( gui_checkbox( "chk_" . $customer_id, "user_chk" ) );
			$line .= "<td><a href = \"" . self::getLink( $customer_id ) . "\">" . $customer_name . "</a></td>";

			$line           .= "<td>" . $customer_total . "</td>";
			$payment_method = $C->get_payment_method();
//			$accountants = self::payment_get_accountants($payment_method);
			// print $accountants . " " . get_user_id() . "<br/>";
//			if (!strstr($accountants, (string) get_user_id())) continue;

			$line .= "<td>" . $row[4] . "</td>";
			$line .= "<td>" . Finance_Payment_Methods::get_payment_method_name( $payment_method ) . "</td>";
			$has_credit_info = self::getTokenStatus($C);
			$has_token = (get_user_meta($customer_id, 'credit_token', true) ? 'T' : '');
			$line .= "<td>" . $has_credit_info . " " . $has_token . "</td>";

			// Invoice User. Create one each load (for performance).
			$invoice_user_id = $C->getInvoiceUserId(false);
			if ( ! $invoice_user_id and $create_invoice_user) {
				try {
					$invoice_user_id = $C->createInvoiceUser();
				} catch (Exception$e) {
				}
				$create_invoice_user = false;
			}
			if ($invoice_user_id)
				$line .= "<td>" .  $invoice_user_id . "</td>";
			else
				$line .= "<td>not found</td>";
			array_push( $table_lines, array( - $customer_total, $line ) );
		}

		if ( count( $table_lines ) ) {
			$table = "<table class='sortable'>";
			$table .= "<tr>";
			$table .= Core_Html::gui_cell( "בחר" );

			$table .= "<td>לקוח</td>";
			$table .= "<td>יתרה לתשלום</td>";
			$table .= "<td>הזמנה אחרונה</td>";
			$table .= "<td>אמצעי תשלום</td>";
			$table .= "<td>פרטי אשראי</td>";
			$table .= "<td>invoice_user</td>";
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

		return $output;
	}

	static function AddDocumentBox(Finance_Client $u)
	{
		return Core_Html::gui_table_args( array(
				array( "סוג פעולה", "סכום", "תאריך", "מזהה" ),
				array(
					'<input type="text" id="transaction_type">',
					'<input type="text" id="transaction_amount">',
					'<input type="date" id="transaction_date">',
					'<input type="text" id="transaction_ref">'
				)
			) ) . '<button id="btn_add" onclick="account_add_transaction(\'' . Finance::getPostFile() . '\',' . $u->getUserId() . ')">הוסף תנועה</button>';
	}

	static function PaymentBox(Finance_Client $C )
	{
		$status = Finance_Yaad::getCustomerStatus($C, false);
		if (! $status) return null;

		$result = "<div>";
		$result .= Core_Html::GuiHeader(1, "בצע תשלום");
		$result .= "מספר תשלומים: " . Core_Html::GuiInput("payment_number", 1) ."<br/>";

		$result .= Core_Html::GuiButton("btn_pay", " בצע חיוב על היתרה", array("action"=>"pay_credit_client('" . Finance::getPostFile() . "', " . $C->getUserId() . ")"));
		$result .= "</div>";

		return $result;
	}

	static function getLink($client_id) {
		return "/wp-admin/users.php?page=client-accounts&id=$client_id";
	}

	static function getTokenStatus($C)
	{
		if (class_exists('Finance_Yaad'))
			return Finance_Yaad::getCustomerStatus($C, true);
		return '';
	}

}