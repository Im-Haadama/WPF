<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/03/18
 * Time: 10:41
 */

//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
require_once( "orders-common.php" );

print header_text( false, true );
$operation = $_GET["operation"];
// my_log( "Operation: " . $operation, __FILE__ );

switch ( $operation ) {
	case "create_order":
		$params = $_GET["params"];
		$name   = null;
		$phone  = null;
		$group  = null;
		$user   = null;
		if ( isset( $_GET["name"] ) ) {
			$name = urldecode( $_GET["name"] );
			// print "שם: " . $name . "<br/>";
		}
		if ( isset( $_GET["phone"] ) ) {
			$phone = urldecode( $_GET["phone"] );
			// print "טלפון: " . $phone . "<br/>";
		}
		if ( isset( $_GET["group"] ) ) {
			$group = urldecode( $_GET["group"] );
			// print "קבוצה: " . $group . "<br/>";
		}
		if ( isset( $_GET["user"] ) ) {
			$user = urldecode( $_GET["user"] );
			// print "קבוצה: " . $group . "<br/>";
		}
		if ( isset( $_GET["email"] ) ) {
			$email = urldecode( ( $_GET["email"] ) );
		} else {
			print "כתובת המייל חסרה";
			die ( 1 );
		}

		form_create_order( explode( ",", $params ), $phone, $name, $group, $user, $email );
		break;
}

function form_create_order( $params, $phone = null, $name = null, $group = null, $user_id = null, $email = null ) {
	global $support_phone;
//	print "יוצר הזמנה ליוזר " . $email . "<br/>";
//	$user = get_user_by("email", $email);
//
//	if (! $user){
//		print "יוזר לא קיים";
//		die(0);
//	}
	$prods      = array();
	$quantities = array();

	if ( count( $params ) < 2 ) {
		print "לא נבחרו מוצרים" . "<br/>";
		die ( 1 );
	};
	for ( $i = 0; $i < count( $params ); $i += 2 ) {
		array_push( $prods, $params[ $i ] );
		array_push( $quantities, $params[ $i + 1 ] );
	}
	$comment = 'הזמנה נוצרה ע"י טופס\n';
	if ( $group ) {
		$comment .= "שם הקבוצה:" . $group . ". ההזמנה תארז עי הקבוצה\n";
	}
	if ( $name ) {
		$comment .= "שם המזמין:" . $name . "\n";
	}
	if ( $phone ) {
		$comment .= "טלפון המזמין:" . $phone . "\n";
	}
	if ( $email ) {
		$comment .= "מייל: " . $email . "\n";
		if ( $u = get_user_by( "email", $email ) ) {
			$user_id = $u->ID;
			print "user: " . $user_id;
		} else {
			print "כתובת המייל לא מוכרת. אנא פנה לשירות הלקוחות לצורך ביצוע ההזמנה. ציין את מספר ההזמנה שמופיע להלן." . "<br/>";
			print "טלפון " . $support_phone . "<br/>";
		}
	}

	return create_order( $user_id ? $user_id : get_user_id(), 0, $prods, $quantities, $comment );

}