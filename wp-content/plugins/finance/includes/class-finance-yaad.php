<?php

class Finance_Yaad {
	private $api_key;
	private $terminal;
	private $signature;
	private $business_name;
	public $debug;

	function init_hooks()
	{
		add_filter('pay_user_credit', array($this, 'pay_user_credit_wrap'));

		if (! TableExists("yaad_transactions"))
			SqlQuery("create table im_yaad_transactions
(
	id int auto_increment
		primary key,
	transaction_id int null,
	CCode int null,
	Amount float null,
	ACode varchar(200) null,
	user_id int null,
	pay_date date null
);

");
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
	 * @throws Exception
	 */
	function pay_user_credit_wrap($customer_id, $amount = 0)
	{
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

//		var_dump($paying_transactions);
//		print "total: $current_total. max amount: $amount<br/>";
		$change = $amount - $current_total;
		return $this->pay_user_credit($user, $paying_transactions, $amount, $change);

//		foreach ($delivery_ids as $delivery_id) {
//			$this->pay_user_credit( $user_id, $delivery_id );
//			die(0);
//		}
	}

	function pay_user_credit(Fresh_Client $user, $account_line_ids, $amount, $change)
	{
		$debug = false;
		if (0 == $amount)
			return true;
//		$delivery = new Fresh_Delivery($delivery_id);

		$token = get_user_meta($user->getUserId(), 'credit_token', true);

		$credit_data = SqlQuerySingleAssoc( "select * from im_payment_info where email = " . QuoteText($user->get_customer_email()) .
		                                    " and card_number not like '%X%'");
		if (! $credit_data) return false;

		if ($token) {
			$transaction_info = self::TokenPay( $token, $credit_data, $user, $amount, CommaImplode($account_line_ids) );
			$transaction_id = $transaction_info['Id'];
			if (! $transaction_id or ($transaction_info['CCode'] != 0)) return false;

			$paid = $transaction_info['Amount'];
			if ($paid) self::RemoveRawInfo($credit_data['id']);
		} else {
			if ($this->debug) print "trying to pay with credit info<br/>";
			// First pay. Use local store credit info, create token and delete local info.
			$transaction_info = self::FirstPay($credit_data, $user, $amount, CommaImplode($account_line_ids));
//			var_dump($transaction_info);
			// info: Id, CCode, Amount, ACode, Fild1, Fild2, Fild3
			if (($transaction_info['CCode'] != 0)) {
				print "Got error " . $transaction_info['CCode'] . "<br/>";
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
				print "שולם בהצלחה\n";
				if ($this->debug) print "pay successful $transaction_id<br/>";

				// Create token and save it.
				$token_info = self::GetToken( $transaction_id );
				if (isset($token_info['Token'])){
					if ($debug) print "Got token " . $token_info['Token'] . "<br/>";
					add_user_meta($user->getUserId(), 'credit_token', $token_info['Token']);

					self::RemoveRawInfo($credit_data['id']);
					print "נוצר טוקן";
				}
			}
		}
		if ($paid) {
			// Create invoice receipt. Update balance.
			Finance_Clients::create_receipt_from_account_ids( 0, 0, 0, $paid, $change, $user->getUserId(), date('Y-m-d'), $account_line_ids );

			return true;
		}
		return false;
	}

	function RemoveRawInfo($del_id)
	{
		$table_name = "im_payment_info";

		$card_four_digit   = SqlQuerySingleScalar("SELECT card_four_digit FROM $table_name WHERE id = ".$del_id." ");
		return SqlQuery("UPDATE $table_name SET card_number =  '".$card_four_digit."' WHERE id = ".$del_id." ");
	}
	/**
	 * @param $credit_info
	 * @param Fresh_Client $user_info
	 * @param $amount
	 * @param $payment_info
	 *
	 * @return array
	 */
	function FirstPay($credit_info, Fresh_Client $user_info, $amount, $payment_info)
	{
		// General
		$params = array();
		self::SetPayInfo($params);
		self::SetTransactionInfo($params, $user_info, $amount, $payment_info);
		$params["CC"]     = $credit_info['card_number'];
		$params["Tmonth"] = $credit_info['exp_date_month'];
		$params["Tyear"]  = $credit_info['exp_date_year'];
		$params["UserId"] = $credit_info['id_number'];
		$params["UserId"] = $credit_info['id_number'];
		$rc = $this->CallServer( 'https://icom.yaad.net/p3/', $params );
		self::SaveTransaction($rc, $user_info);
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

	public function TokenPay( $token, $credit_info, Fresh_Client $user_info, $amount, $payment_info) {
		$params = array();
		self::SetPayInfo($params);
		self::SetTransactionInfo($params, $user_info, $amount, $payment_info);
		$params["Token"] = "True";
		$params["CC"] = $token;
		$params["Tmonth"] = $credit_info['exp_date_month'];
		$params["Tyear"]  = $credit_info['exp_date_year'];
		$rc = $this->CallServer( 'https://icom.yaad.net/p3/', $params );
		self::SaveTransaction($rc, $user_info);
		return $rc;


//			"Info"        => $this->business_name,
//			"Order"       => $payment_info,
//			"Tash"        => "1",
//			"UserId"      => $id,
//			"ClientLName" => "Israeli",
//			"ClientName"  => "Israel",
//			"cell"        => "050555555555",
//			"phone"       => "098610338",
//			"city"        => "netanya",
//			"email"       => "testsoft@yaad.net",
//			"street"      => "levanon+3",
//			"zip"         => "42361",
//			"J5"          => "False",
//			"MoreData"    => "True",
//			"Postpone"    => "False",
//			"sendemail"   => "True",
//			"UTF8"        => "True",
//			"Fild1"       => "freepram",
//			"Fild2"       => "freepram",
//			"Fild3"       => "freepram",
//		);

	}

	private function SaveTransaction($rc, $user_info)
	{
		if (isset($rc['Id'])) {
			$rc['transaction_id'] = $rc['Id'];
			unset( $rc['Id'] );
		}
		$rc['user_id'] = $user_info->getUserId();
		$rc['pay_date'] = date("Y-m-d");
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
			"PassP"  => "yaad",
			"Masof"  => $this->terminal
		);

		$result = $this->CallServer( 'https://icom.yaad.net/p/', $request_params );
		if ( $result ) {
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

	private function SetTransactionInfo(&$params, Fresh_Client $user_info, $amount, $payment_info, $num_of_payments = 1) {
		$params['Info']       = urlencode( "delivery " . $payment_info );
		$params["Amount"]     = $amount;
		$params["Tash"]       = $num_of_payments;
		$params["tashType"]   = 1;
		$params["ClientName"] = urlencode( $user_info->getName() );
//		$params['UserId']    = get_user_meta( $user_info->getUserId(), 'id_number', true );
		$params['UserId']  = SqlQuerySingleScalar( "select id_number from im_payment_info where email = '" . $user_info->get_customer_email() . "'" );
	}
}