<?php

require_once( '../tools_wp_login.php' );
require 'orders-common.php';
require '../delivery/delivery.php';

?>

<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <script>
        function complete_status() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    location.reload(true);
                }
            }
            var request = "orders_close_all_open.php";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function create_subs() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    location.reload(true);
                }
            }
            var request = "orders-create-subs.php";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function replace_baskets() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    location.reload(true);
                }
            }
            var request = "orders-post.php?operation=replace_baskets";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>

</head>
<?php

// Check connection
if ( $link->connect_error ) {
	die( "Connection failed: " . $conn->connect_error );
}


print "<header><center><h1>הזמנות במצב טיפול</h1></center></header>";

$sql = 'SELECT posts.id'
       . ' FROM `wp_posts` posts'
       . " WHERE post_status LIKE '%wc-processing%' or post_status LIKE '%wc-on-hold%' order by 1";


$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

$fields = mysql_num_fields( $export );

for ( $i = 0; $i < $fields; $i ++ ) {
	$header .= mysql_field_name( $export, $i ) . "\t";
}

$data = "<table>";
$data .= "<tr>" . gui_cell( gui_bold( "אזור" ) );
$data .= "<td><h3>מספר </br> הזמנה</h3></td>";
$data .= "<td><h3>שם המזמין</h3></td>";
$data .= "<td><h3>עבור</h3></td>";
$data .= "<td><h3>סכום</h3></td>";
//$data .= "<td><h3>תעודת<br/>משלוח</h3></td>";
//$data .= "<td><h3>סכום</h3></td>";
//$data .= "<td><h3>דמי<br/>משלוח</h3></td>";
//$data .= "<td><h3>אחוז מילוי</h3></td>";
$data                  .= "</tr>";
$count                 = 0;
$total_delivery_total  = 0;
$total_order_total     = 0;
$total_order_delivered = 0;
$total_delivery_fee    = 0;
$lines                 = array();

while ( $row = mysql_fetch_row( $export ) ) {
	$order_id    = $row[0];
	$customer_id = get_postmeta_field( $order_id, '_customer_user' );

	// display order_id with link to display it.
	$count ++;
	// 1) order ID with link to the order
	$zone = order_get_zone( $order_id );
	// print $order_id. " ". $zone . "<br/>";
	$row_text = gui_cell( zone_get_name( $zone ) );
	$row_text .= "<td><a href=\"get-order.php?order_id=" . $order_id . "\">" . $order_id . "</a></td>";

	// 2) Customer name with link to his deliveries
	$row_text .= "<td><a href=\"../account/get-customer-account.php?customer_id=" . $customer_id . "\">" .


	             get_customer_by_order_id( $order_id ) . "</a></td>";

	$row_text .= "<td>" . get_postmeta_field( $order_id, '_shipping_first_name' ) . ' ' .
	             get_postmeta_field( $order_id, '_shipping_last_name' ) . "</td>";

	// 3) Order total
	$order_total       = get_postmeta_field( $order_id, '_order_total' );
	$row_text          .= "<td>" . $order_total . '</td>';
	$total_order_total += $order_total;

	// 4) Delivery note
	$sql1 = 'SELECT id FROM im_delivery'
	        . ' WHERE order_id = ' . $order_id;

	my_log( $sql1 );
	// print $sql . "<br>";
	$export1 = mysql_query( $sql1 );
	if ( $export1 ) {
		$row1        = mysql_fetch_row( $export1 );
		$delivery_id = intval( $row1[0] );
		$delivery    = new Delivery( $delivery_id );
		$row_text    .= "<td><a href=\"..\delivery\get-delivery.php?id=" . $delivery_id . "\"</a>" . $delivery_id . "</td>";
//        $total_amount = get_delivery_total($delivery_id[0]);
//        $row_text .= "<td>" . $total_amount . "</td>";
		if ( $delivery_id > 0 ) {
			$row_text .= "<td>" . $delivery->Price() . "</td>";
			$row_text .= "<td>" . $delivery->DeliveryFee() . "</td>";
			$percent  = "";
			if ( ( $order_total - $delivery->DeliveryFee() ) > 0 ) {
				$percent = round( 100 * ( $delivery->Price() - $delivery->DeliveryFee() ) / ( $order_total - $delivery->DeliveryFee() ), 0 ) . "%";
			}
			$row_text              .= "<td>" . $percent . "%</td>";
			$total_delivery_total  += $delivery->Price();
			$total_delivery_fee    += $delivery->DeliveryFee();
			$total_order_delivered += $order_total;
		}
	} else {
		$row_text .= "<td></td><td></td><td></td>";
	}
	$line = $row_text;

	array_push( $lines, array( $zone, $line ) );
	//   $data .= "<tr> " . trim($line) . "</tr>";
}
sort( $lines );

foreach ( $lines as $line ) {
	$data .= "<tr>" . $line[1] . "</tr>";
}
$rate = 0;
if ( $total_order_delivered > 0 ) {
	$rate = round( 100 * ( $total_delivery_total - $total_delivery_fee ) / $total_order_delivered );
}
$data .= "<tr><td></td><td></td><td></td><td>" . $total_order_total . "</td><td></td>" .
         "<td>" . $total_delivery_total . "</td>" .
         "<td>" . $total_delivery_fee . "</td>" .
         "<td>" . $rate . "%</td></tr>";
$data = str_replace( "\r", "", $data );


$data .= "</table>";
$data .= '<center>סהכ הזמנות השבוע ' . $count . '</center>';

// This month active users
$sql = 'SELECT distinct meta.meta_value ' .
       'FROM `wp_posts` posts, wp_postmeta meta ' .
       'WHERE `post_date` >= date_sub(now(), interval 30 day) ' .
       'and post_status like \'wc%\'' .
       'and meta.post_id = posts.id and meta.meta_key = \'_customer_user\' ' .
       'order by 1';

// print "לקוחות החודש: ";

$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );
$active_users = array();
while ( $row = mysql_fetch_row( $export ) ) {
	$user_id                  = $row[0];
	$active_users[ $user_id ] = $user_id;
	// print get_customer_name($user_id) . ", ";
}

print "<br>";

// Now active users
$sql = 'SELECT meta.meta_value ' .
       'FROM `wp_posts` posts, wp_postmeta meta ' .
       'WHERE post_status like \'wc-%\' ' .
       'and meta.post_id = posts.id and meta.meta_key = \'_customer_user\' ' .
       'and post_date > curdate() - 7 ' .
       'order by 1 ';
$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

// print "לקוחות השבוע: ";

while ( $row = mysql_fetch_row( $export ) ) {
	$user_id = $row[0];
	// print get_customer_name($user_id) . ", ";

	unset( $active_users[ $user_id ] );
}

// print "<br>";

print "עדיין לא הזמינו: ";
foreach ( $active_users as $u ) {
	$name = get_customer_name( $u );
	// $name = "userid-" . $u;
	print   $name . "(" . get_user_order_count( $u ) . "), ";
}

print "$data";

function get_user_order_count( $u ) {
	$sql = 'SELECT count(*) ' .
	       ' FROM `wp_posts` posts, wp_postmeta meta ' .
	       ' WHERE post_status like \'wc-%\' ' .
	       ' and meta.meta_key = \'_customer_user\' and meta.meta_value = ' . $u .
	       ' and meta.post_id = posts.ID';
	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$row = mysql_fetch_row( $export );

	return $row[0];

}

?>
<button id="btn" onclick="complete_status()">סיים טיפול בכל ההזמנות</button>
<button id="btn" onclick="create_subs()">צור הזמנות למנויים</button>
<button id="btn" onclick="replace_baskets()">החלף סלים</button>
</html>
