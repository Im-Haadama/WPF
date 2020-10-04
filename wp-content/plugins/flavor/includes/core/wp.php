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
	if (is_array($post_id)) $post_id = $post_id[0];
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $post_id
	       . " AND meta_key = '" . $field_name . "'";

	return SqlQuerySingleScalar( $sql );
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
	//$customer_name = GetArg($args, "view_as", get_customer_name($user_id));

//	global $no_wp;
	$data = "";

//	if ($no_wp) $user_id =1;
//	else $user_id = 1; // FOR DEBUG wp_get_current_user()->ID;
	$user_id = get_user_id($force_login);

	if (! $user_id and $force_login) {
		print auth_redirect();
		die (1);
	}

	if (date_i18n("H") < 12)
		$day_part = __("Good morning");
	else
		if (date_i18n("H") < 18)
			$day_part = __("Good afternoon");
		else $day_part = __("Good evening");

//	if ($now < strtotime("12pm"))
//		$data .= im_translate("Good morning");
//	else
//		$data .= im_translate("Hello");

//	$data .= " " . gui_div("user_id", get_customer_name($user_id), false, $user_id);
	$user_display = "";
	$user = get_user_by( "id", $user_id );
	if ( $user ) $user_display = $user->user_firstname . " " . $user->user_lastname;

	$data .= get_avatar( get_user_id(), 40 ) . " " . __("Hello") . " <b>".get_user_displayname($user_id) . "</b> $day_part ";

	//. GuiHyperlink("logout", wp_logout_url(get_permalink()));
	//get_customer_name($user_id)
	//if ($viewing_as != $user_id) $data .= "( " . ImTranslate("viewing as") . get_customer_name($viewing_as) . ")";

    //show the time
    // date_default_timezone_set('Asia/Jerusalem'); The time zone should be set generally in wordpress. See wordpress settings.
	$data .= date_i18n("H:i" ) . Core_Html::Br();
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
	return SqlQuery($sql);
}

function get_wp_option($option_id, $default = null)
{
	$string = SqlQuerySingleScalar( "select option_value from wp_options where option_name = '" . $option_id . "'");
	if (! $string) return $default;
	return maybe_unserialize($string);
}

function update_wp_option($option_id, $array_or_string)
{
//	var_dump($array_or_string);
	if (is_array($array_or_string))
		$sql = sprintf("insert into wp_options (option_name, option_value) values ('%s', '%s')" .
		               " on duplicate key update option_value = VALUES(option_value)",
			$option_id, EscapeString(serialize($array_or_string)));
	else
		$sql = sprintf("insert into wp_options (option_name, option_value) values ('%s', '%s')" .
		               " on duplicate key update option_value = VALUES(option_value)",
			$option_id, EscapeString($array_or_string));

    return SqlQuery($sql);
//	$new_string = serialize($new_array);
//	return sql_query("update wp_options set option_value = '" . escape_string($new_string) . "' where option_name = '" . $option_id . "'");
}


function GetUserName( $id ) {
	$u = get_user_to_edit( $id );
	if ($u)
		return $u->display_name;
	return "user $id";
}
	/**
	 * @param bool $force_login
	 *
	 * @return int
	 */

}
