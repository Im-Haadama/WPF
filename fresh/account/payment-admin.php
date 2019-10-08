<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "../load.php" );
require_once(ROOT_DIR . '/niver/fund.php');
require_once(ROOT_DIR . '/niver/gui/gem.php');


print header_text(true, true, is_rtl());
print load_scripts(array( "/niver/gui/client_tools.js", "/fresh/admin/data.js", "/fresh/admin/admin.js" ));

$args["edit"] = 1;
print GemTable("im_payments", $args);
