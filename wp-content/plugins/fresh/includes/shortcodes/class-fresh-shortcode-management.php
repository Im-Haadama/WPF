<?php
/**
 * Fresh Management
 *
 * Used on the management page, this shortcode displays backoffice status and operations 
 *
 * @package Fresh/Shortcodes/Management
 * @version 1
 */

defined( 'FRESH_INCLUDES' ) || exit;

/**
 * Shortcode cart class.
 */
class Fresh_Shortcode_Management {

	/**
	 * Output the management shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function output( $atts ) {
		$status = new Fresh_Status();

		$operation = get_param("operation", false, "show_status");
		if (! get_user_id(true)) { print "Must login"; return; };

		switch ($operation){
			case "show_status":
				print $status->status();
				return;

			case "show_orders":
				print $status->ShowOrders(get_param("status", false, 'wc-pending'));
				return;

			case "show_supplies":
				print $status->SupplyTable(get_param("status", false, 1));
				return;

		}
		// handle actions / operations.
		print Fresh::instance()->handle_operation($operation);
	}
}

