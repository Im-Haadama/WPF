<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

// require_once ("../../focus/gui.php");
if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(ROOT_DIR . '/im-config.php');

require_once(ROOT_DIR . "/init.php" );

init();


require_once(ROOT_DIR . '/niver/gui/inputs.php');

$args = [];
$args["edit"] = true;
print gui_select_days("day", null, $args);
?>
