<?php


/**
 * Class Finance_Clients
 */
class Finance_Client_Accounts
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

	public function init_hooks($loader)
	{
		$loader->AddAction("get_client_open_account", $this);
		$loader->AddAction("get_balance_email", $this);
	}

	function get_balance_email()
	{
		$date = GetParam("date");
		$email = GetParam("email");

		$user = Finance_Client::getUserByEmail($email);
		if (! $user) { print "user not found"; }
		print $user->balance($date);
	}
	function get_client_open_account()
	{
		if (! TableExists("client_accounts")) return "";
		if (! $this->multisite) {
			print "failed: multisite not configured";

			return false;
		}

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
		Finance_Business_Logic::Invoice4uConnect();
		FinanceLog(__FUNCTION__ . "cash: $cash bank: $bank check: $check credit $credit user $user_id date $date rows: " . CommaImplode($row_ids));
		if ( ! $date ) $date = date( 'Y-m-d' );

		if ( ! ( $user_id > 0 ) ) throw  new Exception( "Bad customer id " . __CLASS__ );

		$deliveries_total = 0;
		$del_ids = self::check_deliveries($row_ids, $deliveries_total);
		$change = ($cash + $bank + $check + $credit) - $deliveries_total;

		if ( ! $del_ids or ! count($del_ids) ) {
			print "לא נבחרו תעודות משלוח";
			FinanceLog("no del ids");

			return false;
		}
		return self::CreateInvoiceReceipt($cash, $bank, $check, $credit, $change, $user_id, $date, $del_ids);
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

		$doc_id = self::CreateInvoice($user_id, $date, $del_ids);
		$sql = "UPDATE im_delivery SET payment_receipt = " . $doc_id . " WHERE id IN (" . CommaImplode( $del_ids ) . " ) ";
		FinanceLog(__FUNCTION__ . $sql);
		SqlQuery( $sql );
		return $doc_id;
	}

	static function CreateInvoiceReceipt($cash, $bank, $check, $credit, $change, $user_id, $date, array $del_ids, $subject = null)
	{
		$u = new Finance_Client( $user_id );
		$c = $cash - $change;

		// Check if paid (some bug cause double invoice).
		$sql = "SELECT count(payment_receipt) FROM im_delivery WHERE id IN (" . CommaImplode( $del_ids ) . " )";
		if ( SqlQuerySingleScalar( $sql ) > 0 ) {
			print " כבר שולם" . CommaImplode( $del_ids ) . " <br/>";

			return false;
		}

		$doc_id   = self::CreateInvoiceDocument( "r", $del_ids, $user_id, $date, $c, $bank, $credit, $check, $subject );

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
		Finance_Business_Logic::Invoice4uConnect();

		$u = new Finance_Client( $user_id );

		// Check if paid (some bug cause double invoice).
		$sql = "SELECT count(payment_receipt) FROM im_delivery WHERE id IN (" . CommaImplode( $del_ids ) . " )";
		if ( SqlQuerySingleScalar( $sql ) > 0 ) {
			print " כבר שולם" . CommaImplode( $del_ids ) . " <br/>";

			return false;
		}

		$doc_id = self::CreateInvoiceDocument( "i", $del_ids, $user_id, $date );

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

	static function CreateInvoiceDocument( $type, $ids, $customer_id, $date, $cash = 0, $bank = 0, $credit = 0, $check = 0, $subject = null )
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

		$client = $C->getInvoiceUser();

		if ( !$client or (isset($client->ID) and ! ( $client->ID )) > 0 ) {
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
				$doc->IssueDate = date("c", strtotime($date));
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

		$doc_id =  $invoice->CreateInvoiceDocument( $doc );

		// var_dump($doc);
		return $doc_id;
	}

	static function CreateReceipt( $customer_id, $date, $cash = 0, $bank = 0, $credit = 0, $check = 0, $subject = null )
	{
//		print "cus=$customer_id date=$date cash=$cash bank=$bank credit=$credit check=$check subject=$subject<br/>";
//		$db_prefix = GetTablePrefix("delivery_lines");
		if ( ! ( $customer_id > 0 ) )
			throw new Exception( "Bad customer id" . __CLASS__);

//		$invoice = Finance_Invoice4u::getInstance();
		$invoice = Finance_Business_Logic::Invoice4uConnect();

		if (! $invoice) {
			print "No connection to invoice. Connect first";
			return false;
		}

		$u = new Finance_Client($customer_id);
		$invoice->Login();

		$client = $u->getInvoiceUser(true);

		if ( !$client or  ! ( $client->ID ) > 0 ) {
			print "Client not found " . $customer_id . "<br>";

			return 0;
		}
		$email = $client->Email;
		$doc = new ReceiptDocument($client->ID);

		$iEmail                = new InvoiceEmail();
		$iEmail->Mail          = $email;
		$doc->AssociatedEmails = Array( $iEmail );
		//var_dump($client->ID);

		$doc->DocumentType = InvoiceDocumentType::Receipt;

		// Set the subject
		if ( ! $subject ) {
			$subject = "תשלום";
		}
		$doc->Subject = $subject;

		// Add the deliveries
		$doc->Items = Array();

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

		$doc_id =  $invoice->CreateReceipt( $doc );

		if ( is_numeric( $doc_id ) && $doc_id > 0 ) {
			$pay_description = self::pay_type( $cash, $bank, $credit, $check );

			$u->add_transaction( $date, - ( $cash + $bank + $credit + $check ), $doc_id, $pay_description );
		}

		FinanceLog(__FUNCTION__ . " $customer_id, $date, $cash, $bank, $credit, $check $subject" );
		return $doc_id;
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

	static function TokenInfo(Finance_Client $client)
	{
		$status = get_user_meta($client->getUserId(), 'credit_token', true);

		$result = "";
		$post_file = WPF_Flavor::getPost( "credit_clear_token&id=" . $client->getUserId());

		if ($status) $result .= "Has token." . Core_Html::GuiButton("btn_clear_token", "Clear", "execute_url('$post_file', location_reload)");

		return Core_Html::GuiDiv("token_info", $result);
	}
}
