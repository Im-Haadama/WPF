<?php

require_once( '../tools_wp_login.php' );
require_once 'orders-common.php';
require_once '../delivery/delivery.php';

print header_text( false );

print gui_button( "btn_new", "show_create_order()", "הזמנה חדשה" );
?>
<button id="btn" onclick="complete_status()">סיים טיפול בכל ההזמנות</button>
<button id="btn" onclick="create_subs()">צור הזמנות למנויים</button>

<script type="text/javascript" src="../client_tools.js"></script>

    <script>

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
            var user_id = get_value(document.getElementById("client_select"));
            var prods = [];
            var quantities = [];
            var comment = [];
            var units = [];

            var item_table = document.getElementById("order_items");

            for (var i = 1; i < item_table.rows.length; i++) {
                var prod = get_value(document.getElementById("itm_" + i));
                var q = get_value(document.getElementById("qua_" + i));
                var u = get_value(document.getElementById("uni_" + i));

                if (q > 0) {
                    prods.push(encodeURI(prod));
                    quantities.push(encodeURI(q));
                    units.push(encodeURI(u));
                }

                // ids.push(get_value(item_table.rows[i].cells[0].innerHTML));
            }

            var request = "../orders/orders-post.php?operation=create_order" +
                "&user_id=" + user_id +
                "&prods=" + prods.join() +
                "&quantities=" + quantities.join() +
                // "&comments="   + encodeURI(comment) +
                "&units=" + units.join();

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    logging.innerHTML = xmlhttp.responseText;
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
            product.innerHTML = "<input id=\"itm_" + line_idx + "\" list=\"items\" onkeypress=\"select_product(" + line_idx + ")\">";
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

while ( $row = mysqli_fetch_row( $result ) ) {

// print "לקוחות השבוע: ";

	$user_id = $row[0];
	// print get_customer_name($user_id) . ", ";

	unset( $active_users[ $user_id ] );
}

// print "<br>";

//print "עדיין לא הזמינו: ";
//foreach ( $active_users as $u ) {
//	$name = get_customer_name( $u );
//	// $name = "userid-" . $u;
//	print   $name . "(" . get_user_order_count( $u ) . "), ";
//}

print "$data";

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

?>
<div id="new_order" style="display: none">
	<?php
	print gui_header( 1, "יצירת הזמנה" );
	print gui_header( 2, "בחר לקוח" );
	// TODO: Change back to 90
	print gui_select_client( 90, true );

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
