<?php


/**
 * Class Finance_Clients
 */
class Finance_Clients
{
	private $multisite;

	/**
	 * Finance_Clients constructor.
	 *
	 * @param $multisite
	 */
	public function __construct( ) {
		if (class_exists("Core_Db_MultiSite"))
			$this->multisite = Core_Db_MultiSite::getInstance();
		else {
			if ( get_user_id() == 1 ) {
				print "multisite not found";
			}

			$this->multisite = null;
		}
	}

	public function init_hooks()
	{
		AddAction("get_client_open_account", array($this, 'get_client_open_account'));
		AddAction("account_add_trans", array($this, 'add_trans'));
	}

	function add_trans()
	{
		$customer_id = $_GET["customer_id"];
		$amount      = $_GET["amount"];
		$date        = $_GET["date"];
		$ref         = $_GET["ref"];
		$type        = $_GET["type"];

		$u = new Finance_Client($customer_id);
		return $u->add_transaction($date, $amount, $ref, $type);

//		account_add_transaction( $customer_id, $date, $amount, $ref, $type );
	}
	function get_client_open_account()
	{
		if (! TableExists("client_accounts")) return "";
		if (! $this->multisite)
			return "multisite not configured";
		$sql = "select " . $this->multisite->LocalSiteId() . ", client_id, client_displayname(client_id), round(sum(transaction_amount),2) as total\n"
		       . "from im_client_accounts\n"
		       . "group by 2\n"
		       . "having total > 1";

		$data   = "<table>";
		$result = SqlQuery( $sql );
		while ( $row = SqlFetchRow( $result ) ) {
			$data .= Core_Html::gui_row( $row );
		}
		$data .= "</table>";
		print $data;
	}

	static function admin_page() {
		$client_id    = GetParam( "id", false, null );
		$include_zero = GetParam( "include_zero", false, false );

		// Connect to invoice.
		Finance::Invoice4uConnect();
		if ( $client_id ) {
			print self::client_account( $client_id );
		} else {
			print self::all_clients( $include_zero );
		}
	}

	static function all_clients( $include_zero = false ) {
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

	static function getTokenStatus($C)
	{
		if (class_exists('Finance_Yaad'))
		return Finance_Yaad::getCustomerStatus($C, true);
		return '';
	}

	static function client_account( $customer_id ) {
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
					Core_Html::GuiButton( "btn_receipt", "Invoice Reciept", array( "action" => "create_receipt('" . Finance::getPostFile() . "', " . $customer_id . ")" ) )
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
		} else {
			$new_tran = Core_Html::GuiHeader( 1, "לא ניתן להתחבר ל Invoice4u. בדוק את ההגדרות ואת המנוי. יוזר $" );
			$new_invoice = "";
		}
		$payment_info_id = SqlQuerySingleScalar( "select id from im_payment_info where email = " . QuoteText($u->get_customer_email()));
		if ($payment_info_id) {
			$args = array("post_file" => Flavor::getPost(), "edit"=>true,
			              "fields"=>array("id", "card_number", "exp_date_month", "exp_date_year", "id_number"),
			              "edit_cols"=> array("card_number"=>1, "card_type"=>1, "exp_date_month"=>1, "id_number"=>1, "exp_date_year"=>1));

			$credit_info = Core_Gem::GemElement( "payment_info", $payment_info_id, $args ) . self::TokenInfo($u);
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
		$tabs[]  = ["receipt", "Create Receipt", $new_tran];
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
	static function create_receipt_from_account_ids( $cash, $bank, $check, $credit, $user_id, $date, $row_ids )
	{
		FinanceLog(__FUNCTION__ . "cash: $cash bank: $bank check: $check credit $credit user $user_id date $date rows: " . StringVar($row_ids));
		if ( ! $date ) $date = date( 'Y-m-d' );

		if ( ! ( $user_id > 0 ) ) throw  new Exception( "Bad customer id " . __CLASS__ );

		$deliveries_total = 0;
		$del_ids = self::check_deliveries($row_ids, $deliveries_total);
		$change = ($cash + $bank + $check + $credit) - $deliveries_total;

		if ( ! $del_ids or ! count($del_ids) ) {
			print "לא נבחרו תעודות משלוח";
			MyLog("no del ids");

			return false;
		}
		return self::CreateReceipt($cash, $bank, $check, $credit, $change, $user_id, $date, $del_ids);
	}

	static function check_deliveries($row_ids, &$deliveries_total) {
		$del_ids = [];
		foreach ( $row_ids as $id ) {
			if ( $id > 0 ) {
				$del_id           = SqlQuerySingleScalar( "select transaction_ref from im_client_accounts where ID = " . $id );
				$d                = new Finance_Delivery( 0, $del_id );
				$deliveries_total += $d->getDeliveryTotal();
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
		return $del_ids;
	}

	static function create_invoice_from_account_ids( $user_id, $date, $row_ids )
	{
		FinanceLog(__FUNCTION__, "user $user_id date $date rows: " .
		                         StringVar($row_ids));

		if ( ! $date ) $date = date( 'Y-m-d' );

		if ( ! ( $user_id > 0 ) ) throw  new Exception( "Bad customer id " . __CLASS__ );

		$total = 0;
		if (! ($del_ids = self::check_deliveries($row_ids, $total))) return false;

		return self::CreateInvoice($user_id, $date, $del_ids);
	}

	private static function CreateReceipt($cash, $bank, $check, $credit, $change, $user_id, $date, $del_ids)
	{
		Finance::Invoice4uConnect();

		$u = new Finance_Client( $user_id );
		$c = $cash - $change;

		// Check if paid (some bug cause double invoice).
		$sql = "SELECT count(payment_receipt) FROM im_delivery WHERE id IN (" . CommaImplode( $del_ids ) . " )";
		if ( SqlQuerySingleScalar( $sql ) > 0 ) {
			print " כבר שולם" . CommaImplode( $del_ids ) . " <br/>";

			return false;
		}

		$doc_id   = self::CreateDocument( "r", $del_ids, $user_id, $date, $c, $bank, $credit, $check );

		$pay_type = self::pay_type( $cash, $bank, $credit, $check );
		if ( is_numeric( $doc_id ) && $doc_id > 0 ) {
			$pay_description = $pay_type . " " . CommaImplode( $del_ids );
			$sql = "UPDATE im_delivery SET payment_receipt = " . $doc_id . " WHERE id IN (" . CommaImplode( $del_ids ) . " ) ";
			SqlQuery( $sql );
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

	private static function CreateInvoice($user_id, $date, $del_ids)
	{
		Finance::Invoice4uConnect();

		$u = new Finance_Client( $user_id );

		// Check if paid (some bug cause double invoice).
		$sql = "SELECT count(payment_receipt) FROM im_delivery WHERE id IN (" . CommaImplode( $del_ids ) . " )";
		if ( SqlQuerySingleScalar( $sql ) > 0 ) {
			print " כבר שולם" . CommaImplode( $del_ids ) . " <br/>";

			return false;
		}

		$doc_id = self::CreateDocument( "i", $del_ids, $user_id, $date );

		if ( is_numeric( $doc_id ) && $doc_id > 0 ) {
			$sql = "UPDATE im_delivery SET payment_receipt = " . $doc_id . " WHERE id IN (" . CommaImplode( $del_ids ) . " ) ";
			print $sql;
			SqlQuery( $sql );
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

	static function CreateDocument( $type, $ids, $customer_id, $date, $cash = 0, $bank = 0, $credit = 0, $check = 0, $subject = null )
	{
		$db_prefix = GetTablePrefix("delivery_lines");
		if ( ! ( $customer_id > 0 ) )
			throw new Exception( "Bad customer id" . __CLASS__);

		$invoice = Finance_Invoice4u::getInstance();
		if (! $invoice) {
			print "No connection to invoice. Connect first";
			return false;
		}

		$C = new Finance_Client($customer_id);
		$invoice->Login();

		$client = $C->getInvoiceUser(true);

		if ( !$client or  ! ( $client->ID ) > 0 ) {
			print "Client not found " . $customer_id . "<br>";

			return 0;
		}
		$email = $client->Email;
		// print "user mail: " . $email . "<br/>";
		$doc = new InvoiceDocument();

		$iEmail                = new InvoiceEmail();
		$iEmail->Mail          = $email;
		$doc->AssociatedEmails = Array( $iEmail );
		//var_dump($client->ID);

		$doc->ClientID = $client->ID;
		switch ( $type ) {
			case "r":
				$doc->DocumentType = InvoiceDocumentType::InvoiceReceipt;
				break;
			case "i":
				$doc->DocumentType = InvoiceDocumentType::Invoice;
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
			       . " from ${db_prefix}delivery_lines where delivery_id = $del_id";

			$result = SqlQuery( $sql );

			// drill to lines
			while ( $row = mysqli_fetch_row( $result ) ) {
				if ( $row[4] != 0 ) {
					$item           = new InvoiceItem();
					$item->Name     = $row[0];
					$item->Price    = round( $row[3], 2 );
					$item->Quantity = round( $row[1], 2 );
					if ( $row[2] > 0 ) {
						$item->TaxPercentage   = Israel_Shop::getVatPercent();
						$item->TotalWithoutTax = Israel_Shop::totalWithoutVat($row[4]);
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
				$pay         = new InvoicePaymentCash();
				$pay->Amount = $cash;
				array_push( $doc->Payments, $pay );
			}
			if ( $bank > 0 ) {
				$pay         = new InvoicePaymentBank();
				$pay->Amount = $bank;
				$pay->Date   = $date;
				array_push( $doc->Payments, $pay );
			}
			if ( $credit > 0 ) {
				$pay         = new InvoicePaymentCredit();
				$pay->Amount = $credit;
				array_push( $doc->Payments, $pay );
			}
			if ( $check > 0 ) {
				$pay         = new InvoicePaymentCheck();
				$pay->Amount = $check;
				array_push( $doc->Payments, $pay );
			}

			$doc->Total = $credit + $bank + $cash + $check;
			$doc->ToRoundAmount = false;
		}

		$doc_id =  $invoice->CreateDocument( $doc );

		// var_dump($doc);
		return $doc_id;
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

	static function payment_get_accountants($payment_id)
	{
		return SqlQuerySingleScalar( "select accountants from im_payments where id = " . $payment_id);
	}

	static function install()
	{
		return;
		$db_prefix = GetTablePrefix();
		if (! TableExists("client_accounts"))
			SqlQuery("create table ${db_prefix}client_accounts
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

	static function TokenInfo(Finance_Client $client)
	{
		$status = get_user_meta($client->getUserId(), 'credit_token', true);

		$result = "";
		$post_file = Flavor::getPost("credit_clear_token&id=" . $client->getUserId());

		if ($status) $result .= "Has token." . Core_Html::GuiButton("btn_clear_token", "Clear", "execute_url('$post_file', location_reload)");

		return Core_Html::GuiDiv("token_info", $result);
	}
}
