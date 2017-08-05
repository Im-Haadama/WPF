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

$table_lines = show_trans( $customer_id );

switch ( $operation ) {
	case "table":
		print $table_lines;
		break;

	case "total":
		print "יתרה: " . $total;
		break;
}

