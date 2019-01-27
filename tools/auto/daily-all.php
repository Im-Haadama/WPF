<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/19
 * Time: 20:55
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . "/im_tools.php" );

auto_mail();

function auto_mail() {
	require_once( TOOLS_DIR . "/orders/form.php" );
	require_once( TOOLS_DIR . "/orders/orders-common.php" );
	require_once( TOOLS_DIR . "/mail.php" );

	global $business_name;
	global $support_email;

	$sql = "SELECT user_id FROM wp_usermeta WHERE meta_key = 'auto_mail'";

	$auto_list = sql_query_array_scalar( $sql );

	print "Auto mail...<br/>";
	print "Today " . date( "w" ) . "<br/>";

	foreach ( $auto_list as $client_id ) {
		print get_customer_name( $client_id ) . "<br/>";
		$last = get_user_meta( $client_id, "last_email", true );
		if ( $last == date( 'Y-m-d' ) ) {
			print "already sent";
			continue;
		}
		$setting = get_user_meta( $client_id, 'auto_mail', true );
		$day     = strtok( $setting, ":" );
		$categ   = strtok( ":" );
		print "day: " . $day . "<br/>";
		print "categ: " . $categ . "<br/>";

		if ( $day == date( 'w' ) ) {
			print "שולח...<br/>";
			$subject = "מוצרי השבוע ב-" . $business_name;
			$mail    = "שלום " . get_customer_name( $client_id ) .
			           " להלן רשימת מוצרי פרוטי ";
			do {
				if ( $categ == 0 ) {
					$mail = show_category_all( false, true );
					break;
				}
				if ( $categ == "f" ) {
					$mail = show_category_all( false, true, true );
					break;
				}
				foreach ( explode( ",", $categ ) as $categ ) {
					$mail .= show_category_by_id( $categ, false, true );
				}
			} while ( 0 );
			$user_info = get_userdata( $client_id );
			$to        = $user_info->user_email . ", " . $support_email;

			$rc = send_mail( $subject, $to, $mail );
			print "subject: " . $subject . "<br/>";
			print "mail: " . $mail . "<br/>";
			print "to: " . $to . "<br/>";
			print "rc: " . $rc . "<br/>";

			update_user_meta( $client_id, "last_email", date( 'Y-m-d' ) );
		}
	}

	// Todo: remove this
}
