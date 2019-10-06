<?php


if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );
require_once(ROOT_DIR . '/routes/missions/Mission.php');

print header_text(true, false, true, true);

$m = new Mission(720);


$time = $m->getStart();

print date('d/m H:i', $time);
//print $time . "<br/>";
//
//print gui_input_time("ss", "", $time);