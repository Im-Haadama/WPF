<?php

// Get from different site
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'FRESH_INCLUDES' ) ) define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));

require_once( FRESH_INCLUDES . "/im-config.php" ); // requires wp-config.
require_once( FRESH_INCLUDES . "/core/fund.php" ); // requires wp-config.
require_once( FRESH_INCLUDES . "/fresh/account/account.php" );
require_once( FRESH_INCLUDES . '/fresh/invoice4u/invoice.php' );

boot_no_login('im-haadama', 'im-haadama');

if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="My Realm"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Unauthorized';
	exit;
}
$user =  $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];
if (! check_password($user, $password)){
	die("invalid password");
}

// check user/password
$operation = GetParam("operation", true);

handle_multi_operation($operation);

function handle_multi_operation($operation)
{
	switch ($operation)
	{
		case "create_receipt":
			MyLog( "create_receipt" );
			$cash         = GetParam( "cash" );
			$bank         = GetParam( "bank" );
			$check        = GetParam( "check" );
			$credit       = GetParam( "credit" );
			$change       = GetParam( "change" );
			$row_ids      = GetParamArray( "row_ids" );
			$user_id      = GetParam( "user_id", true );
			$date         = GetParam( "date" );

			//print "create receipt<br/>";
			// (NULL, '709.6', NULL, NULL, '205.44', '', '2019-01-22', Array)
			if (create_receipt( $cash, $bank, $check, $credit, $change, $user_id, $date, $row_ids ))
				print "done";
			break;

		case "get_open_trans":
			$client_id = GetParam( "client_id" );
			print show_trans( $client_id, eTransview::not_paid );
			break;
	}
}