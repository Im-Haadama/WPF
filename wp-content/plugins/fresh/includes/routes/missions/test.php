<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ) );
}
require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

$options = sql_query_array("select * from wp_options where option_name like 'woocommerce_flat_rate_%_settings'");

var_dump($options);

foreach($options as $ship_id)
{
	print $ship_id . "<br/>";
}
// foreach ($options )
// $i->UpdateFromRemote( "wp_options", "option_name", 0, ", array( 'option_id' ), $debug);