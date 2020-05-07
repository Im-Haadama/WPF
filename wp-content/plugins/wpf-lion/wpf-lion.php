<?php
/**
 * Plugin Name: wpf-lion
 * Plugin URI: https://e-fresh.co.il
 * Description: Lion's tools
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 */


add_shortcode( "coupon", 'wpf_get_coupon' );
add_filter('wpcf7_before_send_mail', 'wpf_add_coupon');

//require_once('/var/www/html/wp-content/plugins/flavor/includes/core/fund.php');

function wpf_add_coupon($contact_form)
{
	$form =$contact_form->prop( 'mail' );
	$form['body'] = do_shortcode($form['body']);
//	MyLog($form['body']);

	return $contact_form;
}

	function WpfGetParam( $key, $mandory = false, $default = null ) {
		if ( isset( $_GET[ $key ] ) ) return $_GET[ $key ];

		if ( $mandory ) {
			die ( "Error: " . __FUNCTION__ . " key " . $key . " not supplied" );
		} else {
			return $default;
		}
	}

function wpf_get_coupon() {
	$code = WpfGetParam( "coupon", false, null );
//	print $code;
	if ( $code ) {
		return "קוד ההנחה שלך: $code";
	}
}

