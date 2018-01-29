<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:20
 */
// require_once( "r-shop_manager.php" );

require_once( STORE_DIR . "/wp-includes/taxonomy.php" );


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

function is_admin_user() {
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

