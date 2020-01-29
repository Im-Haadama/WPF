<?php



if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}
require_once(FRESH_INCLUDES . '/im-config.php');

require_once( FRESH_INCLUDES . '/core/wp.php' );
require_once( FRESH_INCLUDES . "/init.php" );

init();

$p = get_wp_option('woocommerce_flat_rate_40_settings');

var_dump($p);