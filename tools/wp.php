<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:20
 */
// require_once( "r-shop_manager.php" );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once(ROOT_DIR . "/wp-includes/load.php");
require_once( ROOT_DIR . "/wp-includes/pluggable.php" );
require_once( ROOT_DIR . "/wp-includes/taxonomy.php" );

// Postmeta table
function get_postmeta_field( $post_id, $field_name ) {
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $post_id
	       . " AND meta_key = '" . $field_name . "'";

	return sql_query_single_scalar( $sql );
}


function set_post_meta_field( $post_id, $field_name, $field_value ) {
	if ( ! add_post_meta( $post_id, $field_name, $field_value, true ) ) {
		update_post_meta( $post_id, $field_name, $field_value );
	}
	// my_log("Error: can't add meta. Post_id=" . $post_id . "Field_name=" . $field_name . "Field_value=" . $field_value, __FILE__);
}

function is_manager() {
	$user    = new WP_User( wp_get_current_user() );
	$manager = false;
	if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
		foreach ( $user->roles as $role ) {
			if ( $role == 'administrator' or $role == 'shop_manager' ) {
				$manager = true;
			}
		}
	}

	return $manager;
}

function is_admin_user() {
	$user    = new WP_User( wp_get_current_user() );
	$manager = false;
	if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
		foreach ( $user->roles as $role ) {
			if ( $role == 'administrator' ) {
				$manager = true;
			}
		}
	}

	return $manager;
}

function greeting()
{
	$data = "";

	$user_id = wp_get_current_user()->ID;

	if (! $user_id) {
		$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';

		print '<script language="javascript">';
		print "window.location.href = '" . $url . "'";
		print '</script>';
		die (1);
	}

	$now = strtotime("now");

	if ($now < strtotime("12pm"))
		$data .= im_translate("Good morning");
	else
		$data .= im_translate("Hello");

	$data .= " " . gui_div("user_id", get_customer_name($user_id), false, $user_id);

	$data .=  ". " . im_translate("the time is:") . Date("G:i", $now ) . ".";

	$data .= "<br/>";

	return $data;
}
