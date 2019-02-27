<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/19
 * Time: 20:55
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/tools/im_tools.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( ROOT_DIR . "/tools/supplies/Supply.php" );

print gui_header( 1, "Handling auto orders" );
auto_mail();

print gui_header( 1, "Handling auto supply" );
auto_supply();

require_once( ROOT_DIR . "/tools/tasklist/Tasklist.php" );
print gui_header( 1, "Creating tasks from templates into tasklist" );
create_tasks();

print "done";
return;

function auto_supply() {
	//	Run once a week, but considered daily because each supplier has it's day.
	$sql = "SELECT id FROM im_suppliers WHERE  auto_order_day = " . date( "w" );

	// print $sql;
	$suppliers = sql_query_array_scalar( $sql );
	$created   = false;

	foreach ( $suppliers as $supplier_id ) {
		print "create auto order for " . get_supplier_name( $supplier_id ) . "\n";

		// $s = new Supply($supplier_id);
		$last_order = sql_query_single_scalar( "SELECT max(date) FROM im_supplies WHERE supplier = " . $supplier_id );

		print "last: " . $last_order . "\n";
		$sold         = supplier_report_data( $supplier_id, $last_order, date( 'y-m-d' ) );
		$supply_lines = array();
		$total        = 0;
		foreach ( $sold as $k => $product ) {
			$prod_id  = $sold[ $k ][0];
			$quantity = $sold[ $k ][1];
			$price    = get_buy_price( $prod_id, $supplier_id );
			if ( $quantity > 0 ) {
				print get_product_name( $prod_id ) . " " . $quantity . "\n";
				array_push( $supply_lines, array( $prod_id, $quantity ) );
				$total += $quantity * $price;
			}
		}
		if ( $total > sql_query_single_scalar( "SELECT min_order FROM im_suppliers WHERE id = " . $supplier_id ) ) {
			$supply = Supply::CreateSupply( $supplier_id );
			foreach ( $supply_lines as $line ) {
				$supply->AddLine( $line[0], $line[1], get_buy_price( $line[0] ) );
			}
			$supply->Send();
		} else {
			print "not enough for an order\n";
		}
		$created = true;
//		var_dump($sold);
	}
	if ( ! $created ) {
		print "Done<br/>";
	}
}

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
