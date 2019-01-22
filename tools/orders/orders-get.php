<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );
require_once( "../im_tools.php" );
require_once( '../r-shop_manager.php' );
// require_once 'orders-common.php';
require_once '../delivery/delivery.php';

print header_text( false, false );

if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
}

$order_type = get_param( "order_type" ); // comma separated. w - waiting to deliver. p - pending/on-hold

if ( ! $order_type ) {
	print gui_button( "btn_new", "show_create_order()", "הזמנה חדשה" );
}

require( "new-order.php" );

?>

<script type="text/javascript" src="/agla/client_tools.js"></script>

    <script>
        function mission_changed(order_id) {
            // "mis_"
            //var order_id = field.name.substr(4);
            var mis = document.getElementById("mis_" + order_id);
            var mission_id = get_value(mis);
//            $mission_id = $_GET["id"];
//            $order_id   = $_GET["order_id"];
            execute_url("orders-post.php?operation=mission&order_id=" + order_id + "&id=" + mission_id);
        }

        function start_handle() {
            var collection = document.getElementsByClassName("select_order_wc-pending");
            var order_ids = new Array();

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    window.location = window.location;
                }
            }

            for (var i = 0; i < collection.length; i++) {
                var order_id = collection[i].id.substr(4);
                if (document.getElementById("chk_" + order_id).checked)
                    order_ids.push(order_id);
            }
            collection = document.getElementsByClassName("select_order_wc-on-hold");
            for (var i = 0; i < collection.length; i++) {
                order_id = collection[i].id.substr(4);
                if (document.getElementById("chk_" + order_id).checked)
                    order_ids.push(order_id);
            }

            var request = "orders-post.php?operation=start_handle&ids=" + order_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function cancel_order() {
            var classes = ["select_order_wc-pending", "select_order_wc-processing", "select_order_wc-on-hold"];
            var order_ids = new Array();

            for (var c = 0; c < classes.length; c++) {
                var collection = document.getElementsByClassName(classes[c]);
                for (var i = 0; i < collection.length; i++) {
                    var order_id = collection[i].id.substr(4);
                    if (document.getElementById("chk_" + order_id).checked)
                        order_ids.push(order_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    window.location = window.location;
                }
            }
            var request = "orders-post.php?operation=cancel_orders&ids=" + order_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function delivered() {
            var collection = document.getElementsByClassName("select_order_wc-awaiting-shipment");
            var order_ids = new Array();
            var table = document.getElementById("wc-awaiting-shipment");

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    window.location = window.location;
                }
            }

            for (var i = 0; i < collection.length; i++) {
                var order_id = collection[i].id.substr(4);
                if (document.getElementById("chk_" + order_id).checked)
                    order_ids.push(order_id);
            }
            var request = "orders-post.php?operation=delivered&ids=" + order_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function select_orders(table_name) {
            var table = document.getElementById(table_name);
            for (var i = 1; i < table.rows.length; i++)
                table.rows[i].firstElementChild.firstElementChild.checked =
                    table.rows[0].firstElementChild.firstElementChild.checked;

        }

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

if ( isset( $week ) ) {
	print "הזמנה לשבוע  " . $week . "<br/>";
	print orders_table( "wc-complete", false, 0, $week );
	die ( 0 );
}

print gui_header( 1, "הזמנות" );

debug_time1( "reset" );

if ( current_user_can( "edit_shop_orders" ) and
     ( is_null( $order_type ) or in_array( "p", explode( ",", $order_type ) ) )
) {
	// print "XXX";
	// print $pending;
	$pending = orders_table( array( "wc-pending", "wc-on-hold" ) );
	if ( strlen( $pending ) > 4 ) {
		print $pending;
		print gui_button( "btn_start", "start_handle()", "התחל טיפול" );
		print gui_button( "btn_cancel", "cancel_order()", "בטל" ) . "<br/>";
	}
} else {
	if ( ! current_user_can( "edit_shop_orders" ) ) {
		print "no permission";
	}
	// print "ot=" . is_null( $order_type ) . "<br/>";
	// print "אין הרשאות";
}

print orders_table( "wc-processing" );

if ( current_user_can( "edit_shop_orders" ) and
     ( is_null( $order_type ) or in_array( "w", explode( ",", $order_type ) ) )
) {
	$shipment = orders_table( "wc-awaiting-shipment" );

	if ( strlen( $shipment ) > 5 ) {
		print $shipment;
		print gui_button( "btn_delivered", "delivered()", "משלוח נמסר" ) . "<br/>";
	}
}

// This month active users
$sql = 'SELECT distinct meta.meta_value ' .
       'FROM `wp_posts` posts, wp_postmeta meta ' .
       'WHERE `post_date` >= date_sub(now(), interval 30 day) ' .
       'and post_status like \'wc%\'' .
       'and meta.post_id = posts.id and meta.meta_key = \'_customer_user\' ' .
       'order by 1';

// print "לקוחות החודש: ";
//$result       = mysqli_query( $conn, $sql );
//$active_users = array();
//while ( $row = mysqli_fetch_row( $result ) ) {
//	$user_id                  = $row[0];
//	$active_users[ $user_id ] = $user_id;
//	// print get_customer_name($user_id) . ", ";
//}

print "<br>";

// Now active users
$sql = 'SELECT meta.meta_value ' .
       'FROM `wp_posts` posts, wp_postmeta meta ' .
       'WHERE post_status like \'wc-%\' ' .
       'and meta.post_id = posts.id and meta.meta_key = \'_customer_user\' ' .
       'and post_date > curdate() - 7 ' .
       'order by 1 ';

$result = mysqli_query( $conn, $sql );

//while ( $row = mysqli_fetch_row( $result ) ) {

// print "לקוחות השבוע: ";

//	$user_id = $row[0];
	// print get_customer_name($user_id) . ", ";

//	unset( $active_users[ $user_id ] );
//}

// print "<br>";

//print "עדיין לא הזמינו: ";
//foreach ( $active_users as $u ) {
//	$name = get_customer_name( $u );
//	// $name = "userid-" . $u;
//	print   $name . "(" . get_user_order_count( $u ) . "), ";
//}

//print "$data";

function get_user_order_count( $u ) {
	$sql = 'SELECT count(*) ' .
	       ' FROM `wp_posts` posts, wp_postmeta meta ' .
	       ' WHERE post_status like \'wc-%\' ' .
	       ' and meta.meta_key = \'_customer_user\' and meta.meta_value = ' . $u .
	       ' and meta.post_id = posts.ID';

	return sql_query_single_scalar( $sql );
}

?>
<!--<button id="btn" onclick="replace_baskets()">החלף סלים</button>-->
<datalist id="units">
    <option value="קג"></option>
    <option value="יח"></option>
</datalist>


<?php

function debug_time2( $str ) {
	$micro_date = microtime();
	$date_array = explode( " ", $micro_date );
	$date       = date( "Y-m-d H:i:s", $date_array[1] );
	echo "$str $date:" . $date_array[0] . "<br>";
}

function debug_time1( $str ) {
	static $prev_time;
	if ( $str == "reset" ) {
		$prev_time = microtime();

		return;
	}
	$now         = microtime();
	$micro_delta = $now - $prev_time;
	$date_array  = explode( " ", $micro_delta );
	$date        = date( "s", $date_array[1] );
	if ( $micro_delta > 0.05 ) {
		my_log( "$str $date:" . $date_array[0] . "<br>", "performance" );
	}
	$prev_time = $now;
}

?>
<div id="logging"></div>

</html>
