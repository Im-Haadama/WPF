<?php




if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname( dirname( __FILE__)  ) );
}

require_once(ROOT_DIR . "/im-config.php"); // requires wp-config.
require_once( ROOT_DIR . "/core/fund.php" ); // requires wp-config.

boot_no_login();

//if (! get_user_id()) {
//	$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';
//
//	print '<script language="javascript">';
//	print "window.location.href = '" . $url . "'";
//	print '</script>';
//	return;
//}
//
//
