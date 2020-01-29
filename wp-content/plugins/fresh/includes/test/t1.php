<?php




if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) )) ;
}
require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . '/fresh/catalog/Basket.php' );

print get_basket_content(1121) . "<br/>";
print get_basket_content(1085) . "<br/>";
print get_basket_content(1118) . "<br/>";