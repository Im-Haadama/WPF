<?php
require 'orders-common.php';
require_once( '../gui/inputs.php' );
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
<center><img src="http://store.im-haadama.co.il/wp-content/uploads/2014/11/cropped-imadama-logo-7x170.jpg"></center>

<?php
$siton = false;

if ( isset( $_GET["siton"] ) ) {
	$siton = true;
}

print "<center><h2>הזמנה מספר " . $order_id;
if ( $siton ) {
	print " - מחירון סיטונאי";
}

print "</h2></center>";


print order_info_data( $order_id );

$data .= "<tr> " . trim( $line ) . "</tr>";

$sql = 'select '
       . ' woi.order_item_name, woim.meta_value, woim.order_item_id'
       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
       . ' where order_id = ' . $order_id
       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
       . ' group by woi.order_item_name order by 3'
       . ' ';
my_log( $sql, "get-order.php" );

$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

$fields = mysql_num_fields( $export );

$data      .= "<table id=\"order_lines\"><tr><td></td><td><h3>פריט</h3></td><td><h3>כמות</h3></td><td><h3>מעם</h3></td><td><h3>מחיר</h3></td><td><h3>סהכ</h3></td></tr>";
$total     = 0;
$vat_total = 0;

while ( $row = mysql_fetch_row( $export ) ) {
	$line          = "";
	$prod_id       = $row[1];
	$order_item_id = $row[2];

	// Display item name
	$value = "<td>" . gui_checkbox( "chk" . $order_item_id, "line_chk" ) . "</td>";
	$value .= "<td>" . $row[0] . '</td>';

	$quantity = quantity_in_order( $order_item_id );

	$value .= '<td>' . gui_input( "qua_" . $order_item_id, $quantity, null ) . '</td>';

	$sql2 = 'SELECT pm.meta_value FROM wp_woocommerce_order_itemmeta woim JOIN wp_postmeta pm'
	        . ' WHERE woim.order_item_id = ' . $order_item_id
	        . ' AND woim.meta_key = \'_product_id\''
	        . ' AND pm.post_id = woim.meta_value '
	        . ' AND pm.meta_key = \'_price\''
	        . ' AND woim.meta_value = pm.post_id'
	        . ' ';

	$export2 = mysql_query( $sql2 ) or die ( "Sql error : " . mysql_error() );

	$fields2 = mysql_num_fields( $export2 );

	$row2 = mysql_fetch_row( $export2 );

	$price = $row2[0];

	if ( $siton ) {
		$price = siton_price( $prod_id );
	}

	$row2 = mysql_fetch_row( $export2 );

	$vat = get_vat_percent( $prod_id );

	$total     = $total + ( $price * $quantity );
	$vat_line  = ( $price * $vat * $quantity ) / 100;
	$vat_total = $vat_total + $vat_line;

	$value .= '<td>' . $vat_line . '</td><td>' . $price . '</td><td>' . $price * $quantity . '</td>';

	$line .= $value;
	$data .= "<tr> " . trim( $line ) . "</tr>";
}
$data .= "<tr><td>סהכ ללא מעם</td><td></td><td></td><td></td><td>" . ( $total - $vat_total ) . "</td></tr>";
$data .= "<tr><td>סהכ מעם</td><td></td><td></td><td></td><td>" . $vat_total . "</td></tr>";
$data .= "<tr><td>סהכ לתשלום</td><td></td><td></td><td></td><td>" . $total . "</td></tr>";

$data = str_replace( "\r", "", $data );

if ( $data == "" ) {
	$data = "\n(0) Records Found!\n";
}

$data .= "</table>";

print "$data";

$delivery_id = get_delivery_id( $order_id );

if ( is_numeric( $delivery_id ) ) {
	print '<a href="../delivery/get-delivery.php?id=' . $delivery_id . '">פתח תעודת משלוח' . '</a> ';
} else {
	print '<a href="../delivery/create-delivery.php?order_id=' . $order_id . '">הפק תעודת משלוח' . '</a> ';
}

print gui_datalist( "items", "im_products", "post_title" );
?>

<table>
    <td>
        <input id="itm_" list="items">
        <input id="qua_">
    </td>
    ;
</table>
<?php print gui_button( "btn_add_item", "add_item()", "הוסף" ); ?>
<?php print gui_button( "btn_del_item", "del_item()", "מחק" ); ?>
<?php print gui_button( "btn_replace", "replace()", "החלף סלים במרכיבים" ); ?>

</body>
</html>
