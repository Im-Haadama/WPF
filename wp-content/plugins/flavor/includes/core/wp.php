<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:20
 */

$no_wp = 0;

if (! function_exists( 'GetUserName' )) {
// Postmeta table
/**
 * @param $post_id
 * @param $field_name
 *
 * @return string
 */
function get_postmeta_field( $post_id, $field_name ) {
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $post_id
	       . " AND meta_key = '" . $field_name . "'";

	return sql_query_single_scalar( $sql );
}


/**
 * @param $post_id
 * @param $field_name
 * @param $field_value
 */
function set_post_meta_field( $post_id, $field_name, $field_value ) {
	if ( ! add_post_meta( $post_id, $field_name, $field_value, true ) ) {
		update_post_meta( $post_id, $field_name, $field_value );
	}
	// my_log("Error: can't add meta. Post_id=" . $post_id . "Field_name=" . $field_name . "Field_value=" . $field_value, __FILE__);
}

/**
 * @return bool
 */
function is_manager()
{
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

/**
 * @return bool
 */
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

/**
 * @param null $args
 *
 * @return string
 */
function greeting( $args = null, $force_login = false )
{
	$extra_text = GetArg($args, "greeting_extra_text", null);
	$viewing_as = GetArg($args, "view_as", get_user_id());

//	global $no_wp;
	$data = "";

//	if ($no_wp) $user_id =1;
//	else $user_id = 1; // FOR DEBUG wp_get_current_user()->ID;
	$user_id = get_user_id($force_login);

	if (! $user_id and $force_login) {
		print auth_redirect();
		die (1);
	}

	$now = strtotime("now");

//	if ($now < strtotime("12pm"))
//		$data .= im_translate("Good morning");
//	else
//		$data .= im_translate("Hello");

//	$data .= " " . gui_div("user_id", get_customer_name($user_id), false, $user_id);
	$data .= get_avatar( get_user_id(), 40 ) . " " . get_customer_name($user_id) . Core_Html::Br() . GuiHyperlink("logout", wp_logout_url(get_permalink()));
	if ($viewing_as != $user_id) $data .= "( " . ImTranslate("viewing as") . get_customer_name($viewing_as) . ")";

	$data .=  Date("G:i", $now );

	// Would go to dropdown.
	// $data .= Core_Html::GuiHyperlink("logout", get_param(1) . "?operation=logout&back=" . encodeURIComponent(get_url()));

	if ($extra_text) $data .= Core_Html::Br() . $extra_text;

	return $data;
}

/**
 * @param $customer_id
 *
 * @return int|string
 */
function get_customer_name( $customer_id )
{
	static $min_supplier = 0;
	if ( ! $min_supplier ) {
		if (table_exists("suppliers"))
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

/**
 * @param $permission
 *
 * @return bool
 */
function im_user_can( $permission ) {
	global $no_wp;
	if ($no_wp) return true; // For debugging
	$user_id = get_current_user_id();
	if (! $user_id) return false;
	return ( user_can( $user_id, $permission ) );
}

/**
 * @param $user
 * @param $name
 * @param $email
 * @param null $address
 * @param null $city
 * @param null $phone
 * @param null $zip
 */
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

function delete_wp_option($option_id)
{
	if (! $option_id) return false;
	$sql = "delete from wp_options where option_name='$option_id'";
//	print $sql . "<br/>";
	return sql_query($sql);
}

function get_wp_option($option_id, $default = null)
{
	$string = sql_query_single_scalar("select option_value from wp_options where option_name = '" . $option_id . "'");
	if (! $string) return $default;
	return maybe_unserialize($string);
}

function update_wp_option($option_id, $array_or_string)
{
//	var_dump($array_or_string);
	if (is_array($array_or_string))
		$sql = sprintf("insert into wp_options (option_name, option_value) values ('%s', '%s')" .
		               " on duplicate key update option_value = VALUES(option_value)",
			$option_id, escape_string(serialize($array_or_string)));
	else
		$sql = sprintf("insert into wp_options (option_name, option_value) values ('%s', '%s')" .
		               " on duplicate key update option_value = VALUES(option_value)",
			$option_id, escape_string($array_or_string));

    return sql_query($sql);
//	$new_string = serialize($new_array);
//	return sql_query("update wp_options set option_value = '" . escape_string($new_string) . "' where option_name = '" . $option_id . "'");
}

function GetUserName( $id ) {
//    var_dump(get_user_meta($id, 'first_name'));
	$result = (get_user_meta( $id, 'first_name' )[0] . " " . get_user_meta( $id, 'last_name' )[0]);
	if (strlen ($result) > 1) return $result;
	if (is_array($id)){
		var_dump($id);
		print __FUNCTION__ . ': bad id';
		return;
	}
	return "user $id";
}
	/**
	 * @param bool $force_login
	 *
	 * @return int
	 */


}
