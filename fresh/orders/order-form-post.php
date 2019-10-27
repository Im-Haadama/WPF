<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/03/18
 * Time: 10:41
 */
if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/im_tools.php" );
require_once( "orders-common.php" );

print header_text( false, true );
$operation = get_param( "operation" );
// my_log( "Operation: " . $operation, __FILE__ );

if ( isset( $operation ) )
switch ( $operation ) {
	case "create_order":
		$params = get_param_array( "params" );
		$name   = get_param( "name" );
		$phone  = get_param( "phone" );
		$group  = get_param( "group" );
		$user   = get_param( "user" );
		$method = get_param( "method" );
		$email  = get_param( "email" );

		print order_form( $params, $name, $phone, $group, $user, $method, $email );
		break;
	default:
		die( "operation $operation not handled" );
}

function order_form( $params, $name, $phone, $group, $user, $method, $email ) {
	global $support_phone;

	$data = "";

	if ( ! $email or strlen( $email ) < 4 ) {
		$data .= "כתובת המייל חסרה";

		return $data;
	}

	if ( count( $params ) < 2 ) {
		$data .= "לא נבחרו מוצרים" . "<br/>";

		return $data;
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
		$data .= "שלום " . get_customer_name( $user ) . "<br/>";
	} else {
		$data .= "שלום! " . "<br/>";
	}

	$data .= "הזמנה " . $order_id . " נקלטה בהצלחה." . "<br/>";
	// $data .= "ההזמנה תסופק לפי ימי החלוקה לאזורך. " . gui_hyperlink( "לפרטים", $_POST["SERVER_NAME"] . "/deliveries" ) . "<br/>";

	if ( $found ) {
		$data .= "מועד החלוקה שנבחר " . order_get_shipping( $order_id ) . "<br/>";
		$data .= "עם אישור ההזמנה על ידינו, תקבל מייל עם העתק ההזמנה<br/>";
		$data .= "תוכל לראות את ההזמנה ולבצע בה שינויים באתר: " . gui_hyperlink( "החשבון שלי", "/balance" ) . "<br/>";
	}
	$data .= $message . "<br/>";
	$data .= " טלפון שירות הלקוחות " . $support_phone . "<br/>";

	return $data;
}