<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/08/16
 * Time: 06:25
 */

if (! defined('ApiService'))
	define( "ApiService", "https://api.invoice4u.co.il/Services" );


class InvoiceCustomer {
	public $Name;
	public $Active = true; // must be unique
	public $Email; // important
	public $Phone;

	public function __construct( $name ) {
		$this->Name = $name;
	}
}

class Finance_Invoice4u
{
	public $token;
	public $result;
	private $user, $password;
	static private $instance = null;

	/**
	 * Invoice4u constructor.
	 */
	public function __construct( $user, $password ) {
		if (! defined('WSDL_CACHE_NONE')) { // Setup error
			$message = "Invoice Setup Error - WSDL is missing";
			print $message;
			FinanceLog($message);
			$this->token = null;
			return;
		}
		$this->user     = $user;
		$this->password = $password;
		$this->Login();
		self::$instance = $this;
	}

	/**
	 * @return Finance_Invoice4u
	 */
	static public function getInstance(): ?Finance_Invoice4u {
		return self::$instance;
	}

	public function Login() {
		return (null != $this->token) or self::DoLogin( $this->user, $this->password );
	}

	private function DoLogin( $invoice_user, $invoice_password, $force = false ) : bool {
		self::InvoiceLog("Invoice4u Login");

		$wsdl = ApiService . "/LoginService.svc?wsdl";
		$user = array( 'username' => $invoice_user, 'password' => $invoice_password, 'isPersistent' => false );

		$this->requestWS( $wsdl, "VerifyLogin", $user );
		$this->token = $this->result;
		if (! $this->token)
			self::InvoiceLog("Login failure");

		return (null != $this->token);
	}

	private function requestWS( $wsdl, $service, $params )
	{
		MyLog(__FUNCTION__, $wsdl . " " . $service, 'invoice4u.log');
		if (! defined('WSDL_CACHE_NONE')) return null;
		try {
			$options = array(
				'trace'              => true,
				'exceptions'         => true,
				'cache_wsdl'         => WSDL_CACHE_NONE,
				'connection_timeout' => 10
			);

			$client = new SoapClient( $wsdl, $options );

			$response = $client->$service( $params );

			$service = $service . "Result";

			$this->result = $response->$service;

			return $this->result;
		} catch ( SoapFault $f ) {
			echo $f->faultstring . "<br/>";
			print "service=$service<br/>";
			var_dump(libxml_get_last_error());
			return $f->faultstring;
		} catch ( Exception $e ) {
			echo $e->getMessage();

			return $e->getMessage();
		}
	}

	public function CreateReceipt($doc)
	{
		$wsdl = ApiService . "/DocumentService.svc?wsdl";
		$this->result = $this->requestWS( $wsdl, "CreateDocument", array( 'doc' => $doc, 'token' => $this->token ) );
		if (isset($this->result->Errors->CommonError))
//			var_dump($this->result->Errors);
			print $this->result->Errors->CommonError->Error ."<br/>";
//		var_dump($this->result);
		return $this->result->DocumentNumber;
	}

	public function CreateInvoiceDocument( $doc ) {
		if ( ! $doc->ClientID > 0 ) {
			print "No client<br/>";

			return 0;
		}

		if (! $this->token) {
			print "invoice4u: Not connected<br/>";
			return false;
		}
		$wsdl = ApiService . "/DocumentService.svc?wsdl";

		$this->result = $this->requestWS( $wsdl, "CreateDocument", array( 'doc' => $doc, 'token' => $this->token ) );

		$docNum = $this->result->DocumentNumber;
		if ( $docNum == 0 ) {
			print "Error creating document!<br/>";
//			foreach ( $doc->Items as $item ) {
//				print $item->Name . " " . $item->Quantity . " " . $item->Price . " " . $item->Total . "<br/>";
//			}
			// var_dump( $doc );
			$check_total  = 0;
			$check_total2 = 0;
			$pay_total    = 0;
			// Sum lines
			foreach ( $doc->Items as $item ) {
				// print ($item->Total) . "<br/>";
				$check_total  += $item->Total;
				$check_total2 += $item->Quantity * $item->Price;
				//var_dump ($item);
			}
			foreach ( $doc->Payments as $pay ) {
				$pay_total += $pay->Amount;
			}

			print "errors: ";
			if ( $check_total != $check_total2 or $check_total != $pay_total ) {
				print "calculated total: " . $check_total . "<br/>";
				print "calculated total2: " . $check_total2 . "<br/>";
				print "total paid: " . $pay_total . "<br/>";
			}
			print "client id: " . $doc->Id . "<br/>";
		}

		return $docNum;
	}

	function InvoiceLog($message, $function = '')
	{
		MyLog($message,$function,'invoice4u.log');
	}

//    public function CreateDocument()
//    {
//        $wsdl = "http://private.invoice4u examples.co.il/Services/DocumentService.svc?wsdl";
//
//        $this->result = $this->requestWS($wsdl, "CreateDocument", array('doc' => $this->doc, 'token' => $this->token));
//    }

	function GetCustomerByID( $invoice_id, $client_name = null, $client_email = null)
	{
		// GetFullCustomer(int id, int orgID, string token)
		self::InvoiceLog($invoice_id, __FUNCTION__);
		$wsdl = ApiService . "/CustomerService.svc?wsdl";

		$cust        = new InvoiceCustomer( $client_name);
		$cust->ID = $invoice_id;
		$cust->Email = $client_email;
		MyLog("iid=$invoice_id email = $client_email<br/>");
		$response = $this->requestWS( $wsdl, "GetCustomers", array(
			'cust' => $cust,
			'OrgId' => 0,
			'token'      => $this->token
		) );
		$this->InvoiceLog("found: " . isset( $response->Response->Customer ));

		if ( isset( $response->Response->Customer ) ){
			if (is_array($response->Response->Customer)) return $response->Response->Customer[0];
			return $response->Response->Customer;
		}
		return null;
	}

	public function GetCustomerByEmail( $email ) {
		$this->InvoiceLog( $email, __FUNCTION__ );

		$cust        = new InvoiceCustomer( "" );
		$cust->Email = $email;

		return self::DoGetCustomer( $cust );
	}

	private function DoGetCustomer($cust)
	{
		$wsdl = ApiService . "/CustomerService.svc?wsdl";

		if (! $this->token) {
			self::InvoiceLog("not login");
			return null;
		}

		$response = $this->requestWS( $wsdl, "GetCustomers", array(
			'cust'       => $cust,
			'token'      => $this->token,
			'getAllRows' => false
		) );
		if ( isset( $response->Response->Customer ) ) return $response->Response->Customer;
		return null;
	}

	function GetCustomerByName( $name ) {
		self::InvoiceLog($name, __FUNCTION__);

		$c = self::DoGetCustomer(new InvoiceCustomer($name));
		if (is_array($c)) return $c[0]; // Shouldn't happen, but did in the test.
		return $c;
	}

//	private function GetCustomerById( $id )  {
//		$wsdl = ApiService . "/CustomerService.svc?wsdl";
//
//		$cust = new InvoiceCustomer( "" );
//		$cust->UniqueID = $id;
//		print "searching for id $id<br/>";
//
//		$response  = $this->requestWS( $wsdl, "GetCustomers", array(
//			'cust'       => $cust,
//			'token'      => $this->token
////		,	'getAllRows' => false
//		) );
//
//		var_dump($response);
//		die (1);
//		if (! $response->Response) return null;
//
//		$customers = $response->Response->Customer;
//
//		if ( $customers )
//			foreach ( $customers as $customer )
//				if ( $customer->ID == $id ) return $customer;
//
//		return null;
//	}

	public function CreateUser( $name, $email, $phone )
	{
		if (! $this->token) {
			MyLog("invoice4u: Not connected");
			return false;
		}
		$wsdl = ApiService . "/CustomerService.svc?wsdl";

		$customer        = new InvoiceCustomer( $name );
		$customer->Email = $email;
		$customer->Phone = $phone;

		$this->result    = $this->requestWS( $wsdl, "Create",
			array( 'customer' => $customer, 'token' => $this->token ) );

		if ( $this->result->Errors ) return false;

		return $this->result->ID;
	}
}

// we need to send this class to the service
class InvoiceDocument {
	public $ClientID;
	public $DocumentType;
	public $Subject;
	public $Currency = "ILS";
	public $Total = 0;
	public $TotalWithoutTax;
	public $TotalTaxAmount;
	public $TaxPercentage;
	public $TaxIncluded = 1; // means that the item price allready include tax, for examples: if item Price = 118 ,so item Total = 118 and item TotalWithoutTax = 100 (in case that TaxPercentage = 18)
	public $ExternalComments = "";
	public $InternalComments = "";
	public $BranchID = null;
	public $IssueDate;
	public $ConversionRate = 1;
	public $Discount;
	public $RoundAmount;
	public $Language = 1;
	public $Items;
	public $AssociatedEmails;
	public $Payments;
	public $ToRoundAmount;

	public function __construct() {
		$this->IssueDate = date( "c", time() ); // can be at the Past no earlier then last invoice
		$this->Discount  = new InvoiceDiscount;
		// $this->Items = array(new Item());
		$this->AssociatedEmails; // = array(new Email());
		$this->Payments = array();
	}
}

class ReceiptDocument {
	public $ClientID;
	public $DocumentType = 2; // receipt
	public $Subject;
	public $Currency = "ILS";
	public $IssueDate;
	public $Language = 1;
	public $AssociatedEmails;
	public $Payments;

	/******************* in case you want to create receipt to specific invoices
	 * Invoices = GetInvoices(token, 10.00, DocumentType.Receipt),
	 * DocumentReffType = (int)DocumentType.Invoice,
	 ********************/

	public function __construct($ClientID) {
		$this->IssueDate        = date( "c", time() ); // can be at the Past no erlier then last invoice
		$this->Discount         = new InvoiceDiscount;
//		$this->Items            = array( new InvoiceItem() );
		$this->AssociatedEmails = array( new InvoiceEmail() );
		$this->Payments         = array();
		$this->ClientID = $ClientID;
	}
}

class InvoiceDiscount {
	public $Value = 0;
	public $BeforeTax = true;// discount calculated before tax calculated
	public $IsNominal = true;// means that the discount is calculated according to currency type (in this case: 5 ILS), if it is false than discount calculated in percentages (in this case: 5%)
}

class InvoiceItem {
	public $Code; // = "001";
	public $Name; // = "first Item";
	public $Quantity; // = 1;
	public $Price; // = 100;
	public $Total; // = 100;
	public $TotalWithoutTax;
	public $TaxPercentage; // in case you want an item without tax
	public $Discount;

	public function __construct() {
		$this->Discount = new InvoiceDiscount;
	}
}

class InvoicePaymentCash {
	public $Date;
	public $Amount;
	public $PaymentType = 4; // Cash

	public function __construct() {
		$this->Date = date( "c", time() );
	}
}

class InvoicePaymentCredit {
	public $Date;
	public $Amount;
	public $PaymentType = 1; // Credit card
	public $NumberOfPayments = 1;
	public $PaymentNumber; // card number
	public $CreditCardName; // card type
	public $ExpirationDate;// car exp. date
	public $PayerID;

	public function __construct() {
		$this->Date = date( "c", time() );
	}
}

/*  OR
  public $Date;
  public $Amount = 100;
  public $PaymentType = 2; // Check
  public $AccountNumber = "******";
  public $BankName = "leumi",;
  public $BranchName = "737";
  public $PaymentNumber = "5";*/

class InvoicePaymentBank {
	public $Date;
	public $Amount;
	public $PaymentType = 3; // Credit card
	public $AccountNumber; // Credit card
	public $BankName; // Credit card
	public $BranchName; // Credit card*/

	public function __construct() {
		$this->Date = date( "c", time() );
	}
}

class InvoicePaymentCheck {
	public $Date;
	public $Amount;
	public $PaymentType = 2; // Check

	public function __construct() {
		$this->Date = date( "c", time() );
	}
}

/*   OR
public $Date;
public $Amount = 100;
public $PaymentType = 5; // Credit*/

// send doc and token object to service to create document

class InvoiceEmail {
	public $Mail; // = "yaakov@im-haadama.co.il";
	public $IsUserMail = false;// means that this is the user email, and this is the email that sends the document to all associated email
}

abstract class InvoiceLanguage {
	const Hebrew = 1;
	const English = 2;
}

abstract class InvoiceDocumentType {
	const Invoice = 1;
	const Receipt = 2;
	const InvoiceReceipt = 3;
	const InvoiceCredit = 4;
	const ProformaInvoice = 5;
	const InvoiceOrder = 6;
	const InvoiceShip = 8;
	const Deposits = 9;
}

abstract class InvoicePaymentType {
	const CreditCard = 1;
	const Check = 2;
	const MoneyTransfer = 3;
	const Cash = 4;
	const Credit = 5;
}

?>