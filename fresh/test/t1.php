<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) )) ;
}
require_once(ROOT_DIR . '/im-config.php');
require_once(ROOT_DIR . '/fresh/catalog/Basket.php');

print get_basket_content(1121) . "<br/>";
print get_basket_content(1085) . "<br/>";
print get_basket_content(1118) . "<br/>";