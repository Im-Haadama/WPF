<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/01/19
 * Time: 16:44
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

require_once( ROOT_DIR . '/niver/PivotTable.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/tools/im_tools_light.php' );

$year = get_param( "year" );
if ( ! $year ) {
	$year = date( "Y" );
}
// $month = get_param("monty");

print gui_hyperlink( "שנה קודמת", "pivot_test.php?year=" . ( $year - 1 ) );

$t = new \Niver\PivotTable( "im_business_info", "EXTRACT(YEAR FROM DATE) = " . $year . " and document_type = 4 and is_active=1",
	"EXTRACT(MONTH FROM DATE)", "part_id", "net_amount" );

$trans            = array();
$trans["part_id"] = 'get_customer_name';
print gui_table( $t->Create(
	'c-get-business_info.php?document_type=4&part_id=%s&date=' . $year . '-' . '%02s-28',
	'c-get-all-business_info.php?part_id=%s',
	$trans ) );