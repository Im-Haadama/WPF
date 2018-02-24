<?php

require_once( "../im_tools.php" );
require_once( '../r-shop_manager.php' );
require_once 'orders-common.php';
require_once '../delivery/delivery.php';
require_once( '../maps/build-path.php' );

print header_text( false );

print gui_button( "btn_new", "show_create_order()", "הזמנה חדשה" );
?>

<script type="text/javascript" src="../client_tools.js"></script>

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
            var collection = document.getElementsByClassName("select_order");
            var order_ids = new Array();
            var table = document.getElementById("wc-pending");

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
            var request = "orders-post.php?operation=start_handle&ids=" + order_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function delivered() {
            var collection = document.getElementsByClassName("select_order");
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
        //        function moveNextRow(my_row) {
        //            if (event.which == 13) {
        //                alert(my_row);
        ////                var current = document.getElementsByName("quantity" + (my_row));
        ////                current[0].value = Math.round(current[0].value * 10) / 10;
        ////                var objs = document.getElementsByName("quantity" + (my_row + 1));
        ////                if (objs[0]) objs[0].focus();
        //            }
        //        }

        function add_order() {
            document.getElementById('add_order').disabled = true;
            var user_name = get_value(document.getElementById("client_select"));
            var user_id = user_name.substr(0, user_name.indexOf(")"));
            if (!(user_id > 0)) {
                alert("יש לבחור לקוח, כולל מספר מזהה מהרשימה");
                document.getElementById('add_order').disabled = false;
                return;
            }
            var prods = [];
            var quantities = [];
            var comment = [];
            var units = [];

            var item_table = document.getElementById("order_items");
            var line_number = 0;

            for (var i = 1; i < item_table.rows.length; i++) {
                var prod = get_value(document.getElementById("itm_" + i));
                var prod_id = prod.substr(0, prod.indexOf(")"));
                var q = get_value(document.getElementById("qua_" + i));
                var u = get_value(document.getElementById("uni_" + i));

                if (q > 0) {
                    prods.push(prod_id);
                    quantities.push(q);
                    units.push(u);
                    line_number++;
                }

                // ids.push(get_value(item_table.rows[i].cells[0].innerHTML));
            }
            if (line_number === 0) {
                alert("יש לבחור מוצרים, כולל כמויות");
                return;
            }

            var mission_id = get_value(document.getElementById("mis_new"));

            var request = "../orders/orders-post.php?operation=create_order" +
                "&user_id=" + user_id +
                "&prods=" + prods.join() +
                "&quantities=" + quantities.join() +
                // "&comments="   + encodeURI(comment) +
                "&units=" + units.join() +
                "&mission_id=" + mission_id;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {  // Request finished
                    if (xmlhttp.responseText.includes("בהצלחה")) {
                        location.reload();
                    } else {
                        logging.innerHTML = xmlhttp.responseText;
                        document.getElementById('add_order').disabled = false;
                    }
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function select_product(my_row) {
            if (event.which === 13) {
                var objs = document.getElementById("qua_" + (my_row));
                if (objs) objs.focus();
            }
        }
        function select_unit(my_row) {
            if (event.which === 13) {
                var objs = document.getElementById("uni_" + (my_row));
                if (objs) objs.focus();
            }
        }

        function add_line() {
            var item_table = document.getElementById("order_items");
            var line_idx = item_table.rows.length;
            var new_row = item_table.insertRow(-1);
            var product = new_row.insertCell(0);
            // product.innerHTML = "<input id=\"itm_" + line_idx + "\" list=\"items\" onkeypress=\"select_product(" + line_idx + ")\">";
            var select = "<? print gui_input_select_from_datalist( "XX", "products" ); ?>";
            product.innerHTML = select.replace("XX", "itm_" + line_idx);
            var quantity = new_row.insertCell(1);
            quantity.innerHTML = "<input id = \"qua_" + line_idx + "\" onkeypress=\"select_unit(" + line_idx + ")\">";
            var units = new_row.insertCell(2);
            units.innerHTML = "<input id=\"uni_" + line_idx + "\" list=\"units\", onkeypress=\"add_line(" + line_idx + ")\">";
            product.firstElementChild.focus();

        }
        function show_create_order() {
            var new_order = document.getElementById("new_order");
            new_order.style.display = 'block';
            add_line();
            document.getElementById("client_select").focus();

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

print gui_datalist( "products", "im_products", "post_title", true );

// Check connection
if ( $link->connect_error ) {
	die( "Connection failed: " . $conn->connect_error );
}

print gui_header( 1, "הזמנות" );

$pending = orders_table( array( "wc-pending", "wc-on-hold" ) );
if ( strlen( $pending ) > 4 ) {
	print $pending;
	print gui_button( "btn_start", "start_handle()", "התחל טיפול" ) . "<br/>";
}
print orders_table( "wc-processing" );

$shipment = orders_table( "wc-awaiting-shipment" );
if ( strlen( $shipment ) > 5 ) {
	print $shipment;
	print gui_button( "btn_delivered", "delivered()", "משלוח נמסר" ) . "<br/>";
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

function debug_time1( $str ) {
	$micro_date = microtime();
	$date_array = explode( " ", $micro_date );
	$date       = date( "Y-m-d H:i:s", $date_array[1] );
	echo "$str $date:" . $date_array[0] . "<br>";
}

function orders_table( $statuses ) {
	global $conn;
//	LIKE 'wc-processing%' or post_status LIKE 'wc-on-hold%' order by 1";

	$status_names = wc_get_order_statuses();
	// var_dump($status_names);
	$all_tables = "";
	if ( ! is_array( $statuses ) ) {
		$statuses = array( $statuses );
	}
//	debug_time1("start loop");
	foreach ( $statuses as $status ) {
		// print $status . "<br/>";

		$data = gui_header( 2, $status_names[ $status ] );

		$sql = 'SELECT posts.id'
		       . ' FROM `wp_posts` posts'
		       . " WHERE post_status = '" . $status . "'" .
		       " order by 1";

		// Build path
		$order_ids = sql_query_array_scalar( $sql );

		// If no orders in this status, move on.
		if ( sizeof( $order_ids ) < 1 ) {
			continue;
		}

		$path = array();
//		debug_time1("start route");
		find_route_1( 1, $order_ids, $path, false );
//		debug_time1("end route");

		// print $sql;
		$result = mysqli_query( $conn, $sql );
//		debug_time1("after q");
		$data                  .= "<table id='" . $status . "'>";
		$data                  .= "<tr>";
		$data                  .= gui_cell( gui_checkbox( "chk_all", "", "",
			array( "onchange=select_orders('" . $status . "')" ) ) );
		$data                  .= "<td><h3>סוג משלוח</h3></td>";
		$data                  .= gui_cell( gui_bold( "משימה" ) );
		$data                  .= "<td><h3>מספר </br> הזמנה</h3></td>";
		$data                  .= "<td><h3>שם המזמין</h3></td>";
		$data                  .= "<td><h3>עבור</h3></td>";
		$data                  .= "<td><h3>סכום</h3></td>";
		$data                  .= "<td><h3>ישוב</h3></td>";
		$data                  .= "<td><h3>אמצעי תשלום</h3></td>";
		$data                  .= "</tr>";
		$count                 = 0;
		$total_delivery_total  = 0;
		$total_order_total     = 0;
		$total_order_delivered = 0;
		$total_delivery_fee    = 0;
		$lines                 = array();

		if ( ! $result ) {
			continue;
		}

		$count = 0;

		while ( $row = mysqli_fetch_row( $result ) ) {
			// debug_time1("after fetch");
			$order_id    = $row[0];
			$row_text    = gui_cell( gui_checkbox( "chk_" . $order_id, "select_order" ) );

			$row_text    .= gui_cell( order_get_shipping( $order_id ) );
			$customer_id = order_get_customer_id( $order_id );

			// display order_id with link to display it.
			$count ++;
			// 1) order ID with link to the order
			$mission_id = order_get_mission_id( $order_id );
			// print $order_id. " ". $mission . "<br/>";

			$row_text .= gui_cell( gui_select_mission( "mis_" . $order_id, $mission_id, "onchange=\"mission_changed(" . $order_id . ")\"" ) );
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
			$delivery_id = get_delivery_id( $order_id );
//    print "order: " . $order_id . "<br/>" . " del: " . $delivery_id . "<br/>";
			if ( $delivery_id > 0 ) {
				$delivery = new Delivery( $delivery_id );
				// if ($delivery_id == 68) var_dump($delivery);
				$row_text .= "<td><a href=\"..\delivery\get-delivery.php?id=" . $delivery_id . "\"</a>" . $delivery_id . "</td>";
//        $total_amount = get_delivery_total($delivery_id[0]);
//        $row_text .= "<td>" . $total_amount . "</td>";
				if ( $delivery_id > 0 ) {
					$row_text .= "<td>" . $delivery->Price() . "</td>";
					$row_text .= "<td>" . $delivery->DeliveryFee() . "</td>";
					$percent  = "";
					if ( ( $order_total - $delivery->DeliveryFee() ) > 0 ) {
						$percent = round( 100 * ( $delivery->Price() - $delivery->DeliveryFee() ) / ( $order_total - $delivery->DeliveryFee() ), 0 ) . "%";
					}
					$row_text              .= "<td>" . $percent . "</td>";
					$total_delivery_total  += $delivery->Price();
					$total_delivery_fee    += $delivery->DeliveryFee();
					$total_order_delivered += $order_total;
				}
			} else {
				$row_text .= "<td>" . order_info( $order_id, '_shipping_city' ) . "</td><td></td><td></td>";
			}
			$row_text .= gui_cell( get_payment_method_name( $customer_id ) );//gui_cell(gui_select_payment("payment_" . $customer_id,"select_payment(" . $customer_id . ")",
			// get_payment_method($customer_id)));
			$line = $row_text;

			array_push( $lines, array( array_search( $customer_id, $path ), $line ) );
		}
		//   $data .= "<tr> " . trim($line) . "</tr>";
		sort( $lines );

		foreach ( $lines as $line ) {
			$data .= "<tr>" . $line[1] . "</tr>";
		}
		$rate = 0;
		if ( $total_order_delivered > 0 ) {
			$rate = round( 100 * ( $total_delivery_total - $total_delivery_fee ) / $total_order_delivered );
		}
//		$data .= "<tr><td></td><td></td><td></td><td>" . $total_order_total . "</td><td></td>" .
//		         "<td>" . $total_delivery_total . "</td>" .
//		         "<td>" . $total_delivery_fee . "</td>" .
//		         "<td>" . $rate . "%</td></tr>";
		$data = str_replace( "\r", "", $data );

		$data .= "</table>";

		if ( $count > 0 ) {
			$all_tables .= $data;
		}
	}

	return $all_tables;
}
?>
<div id="new_order" style="display: none">
	<?php
	print gui_header( 1, "יצירת הזמנה" );
	print gui_table( array(
		array(
			gui_header( 2, "בחר לקוח" ),
			gui_header( 2, "בחר מועד" )
		),
		array(
			gui_select_client( 90, true ),
			gui_select_mission( "mis_new")
		)
	) );

	print gui_header( 2, "בחר מוצרים" );
	print gui_datalist( "items", "im_products", "post_title" );

	print gui_table( array( array( "פריט", "כמות", "קג או יח" ) ),
		"order_items" );

	print gui_button( "add_line", "add_line()", "הוסף שורה" );
	print gui_button( "add_order", "add_order()", "הוסף הזמנה" );
	?>

</div>
<div id="logging"></div>

</html>
