<?php

define ('FRESH_INCLUDES', dirname(dirname(dirname(__FILE__))));
require_once (FRESH_INCLUDES . '/im-config.php');

require_once( FRESH_INCLUDES . '/init.php' );
require_once( FRESH_INCLUDES . '/niver/gui/inputs.php' );
//
//$s = new Supply(72);
//print $s->Html(true, true, true);

$args = [];
$args["bordercolor"] = "red";

$a = array(array("a", "b"), array("c", "d"));


print gui_table_args($a, "test", $args);