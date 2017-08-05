<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/08/16
 * Time: 06:25
 */
class Customer {
	public $Name;
	public $Active = true; // must be unique
	public $Email; // important
	public $Phone;

	public function __construct( $name ) {
		$this->Name = $name;
	}

}

class Invoice4u {
	public $token;
	public $result;

	// public $doc;

	public function Login() {
		global $invoice_user;
		global $invoice_password;
		$wsdl = "http://private.invoice4u.co.il/Services/LoginService.svc?wsdl";
		$user = array( 'username' => $invoice_user, 'password' => $invoice_password, 'isPersistent' => false );
		// $user = array('username' => 'Test@test.com', 'password' => '123456', 'isPersistent' => false);

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

			// var_dump($client->__getTypes()); //exit;

			$response = $client->$service( $params );

			$service = $service . "Result";

			$this->result = $response->$service;

			// print $response->$service;

			return $this->result;
		} catch ( SoapFault $f ) {
			echo $f->faultstring;

			return $f->faultstring;
		} catch ( Exception $e ) {
			echo $e->getMessage();

			return $e->getMessage();
		}
	}

	public function CreateDocument( $doc ) {
		$wsdl = "http://private.invoice4u.co.il/Services/DocumentService.svc?wsdl";

		$this->result = $this->requestWS( $wsdl, "CreateDocument", array( 'doc' => $doc, 'token' => $this->token ) );

		//if (strlen($this->result->Error) > 0)
		//    die ($this->result->Error);
		// foreach ($this->result->Errors as $err)
		//    print $err;
		//print $this->result->Errors->Error;
//        var_dump($this->result);
		$docNum = $this->result->DocumentNumber;
		if ( $docNum == 0 ) {
			var_dump( $doc );
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
				print $pay->Type . " " . $item->Total . "<br/>";
				$pay_total += $pay->Amount;
				var_dump( $pay );
			}

			print "errors: ";
			var_dump( $this->result->Errors );
			print "calculated total: " . $check_total . "<br/>";
			print "calculated total2: " . $check_total2 . "<br/>";
			print "total paid: " . $pay_total . "<br/>";
		}

		return $docNum;
	}

//    public function CreateDocument()
//    {
//        $wsdl = "http://private.invoice4u examples.co.il/Services/DocumentService.svc?wsdl";
//
//        $this->result = $this->requestWS($wsdl, "CreateDocument", array('doc' => $this->doc, 'token' => $this->token));
//    }

	public function GetCustomerByName( $name ) {
		$wsdl = "http://private.invoice4u.co.il/Services/CustomerService.svc?wsdl";

		$this->result = $this->requestWS( $wsdl, "GetByName", array( 'name' => $name, 'token' => $this->token ) );

		return $this->result;
	}

	public function CreateUser( $name, $email, $phone ) {
		$wsdl = "http://private.invoice4u.co.il/Services/CustomerService.svc?wsdl";

		$customer        = new Customer( $name );
		$customer->Email = $email;
		$customer->Phone = $phone;
		$this->result    = $this->requestWS( $wsdl, "Create",
			array( 'customer' => $customer, 'token' => $this->token ) );

		if ( ! $this->result->Errors ) {
			my_log( __METHOD__, $this->result->Errors );
		}
//        var_dump($this->result);
	}
}

// we need to send this class to the service
class Document {
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
		$this->Discount  = new Discount;
		// $this->Items = array(new Item());
		$this->AssociatedEmails; // = array(new Email());
		$this->Payments = array();
	}

}

class Discount {
	public $Value = 0;
	public $BeforeTax = true;// discount calculated before tax calculated
	public $IsNominal = true;// means that the discount is calculated according to currency type (in this case: 5 ILS), if it is false than discount calculated in percentages (in this case: 5%)
}


class Item {
	public $Code; // = "001";
	public $Name; // = "first Item";
	public $Quantity; // = 1;
	public $Price; // = 100;
	public $Total; // = 100;
	public $TotalWithoutTax;
	public $TaxPercentage; // in case you want an item without tax
	public $Discount;

	public function __construct() {
		$this->Discount = new Discount;
	}
}

class PaymentCash {
	public $Date;
	public $Amount;
	public $PaymentType = 4; // Cash

	public function __construct() {
		$this->Date = date( "c", time() );
	}
}

class PaymentCredit {
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

class PaymentBank {
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

class PaymentCheck {
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


class Email {
	public $Mail; // = "yaakov@im-haadama.co.il";
	public $IsUserMail = false;// means that this is the user email, and this is the email that sends the document to all associated email
}

?>