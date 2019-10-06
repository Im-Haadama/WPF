<?php
require_once( "../im_tools.php" );
require_once 'orders-common.php';
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( '../delivery/delivery.php' );

require_once( ROOT_DIR . "/init.php" );
$user_id = login_id();
$manager = user_can( $user_id, "edit_shop_orders" );
$order_id = get_param("order_id", true);
$o        = new Order( $order_id );
if ( $o->getCustomerId() != $user_id and ! $manager ) {
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
    <script type="text/javascript" src="/niver/gui/client_tools.js"></script>
    <script type="text/javascript" src="/fresh/tools.js"></script>
    <script>

        function save_mission() {
            var _mission = get_value(document.getElementById("mission_select"));
            var mission = _mission.substr(0, _mission.indexOf(")"));
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
            let prod_id = get_value_by_name("new_product");
            request_url = request_url + "&prod_id=" + prod_id;
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

$for_edit = 1;

$delivery_id = get_delivery_id( $order_id );
$printed     = get_post_meta( $order_id, 'printed' );

if ( $delivery_id )
	$for_edit = false;

else if ( $printed ) {
	$for_edit = 0;
}

$order = new Order( $order_id );

print $order->infoBox( $for_edit );

if ( $printed ) {
	print "הזמנה הודפסה ולא ניתנת לעריכה<br/>";
} else if ( $delivery_id ) {
	print "הזמנה נארזה ולא ניתנת לעריכה";
}

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

<?php
if ( current_user_can( "edit_shop_orders" ) or
     $o->getCustomerId() == get_current_user_id()
) {

	if ( $for_edit ) {
	    $args = array();
		print gui_header( 1, "Add items" );
		print gui_table_args( array(
			array( "בחר פריט", "כמות", "יח" ),
			array( gui_select_product("new_product", null, $args),
                "<input id=\"qua_\">",
                "<input id=\"uni_\" list=\"units\">" ))); // , array(GuiSelectTable("itm_", "wp_", $args)
		print gui_button( "btn_add_item", "add_item()", "הוסף" );
		print gui_button( "btn_replace", "replace()", "החלף סלים במרכיבים" );
	}
}
?>

</body>
</html>
