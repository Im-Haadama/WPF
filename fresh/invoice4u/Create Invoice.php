<?php
/**
 * User: Shiran
 * Date: 02/19/2015
 */

namespace Invoice4u;

header( "Content-Type: text/plain" );

class Document {
	public $ClientID = 375612;
	public $DocumentType = 1; // invoice
	public $Subject = "Test Invoice";
	public $Currency = "ILS";
	public $Total = 100;
	public $TotalWithoutTax = 100;
	public $TotalTaxAmount = 0;
	public $TaxPercentage = 0;
	public $ExternalComments = "";
	public $InternalComments = "";
	public $BranchID = null;
	public $IssueDate;
	public $ConversionRate = 1;
	public $Discount;
	public $RoundAmount = 0;
	public $Language = 1;
	public $Items;
	public $AssociatedEmails;
	public $Payments;

	public function __construct() {
		$this->IssueDate        = date( "c", time() ); // can be at the Past no erlier then last invoice
		$this->Discount         = new Discount;
		$this->Items            = array( new Item() );
		$this->AssociatedEmails = array( new Email() );
		$this->Payments         = array( new Payment() );
	}
}

class Discount {
	public $Value = 0;
	public $BeforeTax = true;// discount calculated before tax calculated
	public $IsNominal = true;// means that the discount is calculated according to currency type (in this case: 5 ILS), if it is false than discount calculated in percentages (in this case: 5%)
}

class Item {
	public $Code = "001";
	public $Name = "first Item";
	public $Quantity = 1;
	public $Price = 100;
	public $Total = 100;
	public $TotalWithoutTax = 100;
	public $TaxPercentage = 0; // in case you want an item without tax
	public $Discount;

	public function __construct() {
		$this->Discount = new Discount;
	}
}

class Email {
	public $Mail = "test@test.com";
	public $IsUserMail = true;// means that this is the user email, and this is the email that sends the document to all associated email
}

class Payment {
	public $Date;
	public $Amount = 100;
	public $PaymentType = 4;

	public function __construct() {
		$this->Date = date( "c", time() );
	}
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

	public function CreateDocument() {
		$wsdl = "http://localhost/Services/DocumentService.svc?wsdl";

		$doc = new Document();

		$this->result = $this->requestWS( $wsdl, "CreateDocument", array( 'doc' => $doc, 'token' => $this->token ) );
	}
}

$invoice = new Invoice4u();
$invoice->Login();
$invoice->CreateDocument();

var_dump( $invoice->result );

?>