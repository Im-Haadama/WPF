<?php
require_once 'orders-common.php';
require_once( '../gui/inputs.php' );
require_once( '../delivery/delivery.php' );
?>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <script>
		<?php
		$filename = __DIR__ . "/../client_tools.js";
		$handle = fopen( $filename, "r" );
		$contents = fread( $handle, filesize( $filename ) );
		print $contents;

		$order_id = $_GET["order_id"];
		?>

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
$siton = false;

if ( isset( $_GET["siton"] ) ) {
	$siton = true;
}

if ( $siton ) {
	print " - מחירון סיטונאי";
}

print "</h2></center>";

$delivery_id = get_delivery_id( $order_id );
$for_edit    = ! ( $delivery_id > 0 );

print order_info_data( $order_id, $for_edit );

$d = delivery::CreateFromOrder( $order_id );
$d->print_delivery( ImDocumentType::order, ImDocumentOperation::edit );

$data = str_replace( "\r", "", $data );

$data .= "</table>";

print "$data";

if ( is_numeric( $delivery_id ) ) {
	print '<a href="../delivery/get-delivery.php?id=' . $delivery_id . '">פתח תעודת משלוח' . '</a> ';
} else {
	print '<a href="../delivery/create-delivery.php?order_id=' . $order_id . '">הפק תעודת משלוח' . '</a> ';
}

print gui_datalist( "items", "im_products", "post_title" );
?>

<datalist id="units">
    <option value="יח"></option>
</datalist>

<!-- TODO: limit to units datalist -->
<?php
print gui_table( array(
	array( "בחר פריט", "כמות", "יח" ),
	array( "<input id=\"itm_\" list=\"items\">", "<input id=\"qua_\">", "<input id=\"uni_\" list=\"units\">" )
) );
?>

<?php print gui_button( "btn_add_item", "add_item()", "הוסף" ); ?>
<?php print gui_button( "btn_del_item", "del_item()", "מחק" ); ?>
<?php print gui_button( "btn_replace", "replace()", "החלף סלים במרכיבים" ); ?>

</body>
</html>
