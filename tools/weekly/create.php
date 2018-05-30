<?php

require_once( '../r-shop_manager.php' );
require_once( '../multi-site/multi-site.php' );
require_once( '../orders/orders-common.php' );
require_once( '../supplies/supplies.php' );
require_once( '../pricelist/pricelist.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( "../delivery/missions.php" );
require_once( "start.php" );

print header_text();


create_missions();