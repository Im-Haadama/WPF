<?php
/*
 * This class responsible for finance business logic.
 * E.g: Buying: being triggered from the payment gateway, conducting the process: Payment, Creating invoice (after creating the delivery note).
 */

class Finance_Business_Logic {
	private ?Finance_Yaad $paying;
	private ?string $error_message;

	/**
	 * @return string|null
	 */
	public function getErrorMessage(): ?string {
		return $this->error_message;
	}
	/**
	 * @var mixed
	 */
	private $transaction_id;

	/**
	 * @return mixed
	 */
	public function getTransactionId() {
		return $this->transaction_id;
	}

	/**
	 * Finance_Business_Logic constructor.
	 *
	 * @param Finance_Paying|null $paying
	 * @param string|null $error_message
	 */
	public function __construct( ?Finance_Yaad $paying = null) {
		if ($paying)
			$this->paying        = $paying;
		else {
			$this->paying = null;
			self::Paying_Connect();
		}

		$this->error_message = '';
	}

	public function init_hooks($loader)
	{
		$loader->AddAction( 'pay_credit', $this );
		$loader->AddAction( 'pay_user_credit', $this);
		$loader->AddAction('credit_clear_token', $this);


	}
	public function process_payment( $order_id, $args ) : bool {
		$order              = new WC_Order( $order_id );

		$this->Paying_Connect();
		$pay_on_checkout = GetArg($args, "pay_on_checkout", false);

		FinanceLog(__FUNCTION__ . " poc=$pay_on_checkout");
		if (false)
		{
			if (! $this->paying) {
				$this->error_message = "Payment system not connected";
				return false;
			}

			$credit_info = array("card_number" => str_replace("-", "", $_REQUEST["billing_creditcard"]),
			                     "exp_date_month" => $_REQUEST["billing_expdatemonth"],
			                     "exp_date_year" => $_REQUEST["billing_expdateyear"],
			                     "id_number"=>$_REQUEST["billing_idnumber"]
			);
			$passed = $this->pay_order($order_id, $credit_info);
			if (! $passed) {
				return false;
			}
			FinanceLog("Paid. " .$this->error_message);
			$save_as_token = (isset($_REQUEST["save_as_token"]) and "1" == $_REQUEST["save_as_token"]);
			if ($save_as_token) {
				$token_info = $this->GetToken();
				if ( isset( $token_info['Token'] ) ) {
					$user_id = $order->get_user_id();
					FinanceLog( "Saving token for user " . $user_id );
					delete_user_meta($user_id, 'credit_token');
					add_user_meta( $user_id, 'credit_token', $token_info['Token'] );
				}
			}
			return true;
		}

		// Store the data for later payment.
		$billing_creditcard = $_REQUEST['billing_creditcard'];
		$card_number        = str_replace( "-", "", $billing_creditcard );
		$card_type          = $_REQUEST['billing_cardtype'];
		$expdate_year       = $_REQUEST['billing_expdateyear'];
		$expdate_month      = $_REQUEST['billing_expdatemonth'];
		$billing_idnumber   = $_REQUEST['billing_idnumber'];

		if ( isset( $card_number ) && ! empty( $card_number ) ) {
			update_post_meta( $order_id, 'card_number', $card_number );
		}
		if ( isset( $billing_idnumber ) && ! empty( $billing_idnumber ) ) {
			update_post_meta( $order_id, 'id_number', $billing_idnumber );
		}
		if ( isset( $card_type ) && ! empty( $card_type ) ) {
			update_post_meta( $order_id, 'card_type', $card_type );
		}
		if ( isset( $expdate_month ) && ! empty( $expdate_month ) ) {
			update_post_meta( $order_id, 'expdate_month', $expdate_month );
		}
		if ( isset( $expdate_year ) && ! empty( $expdate_year ) ) {
			update_post_meta( $order_id, 'expdate_year', $expdate_year );
		}
		FinanceLog(__FUNCTION__ . $pay_on_checkout);
		return true;
	}

	function Paying_Connect() :bool
	{
		if (! $this->paying) {
			if ( ! defined( 'YAAD_API_KEY' ) ) {
				$this->error_message = "api key not defined";

				return false;
			}
			if ( ! defined( 'YAAD_TERMINAL' ) ) {
				$this->error_message = "terminal not defined";

				return false;
			}
			if ( ! defined( 'YAAD_PassP' ) ) {
				$this->error_message = 'PassP not defined';

				return false;
			}
			$this->paying = new Finance_Yaad( YAAD_API_KEY, YAAD_TERMINAL, get_bloginfo( 'name' ), YAAD_PassP );
			if ($this->error_message = $this->paying->getMessage())
				return false;
		}
		return true;
	}

	function pay_credit() {
		FinanceLog(__FUNCTION__);
		$users = explode( ",", GetParam( "users", true, true ) );
		$payment_number = GetParam("number", false, 1);
		$amount = GetParam("amount", false, 0);

		foreach ( $users as $user ) {
			FinanceLog("trying $user");
			$args = ["user"=>$user,
			         "amount"=>$amount,
			         "payment_number"=>$payment_number];
			Core_Hook_Handler::instance()->DoAction( 'pay_user_credit', $args );
		}

		return true;
	}

	function pay_order($order_id, $credit_info)
	{
		$debug = true;

		FinanceLog(__FUNCTION__ . " $order_id");

		// A) Connect to Paying (Yaad).
		if (! self::Paying_Connect()) {
			FinanceLog($this->error_message);
			$this->error_message = __("Payment system not available. Please contact customer support");
			$this->add_admin_notice($this->error_message);
			return false;
		}
		if ($debug) print "Connected to Yaad.<br/>";

		$inv = self::Invoice4uConnect();

		// B) Connect to Invoice4u
		if (! $inv or ! $inv->Login()){
			$this->error_message = "Invoice system not available.";
			return false;
		}
		if ($debug) print "Connected to invoice4u<br/>";

		// C) Get paid
		$O = new Finance_Order($order_id);
		$user = new Finance_Client($O->getCustomerId());
		$amount = $O->getTotal() + $O->getShippingFee();

		if ($debug) print "Going to get $amount from " . $user->getName() . "<br/>";
		$transaction_info = $this->paying->CreditPay($credit_info, $user->getName(), $user->getUserId(), $amount, "Order $order_id", 1);
		if (($transaction_info['CCode'] != 0)) {
			$this->error_message = ETranslate("Got error ") . $this->paying->ErrorMessage($transaction_info['CCode']);
			FinanceLog("Error: " . $this->error_message);
			return false;
		}
		$this->transaction_id = $transaction_info['Id'];
		if ($debug) print "PAID<br/>";
//		$this->yaad->pay_user_credit($user, $credit_info, $order_id, $amount);
//		$this->message = $this->yaad->getMessage();

		// D) Create devlivery note and invoice receipt
		$Del = new Finance_Delivery($order_id);
		$del_id = $Del->CreateDeliveryFromOrder($order_id, 1);
		if ($debug) print "Delivery number $del_id<br/>";

		FinanceLog(__FUNCTION__ . " user = " . $user->getUserId() . " amount = $amount " . $user->getUserId(). " del=$del_id");

		if ($debug) print "Going to create invoice receipt<br/>";
		$doc_id = Finance_Client_Accounts::CreateInvoiceReceipt(0, 0, 0, $amount, 0, $O->getCustomerId(), date('Y-m-d'), array($del_id), "הזמנה " . $order_id);

		$this->error_message = ETranslate("Invoice" ) . " $doc_id " . ETranslate("created");
		if ($debug) print $this->error_message;
		return true;
	}

	static function Invoice4uConnect() : ?Finance_Invoice4u
	{
		if ($i = Finance_Invoice4u::getInstance())
			return $i;

		if (defined('INVOICE_USER') and defined('INVOICE_PASSWORD'))
			return new Finance_Invoice4u(INVOICE_USER, INVOICE_PASSWORD);

		else FinanceLog("No invoice user or password");

		return null;
	}

	function GetToken()
	{
		return $this->paying->GetToken($this->transaction_id);
	}

	function pay_user_credit_wrap($args)
	{
		$customer_id = GetArg($args, "user");
		if (null == $customer_id) {
			print "Failed: no customer id";
			die (1);
		}
		$amount = GetArg($args, "amount");
		$payment_number = GetArg($args, "payment_number");

		FinanceLog(__FUNCTION__ . " $customer_id");

		if (! $this->Paying_Connect()) return false;

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
		where client_id = ' . $customer_id . '
		and delivery_receipt(transaction_ref) is null
		and transaction_method = "משלוח"
		order by date asc
		';

		// If amount not specified, try to pay the balance.
		$user = new Finance_Client($customer_id);

		if ($amount == 0)
			$amount = $user->balance();

		$rows = SqlQueryArray($sql);
		$current_total = 0;

		$paying_transactions = [];
		foreach ($rows as $row) {
			$trans_amount = $row[2];
			if (($trans_amount + $current_total) < ($amount + 15)) {
				array_push($paying_transactions, $row[0]);
				$current_total += $trans_amount;
			}
		}

		$change = $amount - $current_total;

		$credit_data = SqlQuerySingleAssoc("select * from im_payment_info where user_id = $customer_id");
		if (! $credit_data) {
			print "failed: no credit info for user $customer_id";
			return false;
		}

		return $this->pay_user_credit($user, $credit_data, $paying_transactions, $amount, $change, $payment_number);
//		foreach ($delivery_ids as $delivery_id) {
//			$this->pay_user_credit( $user_id, $delivery_id );
//			die(0);
//		}
	}

	function pay_user_credit(Finance_Client $user, $credit_data, $account_line_ids, $amount, $change = 0, $payment_number = 1)
	{
//		print __FUNCTION__;
		FinanceLog(__FUNCTION__ . " " . $user->getName() . " " . $amount);
		$debug = false;

		if (0 == $amount)
			return true;

		$token = get_user_meta($user->getUserId(), 'credit_token', true);

		if ($token) {
			FinanceLog("trying to pay with token user " . $user->getName());
			$transaction_info = $this->paying->TokenPay( $token, $credit_data, $user->getName(), $user->getUserId(), $amount, CommaImplode($account_line_ids), $payment_number );
			$transaction_id = $transaction_info['Id'];
			if (! $transaction_id or ($transaction_info['CCode'] != 0)) {
				$message = "Failed: " . $user->getName() . ": Got error " . $this->paying->ErrorMessage($transaction_info['CCode']) . "\n";
				print $message;
				FinanceLog($message);
				return false;
			}

			$paid = $transaction_info['Amount'];
			FinanceLog("paid $paid user " . $user->getName());
			if ($paid) E_Fresh_Payment_Gateway::RemoveRawInfo($credit_data['id']);
		} else {
			FinanceLog("trying to pay with credit info " . $user->getName());
			if ($debug) print "trying to pay with credit info<br/>";
			// First pay. Use local store credit info, create token and delete local info.
			$transaction_info = $this->paying->CreditPay($credit_data, $user->getName(), $user->getUserId(), $amount, CommaImplode($account_line_ids), $payment_number);
			FinanceLog("back");
			// info: Id, CCode, Amount, ACode, Fild1, Fild2, Fild3
			if (($transaction_info['CCode'] != 0)) {
				FinanceLog(__FUNCTION__, $transaction_info['CCode']);
				$this->message .= "Got error " . self::ErrorMessage($transaction_info['CCode']) . "\n";
				if ($debug) var_dump($credit_data);
				return false;
			}
			if ($debug) var_dump($transaction_info);
			$transaction_id = $transaction_info['Id'];
			if (! $transaction_id){
				print "No transaction id";
				return false;
			}
			$paid = $transaction_info['Amount'];
			if ($transaction_id) {
				print $user->getName() . " " . __("Paid") . " $amount. $payment_number " . __("payments") . "\n";
				if ($debug) print "pay successful $transaction_id<br/>";

				// Create token and save it.
				$token_info = self::GetToken( $transaction_id );
				if (isset($token_info['Token'])){
					if ($debug) print "Got token " . $token_info['Token'] . "<br/>";
					delete_user_meta($user->getUserId(), 'credit_token');
					add_user_meta($user->getUserId(), 'credit_token', $token_info['Token']);

					self::RemoveRawInfo($credit_data['id']);
				}
			}
		}
		if ($paid) {
			// Create invoice receipt. Update balance.
			FinanceLog("b4 create_receipt_from_account_ids");
			$subject = "delivery " . CommaImplode($account_line_ids);
			Finance_Client_Accounts::create_receipt_from_account_ids( 0, 0, 0, $paid, $user->getUserId(), date('Y-m-d'), $account_line_ids );

			return true;
		}
		return false;
	}
}
