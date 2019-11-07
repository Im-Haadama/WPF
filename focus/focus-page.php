<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}
require_once(ROOT_DIR . '/im-config.php');
require_once(ROOT_DIR . "/init.php" );
require_once(ROOT_DIR . "/focus/gui.php");
require_once(ROOT_DIR . "/org/gui.php");
require_once(ROOT_DIR . "/routes/gui.php");

if (! get_user_id()) {
	$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';

	print '<script language="javascript">';
	print "window.location.href = '" . $url . "'";
	print '</script>';
	return;
}

$debug = get_param("debug", false, false);

global $style_file;
init(null, $style_file);

//$args = [];
//$args["script_files"] = array("/niver/data/data.js", "/niver/gui/client_tools.js");
//print HeaderText($args);
require_once( "focus.php" );
$operation = get_param("operation", false, "focus_main");

if ($debug) print "op=$operation<br/>";

if (! $operation and get_param("id", false, null)) $operation = "show_task";

if ($operation) {
	handle_focus_operation($operation);
	return;
}

