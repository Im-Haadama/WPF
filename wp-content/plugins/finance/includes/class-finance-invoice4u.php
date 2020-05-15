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
	static public function getInstance(): Finance_Invoice4u {
		if (! self::$instance) throw new Exception("no instance");
		return self::$instance;
	}


	// public $doc;

	public function Login() {
		if ( ! $this->token ) {
			self::DoLogin( $this->user, $this->password );
			if ( ! $this->token ) {
				throw new Exception( "Can't login" );
			}
		}
	}

	private function DoLogin( $invoice_user, $invoice_password ) {
		MyLog("Invoice4u Login");
//		if (get_user_id() == 1) print $invoice_user . " " .$invoice_password ."<br/>";
//		MyLog($invoice_user . " " . $invoice_password);
		$wsdl = ApiService . "/LoginService.svc?wsdl";
		$user = array( 'username' => $invoice_user, 'password' => $invoice_password, 'isPersistent' => false );

		$this->requestWS( $wsdl, "VerifyLogin", $user );
		$this->token = $this->result;
//		MyLog($this->token);
	}

	private function requestWS( $wsdl, $service, $params )
	{
		MyLog(__FUNCTION__, $service, 'invoice4u.log');
		if (! defined('WSDL_CACHE_NONE')) return null;
		try {
			$options = array(
				'trace'              => true,
				'exceptions'         => true,
				'cache_wsdl'         => WSDL_CACHE_NONE,
				'connection_timeout' => 10
			);

			$client = new SoapClient( $wsdl, $options );

			// var_dump($client->__getTypes()); //exit;

			$response = $client->$service( $params );

			$service = $service . "Result";

			$this->result = $response->$service;

			// print $response->$service;
			MyLog("done", "Invoice4u", 'invoice4u.log');

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

	public function CreateDocument( $doc ) {
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
			foreach ( $doc->Items as $item ) {
				print $item->Name . " " . $item->Quantity . " " . $item->Price . " " . $item->Total . "<br/>";
			}
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
//				print $pay->Type . " " . $item->Total . "<br/>";
				$pay_total += $pay->Amount;
				var_dump( $pay );
			}

			print "errors: ";
			var_dump( $this->result->Errors );
			if ( $check_total != $check_total2 or $check_total != $pay_total ) {
				print "calculated total: " . $check_total . "<br/>";
				print "calculated total2: " . $check_total2 . "<br/>";
				print "total paid: " . $pay_total . "<br/>";
			}
			print "client id: " . $doc->Id . "<br/>";
			var_dump( $doc );
		}

		return $docNum;
	}

//    public function CreateDocument()
//    {
//        $wsdl = "http://private.invoice4u examples.co.il/Services/DocumentService.svc?wsdl";
//
//        $this->result = $this->requestWS($wsdl, "CreateDocument", array('doc' => $this->doc, 'token' => $this->token));
//    }

	public function GetInvoiceUserId( $customer_id, $client_email = null) {
		if ( ! ( $customer_id > 0 ) ) {
			throw new Exception( "Bad customer id " . __CLASS__ . " " . $customer_id );
		}

		// Try local cache
		$id = get_user_meta( $customer_id, 'invoice_id', 1 );

		if (is_numeric($id) and ($id > 0)) return $id;

		// Try email
		MyLog( "performance - searching customer by email $client_email", __METHOD__ );
		$client = $this->GetCustomerByEmail( $client_email );

		if ( ! isset( $client->ID ) ) return null;

		// if found, save
		if (is_numeric($client->ID) and ($client->ID > 0))
			update_user_meta( $customer_id, 'invoice_id', $client->ID );
		return $client->ID;
	}

	public function GetCustomerByEmail( $email ) {
		$wsdl = ApiService . "/CustomerService.svc?wsdl";

		$cust        = new InvoiceCustomer( "" );
		$cust->Email = $email;
		// print $email;
		$response = $this->requestWS( $wsdl, "GetCustomers", array(
			'cust'       => $cust,
			'token'      => $this->token,
			'getAllRows' => false
		) );
		if ( ! isset( $response->Response->Customer ) ) {
			return null;
		}
		$customer = $response->Response->Customer;
		if ( isset($customer->Email)) return $customer;
		return null;
	}

	public function GetCustomerByName( $name ) {
		$wsdl = ApiService . "/CustomerService.svc?wsdl";

		$this->result = $this->requestWS( $wsdl, "GetByName", array( 'name' => $name, 'token' => $this->token ) );

		return $this->result;
	}

	public function GetCustomerById( $id ) {
		$wsdl = ApiService . "/CustomerService.svc?wsdl";

		$cust = new InvoiceCustomer( "" );

		$response  = $this->requestWS( $wsdl, "GetCustomers", array(
			'cust'       => $cust,
			'token'      => $this->token,
			'getAllRows' => true
		) );

		$customers = $response->Response->Customer;

		if ( $customers ) {
			foreach ( $customers as $customer ) {
				// var_dump($customer);die(1);
				//	print $customer->ID . "<br/>";
				if ( $customer->ID == $id ) {
					// print "found<br/>";
					return $customer;
				}
			}
		}

		return null;
	}

	public function CreateUser( $user_id, $name, $email, $phone )
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

		return true;
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