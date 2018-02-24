<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/11/15
 * Time: 16:46
 */

require_once( "account.php" );

$operation   = $_GET["operation"];
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

