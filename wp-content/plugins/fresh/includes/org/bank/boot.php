<?php

// General boot file for top level scripts.
if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES',  dirname(dirname( dirname( __FILE__)  ) ));
}

// Read configuartion
require_once(FRESH_INCLUDES . "/im-config.php"); // requires wp-config.
require_once( FRESH_INCLUDES . "/niver/fund.php" ); // requires wp-config.

// Connect to DB
boot_no_login('im-haadama', 'im-haadama');

// Auth
//if (! get_user_id()) {
//	$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';
//
//	print '<script language="javascript">';
//	print "window.location.href = '" . $url . "'";
//	print '</script>';
//	return;
//}

// Raise reporting.
if (get_user_id() == 1){
	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );
}

// Check general capabilities. More specific should be made in context.
if (! im_user_can("show_bank")) die ("no permissions");