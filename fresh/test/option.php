<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}
require_once(ROOT_DIR . '/im-config.php');

require_once (ROOT_DIR . '/niver/wp.php');
require_once(ROOT_DIR . "/init.php" );

init();

$p = get_wp_option('woocommerce_flat_rate_40_settings');

var_dump($p);