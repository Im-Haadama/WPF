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
$operation = get_param("operation", true);

handle_multi_operation($operation);

function handle_multi_operation($operation)
{
	switch ($operation)
	{
		case "create_receipt":
			my_log( "create_receipt" );
			$cash         = get_param( "cash" );
			$bank         = get_param( "bank" );
			$check        = get_param( "check" );
			$credit       = get_param( "credit" );
			$change       = get_param( "change" );
			$row_ids      = get_param_array( "row_ids" );
			$user_id      = get_param( "user_id", true );
			$date         = get_param( "date" );

			//print "create receipt<br/>";
			// (NULL, '709.6', NULL, NULL, '205.44', '', '2019-01-22', Array)
			if (create_receipt( $cash, $bank, $check, $credit, $change, $user_id, $date, $row_ids ))
				print "done";
			break;

		case "get_open_trans":
			$client_id = get_param( "client_id" );
			print show_trans( $client_id, eTransview::not_paid );
			break;
	}
}