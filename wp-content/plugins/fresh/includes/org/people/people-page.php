<?php

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}



 require_once( FRESH_INCLUDES . '/im-config.php' );
 require_once( FRESH_INCLUDES . "/init.php" );
 require_once( FRESH_INCLUDES . "/core/fund.php" );
 require_once( "people.php" );

$operation = GetParam("operation", false, "salary_report");

//$month = null;
//$year  = null;
//if ( isset( $_GET["month"] ) ) {
//	$m     = $_GET["month"];
//	$month = substr( $m, 5 );
//	$year  = substr( $m, 0, 4 );
//
//}


if (handle_people_do($operation) !== "not handled") return;

print handle_people_operation($operation);