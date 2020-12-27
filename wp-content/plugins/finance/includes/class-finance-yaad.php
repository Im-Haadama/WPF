<?php
/* Low level class that handles the calls to YAAD Payment server. 
*/

class Finance_Yaad extends Finance_Paying {
	private $api_key;
	private $terminal;
	private $signature;
	private $business_name;
	private $PassP;
	private $message;

	/**
	 * Yaad_Pay constructor.
	 *
	 * @param $api_key
	 * @param $terminal
	 */
	public function __construct( $api_key, $terminal, $business_name, $PassP = "yaad") {
		$this->debug = false;
		$this->api_key   = $api_key;
		$this->terminal  = $terminal;
		$this->signature = null;
		$this->business_name = $business_name;
		$this->PassP = $PassP;
		$this->message = null;
		self::SignIn();
		FinanceLog("Sig: " . $this->signature);
	}

	/**
	 * @return mixed
	 */
	public function getMessage() {
		return $this->message;
	}
//	protected static $_instance = null;
//
//	public static function instance() {
//		if ( is_null( self::$_instance ) ) {
//			self::$_instance = new self( "Finance" );
//		}
//
//		return self::$_instance;
//	}

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
			case 902:
				return "הגדרות מסוף";
		}
		return "Error number $code";
	}

	/**
	 * @param $credit_info
	 * @param Finance_Client $user_info
	 * @param float $amount
	 * @param string $subject
	 * @param int $payment_number
	 *
	 * @return array
	 */
	function CreditPay($credit_info, string $user_name, int $user_id, float $amount, string $subject, int $payment_number = 1)
	{
		FinanceLog(__FUNCTION__ . " " . $credit_info['card_number'] . " $amount ");
		// General
		$params = array();
		self::SetPayInfo($params);
		self::SetTransactionInfo($params, $user_name, $user_id, $amount, $subject, $payment_number);
		$params["CC"]     = $credit_info['card_number'];
		$params["Tmonth"] = $credit_info['exp_date_month'];
		$params["Tyear"]  = $credit_info['exp_date_year'];
		$params["UserId"] = $credit_info['id_number'];
		$params["UserId"] = $credit_info['id_number'];
//		FinanceLog(StringVar($params));
		$rc = $this->CallServer( 'https://icom.yaad.net/p3/', $params );
		self::SaveTransaction($rc, $user_id, $payment_number);
		return $rc;
	}

	public function TokenPay( string $token, array $credit_info, string $user_name, int $user_id, float $amount, string $delivery_info, int $payment_number = 1) {
		$params = array();
		self::SetPayInfo($params);
		self::SetTransactionInfo($params, $user_name, $user_id, $amount, $delivery_info, $payment_number);
		$params["Token"] = "True";
		$params["CC"] = $token;
		$params["Tmonth"] = $credit_info['exp_date_month'];
		$params["Tyear"]  = $credit_info['exp_date_year'];
		$rc = $this->CallServer( 'https://icom.yaad.net/p3/', $params );
		self::SaveTransaction($rc, $user_id, $payment_number);
		return $rc;
	}

	private function SaveTransaction($rc, $user_id, $payment_number)
	{
		if (isset($rc['Id'])) {
			$rc['transaction_id'] = $rc['Id'];
			unset( $rc['Id'] );
		}
		$rc['user_id'] = $user_id;
		$rc['pay_date'] = date("Y-m-d");
		$rc['payment_number'] = $payment_number;
		SqlInsert("yaad_transactions", $rc, array("Fild1", "Fild2", "Fild3"));
	}

	public function GetToken($transid ) {
		if ( ! $this->signature ) {
			return false;
		}
		$params = array(
			"action"    => "getToken",
			"Masof"     => $this->terminal,
			"Key"       => $this->api_key,
			"TransId"   => $transid,
			"PassP"     => $this->PassP,
			"signature" => $this->signature
		);

		return $this->CallServer( 'https://icom.yaad.net/p/', $params );
	}

	public function SignIn() {
		$request_params = array(
			"action" => "APISign",
			"What"   => "SIGN",
			"Key"    => $this->api_key,
			"PassP"  => $this->PassP,
			"Masof"  => $this->terminal
		);

		$result = $this->CallServer( 'https://icom.yaad.net/p/', $request_params );
		if ( $result ) {
			if (! isset($result["signature"])) {
				$this->message = "Can't login to yaad." . StringVar($result);
//				Finance::instance()->add_admin_notice($message);
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

		if (! function_exists("curl_init")) {
			FinanceLog("Curl not installed");
			return false;
		}

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
		$params["PassP"] = $this->PassP;
		$params["KEY"] = $this->api_key;
		$params["UTF8"] = "True";
		$params["UTF8out"] = "True";
		$params["Coin"] = 1;
	}

	private function SetTransactionInfo(&$params, String $user_name, int $user_id, float $amount, string $delivery_numbers, int $num_of_payments = 1) {
		$params['Info']       = urlencode( "delivery " . $delivery_numbers );
		$params["Amount"]     = $amount;
		$params["Tash"]       = $num_of_payments;
		if ($num_of_payments > 1)
			$params["FixTash"] = $num_of_payments;
		$params["tashType"]   = 1;
		$params["ClientName"] = urlencode( $user_name );
//		$params['UserId']    = get_user_meta( $user_info->getUserId(), 'id_number', true );
		$params['UserId']  = $user_id;
	}

	static public function History($customer_id)
	{
		$trans_args = array("query" => "user_id = $customer_id",
			"post_file"=>Finance::getPostFile(), "order"=>"pay_date desc");

		return Core_Gem::GemTable("yaad_transactions", $trans_args);
	}

	static function ClearToken($client_id)
	{
		FinanceLog(__FUNCTION__ . $client_id . " " . get_user_id());
		return delete_user_meta($client_id, 'credit_token');
	}
}