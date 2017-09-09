<?php
require_once '../im_tools.php';
require_once 'delivery.php';
require_once '../orders/orders-common.php';

?>


<?php

$id   = $_GET["id"];
$send = $_GET["send"];

$d = new Delivery( $id );

$order_id = get_order_id( $id );

print order_info_data( $order_id );

print $d->delivery_text( ImDocumentType::delivery, false );
//$sql = 'select product_name, quantity, vat,price, line_price '
//        . ' from im_delivery_lines where delivery_id = ' . $id ;
//
//
//$fields = mysql_num_fields ( $export );
//
//$data = "<table id=\"del_table\" border=\"1\"><tr><td>פריט</td><td>כמות</td><td>מעם</td><td>מחיר</td><td>סהכ</td></tr>";
//$total = 0;
//$vat_total = 0;
//$line_number = 0;
//$calc_total = 0;
//
//while( $row = mysqli_fetch_row( $result ) )
//{
//	$line_number = $line_number + 1;
//	$line = "<tr>";
//    	$product_name = $row[0];
//	$quantity = $row[1];
//	$vat_line = $row[2];
//	$item_price = $row[3];
//	$total_line = $row[4];
//
//	// Display item name
//	$line .= "<td>" . $product_name . '</td>';
//	$line .= "<td>" . $quantity . "</td>";
//	$line .= "<td>" . $vat_line . "</td>";
//	$line .= "<td>" . $item_price . "</td>";
//	$line .= "<td>" . $total_line . "</td>"; $calc_total += $total_line;
//
//	$line .= "</tr>";
//
//	$data .= "<tr> ". trim( $line ) . "</tr>";
//}
//
//$sql = 'select vat, total '
//        . ' from im_delivery where id = ' . $id ;
//
//$row = mysqli_fetch_row( $result );
//
//$vat_total = $row[0];
//$total = $row[1];
//
//$data .= "<tr><td>עיגול</td><td></td><td></td><td></td><td>" . round(100 * ($total - $calc_total)) /100 . "</td></tr>";
//$data .= "<tr><td>סהכ ללא מעם</td><td></td><td></td><td></td><td>" . ($total - $vat_total) . "</td></tr>";
//$data .= "<tr><td>סהכ מעם</td><td></td><td></td><td></td><td>" . $vat_total . "</td></tr>";
//$data .= "<tr><td>סהכ לתשלום</td><td></td><td></td><td></td><td id=\"total\">" . $total . "</td></tr>";
//
//$data = str_replace( "\r" , "" , $data );
//
//if ( $data == "" )
//{
//    $data = "\n(0) Records Found!\n";
//}
//
//$data .= "</table>";
//
//print "$data";
//
//print "</form>";

if ( ! $send ) {
	print '<button id="btn_print" onclick="printDeliveryNotes()">הדפס תעודה</button>';
	print '<button id="btn_del" onclick="deleteDelivery()">מחק תעודה</button>';
	print '<button id="btn_edit" onclick="editDelivery()">ערוך תעודה</button>';
}

?>

<script>

    function editDelivery() {
        window.location.href = "create-delivery.php?id=<?php print $id; ?>";
    }

    function deleteDelivery() {
        var request = "delete-delivery.php?id=<?php print $id; ?>";

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                window.history.back();
                window.reload();
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }
    function printDeliveryNotes() {
        // Get the html
        document.getElementById('btn_print').style.visibility = "hidden";
        window.open("//pdfcrowd.com/url_to_pdf/");
        document.getElementById('btn_print').style.visibility = "visible";
//	var txt = document.documentElement.innerHTML;

        // Download the html
// 	var a = document.getElementById("a");
//	var file = new Blob(txt, 'text/html');
// 	a.href = URL.createObjectURL(file);
        // a.download = 're.html';

//	download(txt, 'myfilename.html', 'text/html')
//	window.open('data:text/html;charset=utf-8,<html dir="rtl" lang="he">' + txt + '</html>');

//

        // To Do: upload the file

        document.getElementById('btn_calc').style.visibility = "visible";
        document.getElementById('btn_print').style.visibility = "visible";
    }


</script>
</body>
</html>
