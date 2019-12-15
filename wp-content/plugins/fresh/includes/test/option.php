<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}
require_once(FRESH_INCLUDES . '/im-config.php');

require_once( FRESH_INCLUDES . '/niver/wp.php' );
require_once( FRESH_INCLUDES . "/init.php" );

init();

$p = get_wp_option('woocommerce_flat_rate_40_settings');

var_dump($p);