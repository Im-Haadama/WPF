<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/09/17
 * Time: 20:24
 */

require_once( "pay_config.php" );
if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
} else {
	print "Bad usage";
	die( 1 );
}

function arg( $name ) {
	if ( isset( $_GET[ $name ] ) ) {
		return $_GET[ $name ];
	} else {
		print "argument $name is missing";
		die ( 2 );
	}
}

switch ( $operation ) {
	case "pay":
		print "a";
		$amount      = arg( "amount" ) * 100;
		$card_number = arg( "card_number" );
		$card_date   = arg( "card_date" );
		$id          = arg( "id" );
		do_pay( $card_number, $id, $card_date, $amount );
		print "b";
		break;
	default:
		print "operation $operation not handled";
		die( 3 );
}

function do_pay( $card_number, $id, $card_date, $caamount ) {
	global $pay_user, $pay_password, $pay_tid;
	$poststring = 'user=' . $pay_user;
	$poststring .= '&password=' . $pay_password;

	/*Build Ashrait XML to post*/
	$poststring .= "&int_in=<ashrait>
							<request>
							<language>ENG</language>
							<command>doDeal</command>
							<requestId/>
							<version>1000</version>
							<doDeal>
								<terminalNumber>" . $pay_tid . "</terminalNumber>
								<authNumber/>
								<transactionCode>Phone</transactionCode>
								<transactionType>Debit</transactionType>
								<total>" . $amount . "</total>
								<creditType>RegularCredit</creditType>
								<cardNo>$card_number</cardNo>
								<cvv>123</cvv>
								<cardExpiration>$card_date</cardExpiration>
								<validation>AutoComm</validation>
								<numberOfPayments/>
								<customerData>
									<userData1/>
									<userData2/>
									<userData3/>
									<userData4/>
									<userData5/>
								</customerData>
								<currency>ILS</currency>
								<firstPayment/>
								<id>$id</id>
								<periodicalPayment/>
								<user>בדיקה</user>
							</doDeal>
						</request>
					</ashrait>";

//print urlencode($poststring);
//init curl connection
	if ( function_exists( "curl_init" ) ) {
		$CR = curl_init();
		curl_setopt( $CR, CURLOPT_URL, $pay_relay );
		curl_setopt( $CR, CURLOPT_POST, 1 );
		curl_setopt( $CR, CURLOPT_FAILONERROR, true );
		curl_setopt( $CR, CURLOPT_POSTFIELDS, $poststring );
		curl_setopt( $CR, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $CR, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $CR, CURLOPT_FAILONERROR, true );


		//actual curl execution perfom
		$result = curl_exec( $CR );
		$error  = curl_error( $CR );

		// on error - die with error message
		if ( ! empty( $error ) ) {
			die( $error );
		}

		curl_close( $CR );
	}

	if ( function_exists( "simplexml_load_string" ) ) {
		if ( strpos( strtoupper( $result ), 'HEB' ) ) {
			$result = iconv( "utf-8", "iso-8859-8", $result );
		}
		$xmlObj = simplexml_load_string( $result );
		if ( isset( $xmlObj->response->doDeal->status ) ) {
			// print out the url which we should redirect our customers to
			echo "result: " . $xmlObj->response->doDeal->status . "<br/>";
			echo "status text:" . $xmlObj->response->doDeal->statusText . "<br/>";
		} else {
			die( '<strong>Can\'t Create Transaction</strong> <br />' .
			     'Error Code: ' . $xmlObj->response->result . '<br />' .
			     'Message: ' . $xmlObj->response->message . '<br />' .
			     'Addition Info: ' . $xmlObj->response->additionalInfo );
		}
	} else {
		die( "simplexml_load_string function is not support, upgrade PHP version!" );
	}
}
//TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384,
//TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256,
//TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA384,
//TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA,
//TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA256,
//TLS_ECDHE_RSA_WITH_3DES_EDE_CBC_SHA,
//TLS_RSA_WITH_3DES_EDE_CBC_SHA,
//TLS_RSA_WITH_AES_256_GCM_SHA384,
//TLS_RSA_WITH_AES_128_GCM_SHA256,
//TLS_RSA_WITH_AES_256_CBC_SHA256,
//TLS_RSA_WITH_AES_256_CBC_SHA,
//TLS_RSA_WITH_AES_128_CBC_SHA256,
//TLS_RSA_WITH_AES_128_CBC_SHA

