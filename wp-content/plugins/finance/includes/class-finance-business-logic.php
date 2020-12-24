<?php
/*
 * This class responsible for finance business logic.
 * E.g: Buying: being triggered from the payment gateway, conducting the process: Payment, Creating invoice (after creating the delivery note).
 */

class Finance_Business_Logic {
	private ?Finance_Paying $paying;
	private ?string $error_message;

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
		global $woocommerce;
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
			FinanceLog($this->error_message);
			$save_as_token = (isset($_REQUEST["save_as_token"]) and "1" == $_REQUEST["save_as_token"]);
			if ($save_as_token) {
				$token_info = $instance->GetToken( $instance->GetTransactionId() );
				if ( isset( $token_info['Token'] ) ) {
					FinanceLog( "Saving token for user " . $user->getUserId() );
					add_user_meta( $user->getUserId(), 'credit_token', $token_info['Token'] );
				}
			}
		} else {
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
			$passed = true;
		}
		if (! $passed)
		{
			FinanceLog("payment failed");

			wc_add_notice( __('Payment error:') . $this->error_message, 'error' );

			return array("result"=>'fail');
		}
		FinanceLog(__FUNCTION__ . $pay_on_checkout);
		$order->update_status( $this->order_status, __( 'Awaiting payment', 'woocommerce-other-payment-gateway' ) );

		wc_reduce_stock_levels( $order_id );
		if ( isset( $_POST[ $this->id . '-admin-note' ] ) && trim( $_POST[ $this->id . '-admin-note' ] ) != '' ) {
			$order->add_order_note( esc_html( $_POST[ $this->id . '-admin-note' ] ), 1 );
		}

		$woocommerce->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);
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
		$debug = false;

		FinanceLog(__FUNCTION__ . " $order_id");
		if (! self::Yaad_Connect()) {
			FinanceLog($this->message);
			$this->message = __("Payment system not available. Please contact customer support");
			$this->add_admin_notice($this->message);
			return false;
		}
		if ($debug) print "Connected to Yaad.<br/>";
		$inv = self::Invoice4uConnect();

		if (! $inv or ! $inv->Login()){
			$this->message = "Invoice system not avaiable.";
			return false;
		}

		if ($debug) print "Connected to invoice4u<br/>";

		$O = new Finance_Order($order_id);
		$user = new Finance_Client($O->getCustomerId());
		$amount = $O->getTotal() + $O->getShippingFee();

		if ($debug) print "Goint to get $amount from " . $user->getName() . "<br/>";
		$transaction_info = $this->yaad->FirstPay($credit_info, $user, $amount, "Order $order_id", 1);
		if (($transaction_info['CCode'] != 0)) {
			$this->message = ETranslate("Got error ") . Finance_Yaad::ErrorMessage($transaction_info['CCode']);
			print "Error: " . Finance_Yaad::ErrorMessage($transaction_info['CCode']);
			return false;
		}

		$this->transaction_id = $transaction_info['Id'];

		if ($debug) print "PAID<br/>";
//		$this->yaad->pay_user_credit($user, $credit_info, $order_id, $amount);
//		$this->message = $this->yaad->getMessage();

		$Del = new Finance_Delivery($order_id);
		$del_id = $Del->CreateDeliveryFromOrder($order_id, 1);
		if ($debug) print "Delivery number $del_id<br/>";

		FinanceLog(__FUNCTION__ . " user = " . $user->getUserId() . " amount = $amount " . $user->getUserId(). " del=$del_id");

		if ($debug) print "Going to create invoice receipt<br/>";
		$doc_id = Finance_Client_Accounts::CreateInvoiceReceipt(0, 0, 0, $amount, 0, $O->getCustomerId(), date('Y-m-d'), array($del_id), "הזמנה " . $order_id);

		$this->message = ETranslate("Invoice" ) . " $doc_id " . ETranslate("created");
		return true;
	}


}