<?php
/**
 * User: Shiran
 * Date: 02/19/2015
 */

namespace Invoice4u;
header( "Content-Type: text/plain" );

// we need to send this class to the service
class Document {
	public $ClientID = 375612;
	// --------OR--------
	//public $GeneralCustomer;

	public $DocumentType = 4; // invoice credit
	public $Subject = "Test Receipt";
	public $Currency = "ILS";
	public $IssueDate;
	public $Language = 1;
	public $AssociatedEmails;
	public $Total = 10.00; // amount of credit
	public $CreditAmount = 10.00;// amount of credit
	public $Invoices;
	public $DocumentReffType = "Invoice"; //OR "InvoiceReceipt"

	public function __construct( $docForCredit ) {
		$this->IssueDate        = date( "c", time() ); // can be at the Past no erlier then last invoice
		$this->Discount         = new Discount;
		$this->Items            = array( new Item() );
		$this->AssociatedEmails = array( new Email() );
		$this->Payments         = array( new Payment() );
		$this->GeneralCustomer  = new GeneralCustomer();
		$this->Invoices         = $docForCredit;
	}

}


class GeneralCustomer {
	public $Identifier = "0665445262";
	public $Name = "Some Name";
}

class Discount {
	public $Value = 0;
	public $BeforeTax = true;// discount calculated before tax calculated
	public $IsNominal = true;// means that the discount is calculated according to currency type (in this case: 5 ILS), if it is false than discount calculated in percentages (in this case: 5%)
}

class Payment {
	public $Date;
	public $Amount = 100;
	public $PaymentType = 4; // Cash

	public function __construct() {
		$this->Date = date( "c", time() );
	}

	/* OR
	 public $Date;
	 public $Amount = 100;
	 public $PaymentType = 1; // Credit card
	 public $NumberOfPayments = 1;
	 public $PaymentNumber = "6666"; // card number
	 public $CreditCardName = "aaa"; // card type
	 public $ExpirationDate ="05/15";// car exp. date
	 public  $PayerID = "*********";*/

	/*  OR
	  public $Date;
	  public $Amount = 100;
	  public $PaymentType = 2; // Check
	  public $AccountNumber = "******";
	  public $BankName = "leumi",;
	  public $BranchName = "737";
	  public $PaymentNumber = "5";*/

	/*   OR
	   public $Date;
	   public $Amount = 100;
	   public $PaymentType = 3; // Credit card
	   public $AccountNumber = "******"; // Credit card
	   public $BankName = "leumi"; // Credit card
	   public $BranchName = "737"; // Credit card*/

	/*   OR
	public $Date;
	public $Amount = 100;
	public $PaymentType = 5; // Credit*/

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

class DocumentsRequest {

	public $DocumentNumber = 11097;
	public $Type = "Invoice";
	public $ReportType = "Document"; //Document
	public $CustomerName = "aaaa";
}

class Invoice4u {
	public $token;
	public $result;

	// set connection to the service and return result

	public function Login() {
		$wsdl = "http://privateqa.invoice4u.co.il/Services/LoginService.svc?wsdl";
		$user = array( 'username' => 'shiran.drori@sergata.net', 'password' => '123456', 'isPersistent' => false );

		$this->requestWS( $wsdl, "VerifyLogin", $user );
		$this->token = $this->result;
	}

	// login to get token

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

	// send doc and token object to service to create document

	public function CreateDocument( $docForCredit ) {
		$wsdl = "http://privateqa.invoice4u.co.il/Services/DocumentService.svc?wsdl";

		$doc = new Document( $docForCredit );

		$this->result = $this->requestWS( $wsdl, "CreateDocument", array( 'doc' => $doc, 'token' => $this->token ) );
	}

	public function GetInvoices( $token, $sumToCredit, $documentType ) {
		$docRequest = new DocumentsRequest();
		$result     = $this->requestWS( "http://privateqa.invoice4u.co.il/Services/DocumentService.svc?wsdl", "GetDocuments", array(
			'dr'    => $docRequest,
			'token' => $this->token
		) );

		/* //Set Credit Amount
		 if ($documentType == "InvoiceCredit")
		 {
			 $result->Response->Document->CreditAmount = sumToCredit;
		 }
		 $result->Response->Document->ReceiptAmount = sumToCredit;*/

		return $result->Response->Document;
	}
}

$invoice = new Invoice4u();
$invoice->Login();

// to get an invoice for the receipt
$docForCredit = $invoice->GetInvoices( $invoice->token, 10.00, "InvoiceCredit" );

$invoice->CreateDocument( $docForCredit );

var_dump( $invoice->result );

?>