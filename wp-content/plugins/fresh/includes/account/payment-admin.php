<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

init();

require_once( "../load.php" );
require_once( FRESH_INCLUDES . '/core/fund.php' );
require_once( FRESH_INCLUDES . '/core/gui/gem.php' );

print header_text(true, true, is_rtl());
print load_scripts(array( "/core/gui/client_tools.js", "/core/data/data.js" ));

$args["edit"] = 1;
print GemTable("im_payments", $args);
