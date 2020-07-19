<?php

class Finance_Yaad {
	private $api_key;
	private $terminal;
	private $signature;
	private $business_name;
	public $debug;

	function init_hooks()
	{
	}

	/**
	 * @param bool $debug
	 */
	public function setDebug( bool $debug ): void {
		$this->debug = $debug;
	}


	/**
	 * @param $customer_id
	 * @param int $amount
	 * pay customer balance or the given amount.
	 *
	 * @param int $payment_number
	 *
	 * @return bool
	 */
	function pay_user_credit_wrap($customer_id, $amount = 0, $payment_number = 1)
	{
		MyLog(__FUNCTION__ . ": pay for $customer_id");
		// $delivery_ids = sql_query_array_scalar("select id from im_delivery where payment_receipt is null and draft is false");
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
		$user = new Fresh_Client($customer_id);

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
		return $this->pay_user_credit($user, $paying_transactions, $amount, $change, $payment_number);
	}

	static function getCustomerStatus(Fresh_Client $C, $string)
	{
		if ($string)
			return (SqlQuerySingleScalar( "select count(*) from im_payment_info where card_number not like '%X%' and email = " . QuoteText($C->get_customer_email())) > 0 ? 'C' : '') .
			(SqlQuerySingleScalar( "select count(*) from im_payment_info where card_number like '%X%' and email = " . QuoteText($C->get_customer_email())) > 0 ? 'X' : '');
		else
			return (SqlQuerySingleScalar( "select count(*) from im_payment_info where card_number not like '%X%' and email = " . QuoteText($C->get_customer_email())) > 0 ? 1 : 0) +
			       (SqlQuerySingleScalar( "select count(*) from im_payment_info where card_number like '%X%' and email = " . QuoteText($C->get_customer_email())) > 0 ? 2 : 0);

	}
	function pay_user_credit(Fresh_Client $user, $account_line_ids, $amount, $change, $payment_number = 1)
	{
		MyLog(__FUNCTION__ . " " . $user->getName() . " " . $amount);
		$debug = false;
		if (0 == $amount)
			return true;

		$token = get_user_meta($user->getUserId(), 'credit_token', true);

		$credit_data = SqlQuerySingleAssoc( "select * from im_payment_info where user_id = " . $user->getUserId());
		// .                                    " and card_number not like '%X%'");
		if (! $credit_data) {
			MyLog("no credit info found");
			return false;
		}

		if ($token) {
			MyLog("trying to pay with token user " . $user->getName());
			$transaction_info = self::TokenPay( $token, $credit_data, $user, $amount, CommaImplode($account_line_ids), $payment_number );
			$transaction_id = $transaction_info['Id'];
			if (! $transaction_id or ($transaction_info['CCode'] != 0)) {
				$message = $user->getName() . ": Got error " . self::ErrorMessage($transaction_info['CCode']) . "\n";
				print $message;
				MyLog($message);
				return false;
			}

			$paid = $transaction_info['Amount'];
			if ($paid) self::RemoveRawInfo($credit_data['id']);
		} else {
			MyLog("trying to pay with credit info " . $user->getName());
			if ($this->debug) print "trying to pay with credit info<br/>";
			// First pay. Use local store credit info, create token and delete local info.
			$transaction_info = self::FirstPay($credit_data, $user, $amount, CommaImplode($account_line_ids), $payment_number);
			// info: Id, CCode, Amount, ACode, Fild1, Fild2, Fild3
			if (($transaction_info['CCode'] != 0)) {
				MyLog(__FUNCTION__, $transaction_info['CCode']);
				print "Got error " . self::ErrorMessage($transaction_info['CCode']) . "\n";
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
				if ($this->debug) print "pay successful $transaction_id<br/>";

				// Create token and save it.
				$token_info = self::GetToken( $transaction_id );
				if (isset($token_info['Token'])){
					if ($debug) print "Got token " . $token_info['Token'] . "<br/>";
					add_user_meta($user->getUserId(), 'credit_token', $token_info['Token']);

					self::RemoveRawInfo($credit_data['id']);
					print "נוצר טוקן\n";
				}
			}
		}
		if ($paid) {
			// Create invoice receipt. Update balance.
			Finance_Clients::create_receipt_from_account_ids( 0, 0, 0, $paid, $user->getUserId(), date('Y-m-d'), $account_line_ids );

			return true;
		}
		return false;
	}

	static function ErrorMessage($code)
	{
		switch ($code)
		{
			case 2:
				return "גנוב החרם כרטיס";
			case 4:
				return "סירוב";
			case 6:
				return "מספר ת.ז שגוי";
			case 447:
				return "מספר כרטיס שגוי";
		}
		return "Error number $code";
	}

	function RemoveRawInfo($row_id)
	{
		global $wpdb;
		MyLog(__FUNCTION__ . ": $row_id");
		MyLog($row_id, __FUNCTION__);
		$table_name = "im_payment_info";
		$card_four_digit   = $wpdb->get_var("SELECT card_four_digit FROM $table_name WHERE id = ".$row_id." ");
		$dig4 = setCreditCard($card_four_digit);
		SqlQuery("UPDATE $table_name SET card_number =  '".$dig4."' WHERE id = ".$row_id." ");
		return true;
	}

	/**
	 * @param $credit_info
	 * @param Fresh_Client $user_info
	 * @param float $amount
	 * @param string $delivery_numbers
	 * @param int $payment_number
	 *
	 * @return array
	 */
	function FirstPay($credit_info, Fresh_Client $user_info, float $amount, string $delivery_numbers, int $payment_number = 1)
	{
		// General
		$params = array();
		self::SetPayInfo($params);
		self::SetTransactionInfo($params, $user_info, $amount, $delivery_numbers, $payment_number);
		$params["CC"]     = $credit_info['card_number'];
		$params["Tmonth"] = $credit_info['exp_date_month'];
		$params["Tyear"]  = $credit_info['exp_date_year'];
		$params["UserId"] = $credit_info['id_number'];
		$params["UserId"] = $credit_info['id_number'];
//		MyLog(StringVar($params));
		$rc = $this->CallServer( 'https://icom.yaad.net/p3/', $params );
		self::SaveTransaction($rc, $user_info, $payment_number);
		return $rc;
	}
	/**
	 * Yaad_Pay constructor.
	 *
	 * @param $api_key
	 * @param $terminal
	 */
	public function __construct( $api_key, $terminal, $business_name ) {
		$this->debug = false;
		$this->api_key   = $api_key;
		$this->terminal  = $terminal;
		$this->signature = null;
		$this->business_name = $business_name;
		self::SignIn();
	}

	public function TokenPay( string $token, array $credit_info, Fresh_Client $user_info, float $amount, string $delivery_info, int $payment_number = 1) {
		$params = array();
		self::SetPayInfo($params);
		self::SetTransactionInfo($params, $user_info, $amount, $delivery_info, $payment_number);
		$params["Token"] = "True";
		$params["CC"] = $token;
		$params["Tmonth"] = $credit_info['exp_date_month'];
		$params["Tyear"]  = $credit_info['exp_date_year'];
		$rc = $this->CallServer( 'https://icom.yaad.net/p3/', $params );
		self::SaveTransaction($rc, $user_info, $payment_number);
		return $rc;
	}

	private function SaveTransaction($rc, $user_info, $payment_number)
	{
		if (isset($rc['Id'])) {
			$rc['transaction_id'] = $rc['Id'];
			unset( $rc['Id'] );
		}
		$rc['user_id'] = $user_info->getUserId();
		$rc['pay_date'] = date("Y-m-d");
		$rc['payment_number'] = $payment_number;
		SqlInsert("yaad_transactions", $rc, array("Fild1", "Fild2", "Fild3"));
	}

	private function GetToken( $transid ) {
		if ( ! $this->signature ) {
			return false;
		}
		$params = array(
			"action"    => "getToken",
			"Masof"     => $this->terminal,
			"Key"       => $this->api_key,
			"TransId"   => $transid,
			"PassP"     => "yaad",
			"signature" => $this->signature
		);

		return $this->CallServer( 'https://icom.yaad.net/p/', $params );
	}

	public function SignIn() {
		$request_params = array(
			"action" => "APISign",
			"What"   => "SIGN",
			"Key"    => $this->api_key,
			"PassP"  => "yaad.net",
			"Masof"  => $this->terminal
		);

		$result = $this->CallServer( 'https://icom.yaad.net/p/', $request_params );
		if ( $result ) {
			if (! isset($result["signature"])) {
				$message = "can't login to yaad." . StringVar($result);
				Finance::instance()->add_admin_notice($message);
				$this->signature = null;
			} else
				$this->signature = $result["signature"];

			return true;
		}

		return false;
	}

	private function CallServer( $base_url, $request_params )
	{
		$url = AddParamToUrl( $base_url, $request_params );
		if ($this->debug) print $url . "<br/>";

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$output = curl_exec( $ch );

		return ParseQuery( $output );
	}

	private function SetPayInfo(&$params)
	{
		$params['action'] = 'soft';
		$params["Masof"] = $this->terminal;
		$params["PassP"] = "yaad";
		$params["KEY"] = $this->api_key;
		$params["UTF8"] = "True";
		$params["UTF8out"] = "True";
		$params["Coin"] = 1;
	}

	private function SetTransactionInfo(&$params, Fresh_Client $user_info, float $amount, string $delivery_numbers, int $num_of_payments = 1) {
		$params['Info']       = urlencode( "delivery " . $delivery_numbers );
		$params["Amount"]     = $amount;
		$params["Tash"]       = $num_of_payments;
		if ($num_of_payments > 1)
			$params["FixTash"] = $num_of_payments;
		$params["tashType"]   = 1;
		$params["ClientName"] = urlencode( $user_info->getName() );
//		$params['UserId']    = get_user_meta( $user_info->getUserId(), 'id_number', true );
		$params['UserId']  = SqlQuerySingleScalar( "select id_number from im_payment_info where email = '" . $user_info->get_customer_email() . "'" );
	}

	static public function History($customer_id)
	{
		$trans_args = array("query" => "user_id = $customer_id",
			"post_file"=>Finance::getPostFile(), "order"=>"pay_date desc");

//		$result = Core_Html::

		return Core_Gem::GemTable("yaad_transactions", $trans_args);

	}
}