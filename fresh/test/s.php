<?php

define ('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
require_once (ROOT_DIR . '/im-config.php');

require_once(ROOT_DIR . '/init.php');
require_once (ROOT_DIR . '/supplies/Supply.php');

$s = new Supply(72);
print $s->Html(true, true, true);

