<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname( dirname( __FILE__)  ) );
}

require_once(ROOT_DIR . "/im-config.php"); // requires wp-config.
require_once( ROOT_DIR . "/niver/fund.php" ); // requires wp-config.

boot_no_login();
