<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/11/15
 * Time: 16:46
 */

$operation   = $_GET["operation"];
if ( isset( $_GET["key"] ) ) {
	$auth_key = $_GET["key"];
	require_once( "../im_tools_light.php" );
	if ( ! valid_key( $auth_key ) ) {
		print $auth_key . " key is not valid";
		die ( 1 );
	}
} else {
	require_once( "account.php" );
}
$customer_id = $_GET["customer_id"];
my_log( "operation = " . $operation, __FILE__ );

switch ( $operation ) {
	case "table":
		$table_lines = show_trans( $customer_id );
		print $table_lines;
		break;

	case "total":
		print "יתרה: " . sql_query_single_scalar( "SELECT round(sum(transaction_amount), 1) FROM im_client_accounts WHERE client_id = " . $customer_id );
		break;
}

