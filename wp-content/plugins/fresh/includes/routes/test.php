<?php


if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );
require_once( FRESH_INCLUDES . '/routes/missions/Mission.php' );

print header_text(true, false, true, true);

$m = new Mission(720);


$time = $m->getStart();

print date('d/m H:i', $time);
//print $time . "<br/>";
//
//print gui_input_time("ss", "", $time);