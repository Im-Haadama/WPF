<?php

require '../r-shop_manager.php';
require_once( FRESH_INCLUDES . "/fresh/supplies/Supply.php" );
require_once( FRESH_INCLUDES . '/niver/gui/inputs.php' );
require_once( "../account/gui.php" );
require_once( "../catalog/gui.php" );

$s = new Supply(941);

 print $s->HtmlLines(true);

//$a = false;
//$b = 1;
//$c = false;
//
//print ($a or $b or $c);