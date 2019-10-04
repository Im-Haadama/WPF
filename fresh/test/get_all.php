<?php

require_once("../r-shop_manager.php");

// require_once(TOOLS_DIR . '/fresh/multi-site/imMulti-site.php');

$m        = new ImMultiSite();

print $m->GetAll("about.php");