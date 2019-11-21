<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

 require_once( ROOT_DIR . '/im-config.php' );
 require_once( ROOT_DIR . "/init.php" );
 require_once( ROOT_DIR . "/niver/fund.php" );
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

handle_people_operation($operation);


