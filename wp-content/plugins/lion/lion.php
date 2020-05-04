<?php
/**
 * Plugin Name: lion
 * Plugin URI: https://e-fresh.co.il
 * Description: Lion's tools
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 */


add_shortcode( "coupon", 'get_coupon' );

function GetParam( $key, $mandory = false, $default = null ) {
	if ( isset( $_GET[ $key ] ) ) {
		return $_GET[ $key ];
	}

	if ( $mandory ) {
		die ( "Error: " . __FUNCTION__ . " key " . $key . " not supplied" );
	} else {
		return $default;
	}
}

function get_coupon()
{
	$code = GetParam("coupon", false);

	if ($code) print "קוד ההנחה שלך: $code";
}