<?php

define ('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
require_once (ROOT_DIR . '/im-config.php');

require_once(ROOT_DIR . '/init.php');
require_once (ROOT_DIR . '/niver/gui/inputs.php');
//
//$s = new Supply(72);
//print $s->Html(true, true, true);

$args = [];
$args["bordercolor"] = "red";

$a = array(array("a", "b"), array("c", "d"));


print gui_table_args($a, "test", $args);