<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

init();

require_once( "../load.php" );
require_once(ROOT_DIR . '/niver/fund.php');
require_once(ROOT_DIR . '/niver/gui/gem.php');

print header_text(true, true, is_rtl());
print load_scripts(array( "/niver/gui/client_tools.js", "/niver/data/data.js" ));

$args["edit"] = 1;
print GemTable("im_payments", $args);
