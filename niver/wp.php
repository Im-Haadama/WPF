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

$no_wp = 0;

require_once( ROOT_DIR . "/wp-includes/load.php" );
require_once( ROOT_DIR . "/wp-includes/pluggable.php" );
require_once( ROOT_DIR . "/wp-includes/taxonomy.php" );
require_once (ROOT_DIR . '/niver/data/translate.php');
require_once (ROOT_DIR . '/niver/gui/inputs.php');

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
//	global $no_wp;
	$data = "";

//	if ($no_wp) $user_id =1;
//	else $user_id = 1; // FOR DEBUG wp_get_current_user()->ID;
	$user_id = get_user_id();

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

	$data .=  ". " . im_translate("the time is:") . " " . Date("G:i", $now ) . ".";

	$data .= gui_hyperlink("logout", get_param(1) . "?operation=logout&back=" . encodeURIComponent(get_url()));

	$data .= "<br/>";

	return $data;
}

function get_customer_name( $customer_id ) {
	static $min_supplier = 0;
	if ( ! $min_supplier ) {
		if (table_exists("im_suppliers"))
			$min_supplier = sql_query_single_scalar( "SELECT min(id) FROM im_suppliers" );
		else
			$min_supplier = 1000000;
	}

	if ( $customer_id < $min_supplier ) {
		$user = get_user_by( "id", $customer_id );

		if ( $user ) {
			return $user->user_firstname . " " . $user->user_lastname;
		}

		return "לא נבחר לקוח";
	}

	return get_supplier_name( $customer_id );
}

function get_user_id() {
	if (function_exists('wp_get_current_user'))
		$current_user = wp_get_current_user();
	else
		return 0;

	return $current_user->ID;
}

function im_user_can( $permission ) {
	global $no_wp;
	if ($no_wp) return true; // For debugging
	return ( user_can( login_id(), $permission ) );
}

function login_id() {
	$user = wp_get_current_user();
	if ( $user->ID == "0" ) {
		// Force login
//		$inclued_files = get_included_files();
//		var_dump( $inclued_files );
//		my_log( __FILE__, $inclued_files[ count( $inclued_files ) - 2 ] );
		$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';
//		die( 1 );

		print '<script language="javascript">';
		print "window.location.href = '" . $url . "'";
		print '</script>';
		print $_SERVER['REMOTE_ADDR'] . "<br/>";
		var_dump( $user );
		exit();
	}

	return $user->ID;
}


function add_im_user( $user, $name, $email, $address = null, $city = null, $phone = null, $zip = null)
{
	if ( strlen( $email ) < 1 ) {
		$email = randomPassword() . "@aglamaz.com";
	}

	if ( $user == "אוטומטי" or strlen( $user ) < 5 ) {
		$user = substr( $email, 0, 8 );
		print "user: " . $user . "<br/>";
	}

	print "email: " . $email . "<br/>";
	print "user: " . $user . "<br/>";

	$id = wp_create_user( $user, randomPassword(), $email );
	if ( ! is_numeric( $id ) ) {
		print "לא מצליח להגדיר יוזר";
		var_dump( $id );

		return;
	}
	$name_part = explode( " ", $name );
	update_user_meta( $id, 'first_name', $name_part[0] );
	update_user_meta( $id, 'shipping_first_name', $name_part[0] );
	unset( $name_part[0] );

	if ($address) {
		update_user_meta( $id, 'billing_address_1', $address );
		update_user_meta( $id, 'shipping_address_1', $address );
	}
	if ($city) {
		update_user_meta( $id, 'billing_city', $city );
		update_user_meta( $id, 'shipping_city', $city );
	}

	update_user_meta( $id, 'last_name', implode( " ", $name_part ) );
	update_user_meta( $id, 'shipping_last_name', implode( " ", $name_part ) );
	if ($phone) update_user_meta( $id, 'billing_phone', $phone );
	if ($zip) {
		update_user_meta( $id, 'billing_postcode', $zip );
		update_user_meta( $id, 'shipping_postcode', $zip );
	}

	im_set_default_display_name( $id);
	print "משתמש התווסף בהצלחה";

	return $id;

}
