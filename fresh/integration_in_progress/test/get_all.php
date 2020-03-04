<?php

require_once( "../r-shop_manager.php" );

// require_once(TOOLS_DIR . '/fresh/multi-site/imMulti-site.php');

$m        = new Core_Db_MultiSite();

print $m->GetAll("about.php");