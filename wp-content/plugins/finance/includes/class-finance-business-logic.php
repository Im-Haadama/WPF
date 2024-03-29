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
		$loader->AddAction("finance_get_transaction_amount", $this, 'get_transaction_amount');
		add_action('admin_init', __CLASS__ . '::CreateTokens');
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
		$progress_key = __FUNCTION__ . "_progress";

		$users = explode( ",", GetParam( "users", true, true ) );
		$payment_number = GetParam("number", false, 1);
		$amount = GetParam("amount", false, 0);

		InfoUpdate($progress_key, "starting " . __FUNCTION__);
		foreach ( $users as $user ) {
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
		$user = new Finance_Client( $customer_id );

		if (null == $customer_id) {
			print "Failed: no customer id";
			die (1);
		}
		$amount = GetArg($args, "amount");
		$payment_number = GetArg($args, "payment_number");
		$check_only = GetArg($args, "check_only");

		if (! $this->Paying_Connect()) return false;

		if (! $check_only) {
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

			if ( $amount == 0 ) {
				$amount = $user->balance();
			}

			$rows          = SqlQueryArray( $sql );
			$current_total = 0;

			$paying_transactions = [];
			foreach ( $rows as $row ) {
				$trans_amount = $row[2];
				if ( ( $trans_amount + $current_total ) < ( $amount + 15 ) ) {
					array_push( $paying_transactions, $row[0] );
					$current_total += $trans_amount;
				}
			}

			$change = $amount - $current_total;
		} else {
			$paying_transactions = null;
			$change = 0;
		}

		$credit_data = SqlQuerySingleAssoc("select * from im_payment_info where user_id = $customer_id");
		if (! $credit_data) {
			print "failed: no credit info for user " . $user->getName();
			return false;
		}

		$rc = $this->pay_user_credit($user, $credit_data, $paying_transactions, $amount, $change, $payment_number, $check_only);
		if (! $rc) {
			print "Failed: " . $this->getErrorMessage();
		}
//		foreach ($delivery_ids as $delivery_id) {
//			$this->pay_user_credit( $user_id, $delivery_id );
//			die(0);
//		}
	}

	function pay_user_credit(Finance_Client $user, $credit_data, $account_line_ids, $amount, $change = 0, $payment_number = 1, $check_only = false)
	{
		$progress_key = "pay_credit_progress";

//		print __FUNCTION__;
		FinanceLog(__FUNCTION__ . " " . $user->getName() . " " . $amount, $progress_key);

		if (0 == $amount)
			return true;

		$token = get_user_meta($user->getUserId(), 'credit_token', true);

		if (!$check_only and $token) {
			FinanceLog("trying to pay with token user " . $user->getName(), $progress_key);
			$transaction_info = $this->paying->TokenPay( $token, $credit_data, $user->getName(), $user->getUserId(), $amount, self::payment_subject($account_line_ids), $payment_number, $check_only );
			if  (! $transaction_info) {
				FinanceLog("Failed: can't pay. Contact support (no transaction info)", $progress_key);
				return false;
			}
			$transaction_id = $transaction_info['Id'];
			if (! $transaction_id or ($transaction_info['CCode'] != 0)) {
				$message = "Failed: " . $user->getName() . ": Got error " . $this->paying->ErrorMessage($transaction_info['CCode']) . "\n";
				FinanceLog($progress_key, $message, $progress_key);
				return false;
			}

			$paid = $transaction_info['Amount'];
			FinanceLog("paid $paid user " . $user->getName(), $progress_key);

			if ($paid) E_Fresh_Payment_Gateway::RemoveRawInfo($credit_data['id']);
		} else {
			FinanceLog("trying to pay with credit info " . $user->getName(), $progress_key);
			// First pay. Use local store credit info, create token and delete local info.
			$transaction_info = $this->paying->CreditPay($credit_data, $user->getName(), $user->getUserId(), $amount, self::payment_subject($account_line_ids), $payment_number, $check_only);
			// info: Id, CCode, Amount, ACode, Fild1, Fild2, Fild3
			if ((($transaction_info['CCode'] != 0) and ($check_only and $transaction_info['CCode'] != 600))) {
				FinanceLog(__FUNCTION__ . $transaction_info['CCode']);
				$this->error_message .= "Got error " . $this->paying->ErrorMessage($transaction_info['CCode']) . "\n";
				FinanceLog($this->error_message, $progress_key);
				return false;
			}
			if ($check_only)  return true;

			$transaction_id = $transaction_info['Id'];
			if (! $transaction_id){
				print "No transaction id";
				return false;
			}
			$paid = $transaction_info['Amount'];
			if ($transaction_id) {
				// Create token and save it.
				$token_info = self::GetToken( $transaction_id );
				if (isset($token_info['Token'])){
					delete_user_meta($user->getUserId(), 'credit_token');
					add_user_meta($user->getUserId(), 'credit_token', $token_info['Token']);

					E_Fresh_Payment_Gateway::RemoveRawInfo($credit_data['id']);
				}
			}
		}
		if ($paid) {
			// Create invoice receipt. Update balance.
			FinanceLog("Creating receipt", $progress_key);
			Finance_Client_Accounts::create_receipt_from_account_ids( 0, 0, 0, $paid, $user->getUserId(), date('Y-m-d'), $account_line_ids );
			FinanceLog("Done", $progress_key);

			return true;
		}
		return false;
	}

	static public function CreateTokens()
	{
		if (! defined('YAAD_API_KEY')) return;
		$info_key = __CLASS__ . __FUNCTION__;
		$last_run = InfoGet($info_key);
		if ($last_run == current_time('Y-m-d')) return;

		FinanceLog(__FUNCTION__);
		$sql = "select id from im_payment_info where card_number not like 'XXXX%' and length(card_number) > 4";
		$ids = SqlQueryArrayScalar($sql);
		$yaad = new Finance_Yaad( YAAD_API_KEY, YAAD_TERMINAL, get_bloginfo( 'name' ), YAAD_PassP );
		foreach ($ids as $id)
		{
			FinanceLog("Handling $id");
			$user_id = SqlQuerySingleScalar("select user_id from im_payment_info where id = $id");
			if (! $user_id) {
				$email = SqlQuerySingleScalar("select email from im_payment_info where id = $id");
				$user_id = SqlQuerySingleScalar("select ID from wp_users where user_email = '$email'");
				SqlQuery("update im_payment_info set user_id = $user_id where id = $id");
			}
			$u = new Finance_Client($user_id);
			$trans_id = SqlQuerySingleScalar("select transaction_id from im_yaad_transactions where user_id = $user_id order by id desc limit 1");
			$token_info = $yaad->GetToken($trans_id);
			$user = new Finance_Client($user_id);
			if (isset($token_info['Token'])) {
				FinanceLog("Got token. Removing raw");
				delete_user_meta( $user->getUserId(), 'credit_token' );
				add_user_meta( $user->getUserId(), 'credit_token', $token_info['Token'] );

				E_Fresh_Payment_Gateway::RemoveRawInfo( $id );
			}
		}
		FinanceLog(__FUNCTION__ . " done");
		InfoUpdate($info_key, current_time("Y-m-d"));
	}

	function credit_clear_token()
	{
		$client_id = GetParam("id");
		Finance_Yaad::ClearToken($client_id);
	}

	function payment_subject($accoount_ids)
	{
		if (! $accoount_ids) return "no info";
		$sql = "select transaction_ref from im_client_accounts where id in (" . CommaImplode($accoount_ids). ")";
		$ids = SqlQueryArrayScalar($sql);
		if (! $ids) die("Failed: deliveries not found");

		$sql = "select order_id from im_delivery where id in (" . CommaImplode($ids). ")";
		$order_ids = SqlQueryArrayScalar($sql);
		if (! $order_ids) die ("Failed: orders not found");

		return __("orders") . " " . CommaImplode($order_ids);
	}

	function get_transaction_amount()
	{
		$sql = "SELECT amount FROM im_business_info \n" .
		       " WHERE id = " . GetParam( "id", true );
		print SqlQuerySingleScalar( $sql );
	}

}
