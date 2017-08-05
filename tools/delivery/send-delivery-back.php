<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/04/16
 * Time: 15:32
 */

include_once( "../tools_wp_login.php" );
include_once( "../orders/orders-common.php" );
include_once( "../account/account.php" );

$del_id    = $_GET["del_id"];
$order_id  = get_order_id( $del_id );
$client_id = get_customer_id_by_order_id( $order_id );

my_log( __FILE__, "client_id = " . $client_id );

// OK... For my limitation of working with concurrent xmlhttprequest I'll check now if
// saving delivery lines finished.

$sql = "SELECT dlines FROM im_delivery WHERE id = " . $del_id;
$export = mysql_query( $sql ) or die ( $sql . " Sql error: " . mysql_error() );
$row    = mysql_fetch_row( $export );
$dlines = $row[0];
my_log( __FILE__, "dlines = " . $dlines );

for ( $i = 0; $i < 10; $i ++ ) {
	// Check if save finished
	$sql = "SELECT count(*) FROM im_delivery_lines WHERE delivery_id = " . $del_id;
	$export = mysql_query( $sql ) or die ( $sql . " Sql error: " . mysql_error() );

	$row = mysql_fetch_row( $export );
	my_log( "DLINES = " . $dlines, ", SAVED = " . $row[0] );

	if ( $row[0] == $dlines ) {
		break;
	}
	sleep( 1 );
}

my_log( __FILE__, "sending..." );

$del_user = order_info( $order_id, '_billing_first_name' );
$message  = "
<html lang=\"he\">
<head>
<meta charset=\"utf-8\" />
<title>משלוח חדש</title>
</head>
<body dir=\"rtl\">
שלום " . $del_user . "!
<br>
<br>
המשלוח שלך ארוז ויוצא לדרך!";
$message  .= "<br> היתרה המעודכנת במערכת " . client_balance( $client_id );

$message .= "<Br> להלן פרטי המשלוח";

$message .= file_get_contents( "http://store.im-haadama.co.il/tools/delivery/get-delivery.php?id=" . $del_id . "&send=1" );

$message .= "<br /> לפרטים אודות מצב החשבון והמשלוח האחרון הכנס
<a href = \"http://store.im-haadama.co.il/tools/account/get-customer-account.php?customer_id=" . $client_id . "\"> לאתר</a>
 העברות בנקאיות מתעדכנות בחשבונכם אצלנו עד כעשרה ימים לאחר התשלום.
<li>
למשלמים בהעברה בנקאית - פרטי החשבון:  \"עם האדמה\" בנק לאומי, סנף 648, חשבון 54010067 (מספר החשבון השנתה!)
</li>
<li>המחאה לפקודת \"עם האדמה\"</li>
<li>
במידה ושילמתם כבר, המכתב נשלח לצורך פירוט עלות המשלוח בלבד ואין צורך לשלם שוב.
</li>

נשמח מאוד לשמוע מה דעתכם! לשאלות בנוגע למשלוח מוזמנים ליצור אריתנו קשר במייל info@im-haadama.co.il.
</body>
</html>";

$user_info = get_userdata( $client_id );
my_log( $user_info->user_email );
send_mail( "משלוח בוצע", $user_info->user_email . ", info@im-haadama.co.il", $message );
