<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 19/02/19
 * Time: 19:08
 */

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/im_tools.php' );
require_once( TOOLS_DIR . '/account/account.php' );

$date  = date( 'Y-m-d', strtotime( "last day of this month" ) );
$month = date( 'Ym', strtotime( "last day of this month" ) );

// Add to account -390 NIS a mount.
$sql = "SELECT count(*) FROM im_client_accounts \n" .
       "WHERE client_id = 24 \n" .
       "AND transaction_method LIKE 'דמי שימוש%'\n" .
       "AND date = '" . $date . "'";

//print $sql;

$c = sql_query_single_scalar( $sql );

//print $c;

if ( ! $c ) {
	account_add_transaction( 24, $date, - 390, $month, "דמי שימוש" );
	print "added";
}

