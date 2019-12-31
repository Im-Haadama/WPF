<?php

defined( 'FRESH_INCLUDES' ) || exit;

/**
 * Shortcode cart class.
 */
class Fresh_Shortcodes {

	/**
	 * Calculate shipping for the cart.
	 *
	 * @throws Exception When some data is invalid.
	 */

	/**
	 * Output the management shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function suppliers( $atts ) {
		$operation = get_param("operation", false, "show_status");
		if (get_user_id(true))
			print Fresh::instance()->handle_operation($operation);
	}

	public static function inventory_count( $atts ) {
		$operation = get_param("operation", false, "inventory");
		if (get_user_id(true))
			print Fresh::instance()->handle_operation($operation);
	}

	public static function packing_control( $atts ) {
		$operation = get_param("operation", false, "inventory");
		if (get_user_id(true))
			print Fresh_Packing::instance()->handle_operation($operation);
	}

}
