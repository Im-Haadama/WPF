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
		$params = get_param_array( "params" );
		$name   = get_param( "name" );
		$phone  = get_param( "phone" );
		$group  = get_param( "group" );
		$user   = get_param( "user" );
		$method = get_param( "method" );
		$email  = get_param( "email" );
		if ( ! $email or strlen( $email ) < 4 ) {
			print "כתובת המייל חסרה";
			die ( 1 );
		}

		if ( count( $params ) < 2 ) {
			print "לא נבחרו מוצרים" . "<br/>";
			die ( 1 );
		};

		$prods      = array();
		$quantities = array();
		for ( $i = 0; $i < count( $params ); $i += 2 ) {
			array_push( $prods, $params[ $i ] );
			array_push( $quantities, $params[ $i + 1 ] );
		}

		$comment = 'הזמנה נוצרה ע"י טופס\n';
		$comment .= "דואל " . $email . '\n';
		if ( $group ) {
			$comment .= "שם הקבוצה:" . $group . ". ההזמנה תארז עי הקבוצה\n";
		}
		if ( $name ) {
			$comment .= "שם המזמין:" . $name . "\n";
		}
		if ( $phone ) {
			$comment .= "טלפון המזמין:" . $phone . "\n";
		}

		$message = "";
		$found   = true;
		if ( ! $user ) {
			$wp_user = get_user_by( "email", $email );
			if ( $wp_user ) {
				$user = $wp_user->ID;
			} else {
				$found = false;
				global $support_user;
				$user = $support_user;

				$message .= "כתובת המייל לא מוכרת. אנא פנה לשירות הלקוחות לצורך אישור ההזמנה. ציין את מספר ההזמנה.";
			}
		}
		$o        = Order::CreateOrder( $user, 0, $prods, $quantities, $comment, null, 0, $method );
		$order_id = $o->GetID();

		if ( ! $o ) {
			die ( "error creating order" );
		}


		if ( $found ) {
			print "שלום " . get_customer_name( $user ) . "<br/>";
		} else {
			print "שלום! " . "<br/>";
		}

		print "הזמנה " . $order_id . " נקלטה בהצלחה." . "<br/>";
		// print "ההזמנה תסופק לפי ימי החלוקה לאזורך. " . gui_hyperlink( "לפרטים", $_POST["SERVER_NAME"] . "/deliveries" ) . "<br/>";

		if ( $found ) {
			print "מועד החלוקה שנבחר " . order_get_shipping( $order_id ) . "<br/>";
			print "עם אישור ההזמנה על ידינו, תקבל מייל עם העתק ההזמנה<br/>";
			print "תוכל לראות את ההזמנה ולבצע בה שינויים באתר: " . gui_hyperlink( "החשבון שלי", "/balance" ) . "<br/>";
		}
		print  $message . "<br/>";
		print " טלפון שירות הלקוחות " . $support_phone . "<br/>";
		break;
	default:
		die( "operation $operation not handled" );
}
