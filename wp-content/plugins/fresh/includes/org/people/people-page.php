<?php

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

 require_once( FRESH_INCLUDES . '/im-config.php' );
 require_once( FRESH_INCLUDES . "/init.php" );
 require_once( FRESH_INCLUDES . "/niver/fund.php" );
 require_once( "people.php" );

$operation = get_param("operation", false, "salary_report");

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