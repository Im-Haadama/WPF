<?php
/**
 * User: Shiran
 * Date: 02/19/2015
 */
header( "Content-Type: text/plain" );

class Customer {
	public $Name = "balat9"; // must be unique
	public $Active = true; // important
	/*
  public $UniqueID = "";
  public $Email = "Tesfghting@testing.com";

	 public $PayTerms =30;
	 public $Phone = "0465422356";
	 public $Fax = "046588689";
	 public $Cell = "0522256664";
	 public $Address = "הרחוב הראשי";
	 public $City = "עיר חשובה";
	 public $Zip = "523367";*/
}

class Invoice4u {
	public $token;
	public $result;

	public function Login() {
		$wsdl = "http://localhost/Services/LoginService.svc?wsdl";
		$user = array( 'username' => 'shiran.drori@sergata.net', 'password' => '123456', 'isPersistent' => false );

		$this->requestWS( $wsdl, "VerifyLogin", $user );
		$this->token = $this->result;
	}

	private function requestWS( $wsdl, $service, $params ) {
		try {
			$options = array(
				'trace'              => true,
				'exceptions'         => true,
				'cache_wsdl'         => WSDL_CACHE_NONE,
				'connection_timeout' => 10
			);

			$client = new SoapClient( $wsdl, $options );

			//var_dump($client->__getTypes()); exit;

			$response = $client->$service( $params );

			$service = $service . "Result";

			$this->result = $response->$service;

			return $this->result;
		} catch ( SoapFault $f ) {

			echo $f->faultstring;

			return $f->faultstring;
		} catch ( Exception $e ) {

			echo $e->getMessage();

			return $e->getMessage();
		}
	}

	public function CreateCustomer() {
		$wsdl = "http://localhost/Services/CustomerService.svc?wsdl";

		$customer     = new Customer();
		$this->result = $this->requestWS( $wsdl, "Create", array( 'customer' => $customer, 'token' => $this->token ) );
	}
}

$invoice = new Invoice4u();
$invoice->Login();
$invoice->CreateCustomer();

var_dump( $invoice->result );

?>