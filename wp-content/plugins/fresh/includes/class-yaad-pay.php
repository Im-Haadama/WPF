<?php

class Yaad_Pay {
	private $api_key;
	private $terminal;
	private $signature;

	/**
	 * Yaad_Pay constructor.
	 *
	 * @param $api_key
	 * @param $terminal
	 */
	public function __construct( $api_key, $terminal ) {
		$this->api_key  = $api_key;
		$this->terminal = $terminal;
		$this->signature = null;
	}

	public function Pay($token, $amount)
	{
		$params = array(
			"action"=> "soft",
			"Masof"=> $this->terminal,
			"PassP"=>"yaad",
			"Amount"=>$amount,
			"CC"=>$token,
			"Tmonth"=>"4",
			"Tyear"=>"2020",
			"Coin"=>"1",
			"Info"=>"fruity",
			"Order"=>"12345678910",
			"Tash"=>"1",
			"UserId"=>"012680286",
			"ClientLName"=>"Israeli",
			"ClientName"=>"Israel",
			"cell"=>"050555555555",
			"phone"=>"098610338",
			"city"=>"netanya",
			"email"=>"testsoft@yaad.net",
			"street"=>"levanon+3",
			"zip"=>"42361",
			"J5"=>"False",
			"MoreData"=>"True",
			"Postpone"=>"False",
			"sendemail"=>"True",
			"UTF8"=>"True",
			"UTF8out"=>"True",
			"Fild1"=>"freepram",
			"Fild2"=>"freepram",
			"Fild3"=>"freepram",
			"Token"=>"True"
		);
		return $this->CallServer('https://icom.yaad.net/p3/', $params);
	}

	public function GetToken($transid)
	{
		if (! $this->signature)
			return false;
		$params = array(
			"action"=> "getToken",
			"Masof" => $this->terminal,
			"Key" => $this->api_key,
			"TransId" => $transid,
			"PassP" => "yaad",
			"signature" => $this->signature
		);

		return $this->CallServer('https://icom.yaad.net/p/', $params);
	}

	public function SignIn()
	{
		$request_params = array(
			"action" => "APISign",
			"What" => "SIGN",
			"Key" => $this->api_key,
			"PassP" => "yaad",
			"Masof" => $this->terminal
//			"Order" => "12345678910",
//			"Info" => "test-api",
//			"Amount" => "10",
//			"UTF8" => "True",
//			"UTF8out" => "True",
//			"UserId" => "203269535",
//			"ClientName" => "Israel",
//			"ClientLName" => "Isareli",
//			"street" => "levanon+3",
//			"city" => "netanya",
//			"zip" => "42361",
//			"phone" => "098610338",
//			"cell" => "050555555555",
//			"email" => "test@yaad.net",
//			"Tash" => "2",
//			"FixTash" => "False",
//			"ShowEngTashText" => "False",
//			"Coin" => "1",
//			"Postpone" => "False",
//			"J5" => "False",
//			"Sign" => "True",
//			"MoreData" => "True",
//			"sendemail" => "True",
//			"SendHesh" => "True"
	);

		$result = $this->CallServer('https://icom.yaad.net/p/', $request_params);
		if ($result) {
			$this->signature = $result["signature"];

			return true;
		}
		return false;
	}

	private function CallServer($base_url, $request_params)
	{
		$url = AddParamToUrl($base_url, $request_params);
//		print $url . "<br/>";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);

		$result = ParseQuery($output);

		return $result;
	}
}