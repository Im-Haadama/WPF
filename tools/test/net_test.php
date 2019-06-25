<?php

require_once( "../im_tools.php" );
// TODO: require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );

$m        = new ImMultiSite();

print $m->GetAll("tools/about.php");

