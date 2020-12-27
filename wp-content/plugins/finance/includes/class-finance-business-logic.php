<?php
/*
 * This class responsible for finance business logic.
 * E.g: Buying: being triggered from the payment gateway, conducting the process: Payment, Creating invoice (after creating the delivery note).
 */

class Finance_Business_Logic {
	private ?Finance_Paying $paying;
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
	public function __construct( ?Finance_Paying $paying = null ) {
		if ($paying)
			$this->paying        = $paying;
		else {
			$this->paying = null;
			self::Paying_Connect();
		}

		$this->error_message = '';
	}

	public function process_payment( $order_id, $args ) : bool {
		$order              = new WC_Order( $order_id );

		$this->Paying_Connect();
		$pay_on_checkout = GetArg($args, "pay_on_checkout", false);

		if (! $this->paying) {
			$this->error_message = "Payment system not connected";
			return false;
		}

		FinanceLog(__FUNCTION__);
		if ($pay_on_checkout)
		{
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
		FinanceLog(__FUNCTION__);
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
}
