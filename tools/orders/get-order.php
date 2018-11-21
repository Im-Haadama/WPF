<?php

// error_reporting( E_ALL );
// ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
require_once 'orders-common.php';
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( '../delivery/delivery.php' );

$user_id = login_id();
$manager = false;
if ( user_can( $user_id, "edit_shop_orders" ) ) {
	$manager = true;
}

$order_id = $_GET["order_id"];
if ( order_get_customer_id( $order_id ) != $user_id and ! $manager ) {
	die ( "no permission" );
}

$margin   = false;
if ( $manager and isset( $_GET["margin"] ) ) {
	$margin = true;
}
?>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <script type="text/javascript" src="/agla/client_tools.js"></script>
    <script type="text/javascript" src="/tools/tools.js"></script>
    <script>

        function save_mission() {
            var mission = get_value(document.getElementById("mission_select"));
            var request = "orders-post.php?operation=mission&id=" + mission + "&order_id=<?print $order_id; ?>";

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    location.reload();
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
        function del_item() {
            var table = document.getElementById('order_lines');

            var collection = document.getElementsByClassName("line_chk");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    // var name = get_value(table.rows[i+1].cells[0].firstChild);
                    var line_id = collection[i].id.substr(3);

                    params.push(line_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    location.reload();
                }
            }
            var request = "orders-post.php?operation=delete_lines&order_id=<?php print $order_id; ?>&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function add_item() {
            var request_url = "orders-post.php?operation=add_item&order_id=<?php print $order_id; ?>";
            var _name = encodeURI(get_value(document.getElementById("itm_")));
            request_url = request_url + "&name=" + _name;
            var _q = encodeURI(get_value(document.getElementById("qua_")));
            request_url = request_url + "&quantity=" + _q;
            var _u = encodeURI(get_value(document.getElementById("uni_")))
            if (_u.length > 1) request_url = request_url + "&units=" + _u;

            var request = new XMLHttpRequest();
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    window.location = window.location;
                }
            }
            request.open("GET", request_url, true);
            request.send();
        }

        function replace() {
            var request_url = "replace-basket.php?order_id=<?php print $order_id; ?>";
            var request = new XMLHttpRequest();
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    window.location = window.location;
                }
            }
            request.open("GET", request_url, true);
            request.send();
        }

    </script>
</head>
<body>

<?php
// print header_text( true );
//$siton = false;
//
//if ( isset( $_GET["siton"] ) ) {
//	$siton = true;
//}
//
//if ( $siton ) {
//	print " - מחירון סיטונאי";
//}

print "</h2></center>";

$delivery_id = get_delivery_id( $order_id );
$for_edit    = ! ( $delivery_id > 0 );

if ( get_post_meta( $order_id, 'printed' ) )
	$for_edit = false;

print order_info_data( $order_id, $for_edit );

$d = delivery::CreateFromOrder( $order_id );
$d->PrintDeliveries( ImDocumentType::order, ImDocumentOperation::edit, $margin );

if ( current_user_can( "edit_shop_orders" ) ) {
	if ( is_numeric( $delivery_id ) ) {
		print '<a href="../delivery/get-delivery.php?id=' . $delivery_id . '">פתח תעודת משלוח' . '</a> ';
	} else {
		print '<a href="../delivery/create-delivery.php?order_id=' . $order_id . '">הפק תעודת משלוח' . '</a> ';
	}
}
print "<br/>";
print gui_datalist( "items", "im_products", "post_title" );
print "<br/>";
//print gui_button("btn_cancel", "cancel_order()", "בטל הזמנה");

print gui_button( "btn_del_item", "del_item()", "מחק פריטים מסומנים" );
print "<br/>";
?>

<datalist id="units">
    <option value="יח"></option>
</datalist>

<!-- TODO: limit to units datalist -->
<?php
?>

<?php if ( current_user_can( "edit_shop_orders" ) ) {

	if ( $for_edit ) {
		print gui_header( 1, "הוספת פריטים" );
		print gui_table( array(
			array( "בחר פריט", "כמות", "יח" ),
			array( "<input id=\"itm_\" list=\"items\">", "<input id=\"qua_\">", "<input id=\"uni_\" list=\"units\">" )
		) );
		print gui_button( "btn_add_item", "add_item()", "הוסף" );
		print gui_button( "btn_replace", "replace()", "החלף סלים במרכיבים" );
	}
}
?>

</body>
</html>
